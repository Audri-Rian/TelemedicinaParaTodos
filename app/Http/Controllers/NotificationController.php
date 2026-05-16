<?php

namespace App\Http\Controllers;

use App\Presenters\NotificationPresenter;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private NotificationPresenter $presenter
    ) {}

    /**
     * Listar notificações do usuário
     */
    #[OA\PathItem(path: '/api/notifications')]
    #[OA\Get(
        path: '/api/notifications',
        operationId: 'listNotifications',
        summary: 'Listar notificações',
        tags: ['Notificações'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'unread_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada (data + meta: current_page, last_page, per_page, total)', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')), new OA\Property(property: 'meta', type: 'object')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $defaultPerPage = (int) config('telemedicine.notifications.per_page', 15);
        $maxPerPage = (int) config('telemedicine.notifications.max_per_page', 100);

        $perPage = (int) $request->get('per_page', $defaultPerPage);
        if ($perPage <= 0) {
            $perPage = $defaultPerPage;
        }
        $perPage = min($perPage, $maxPerPage);
        $type = $request->get('type');
        $unreadOnly = $request->boolean('unread_only', false);

        $query = $user->notifications()->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($perPage);

        return response()->json([
            'data' => $this->presenter->transformMany($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Obter notificações não lidas
     */
    #[OA\PathItem(path: '/api/notifications/unread')]
    #[OA\Get(
        path: '/api/notifications/unread',
        operationId: 'getUnreadNotifications',
        summary: 'Notificações não lidas',
        tags: ['Notificações'],
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista e contagem', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')), new OA\Property(property: 'count', type: 'integer')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notificationService->getUnread($user);

        return response()->json([
            'data' => $this->presenter->transformMany($notifications),
            'count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Obter contador de notificações não lidas
     */
    #[OA\PathItem(path: '/api/notifications/unread-count')]
    #[OA\Get(
        path: '/api/notifications/unread-count',
        operationId: 'getUnreadNotificationsCount',
        summary: 'Contagem de não lidas',
        tags: ['Notificações'],
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Contagem', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'count', type: 'integer')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationService->getUnreadCount($request->user()),
        ]);
    }

    /**
     * Marcar notificação como lida
     */
    #[OA\PathItem(path: '/api/notifications/{id}/read')]
    #[OA\Post(
        path: '/api/notifications/{id}/read',
        operationId: 'markNotificationAsRead',
        summary: 'Marcar como lida',
        tags: ['Notificações'],
        security: [['cookieAuth' => []]],
        parameters: [new OA\PathParameter(name: 'id', description: 'ID da notificação', schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Notificação marcada como lida', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrada'),
        ]
    )]
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'message' => 'Notificação marcada como lida',
            'data' => $this->presenter->transform($notification->fresh()),
        ]);
    }

    /**
     * Marcar todas as notificações como lidas
     */
    #[OA\PathItem(path: '/api/notifications/read-all')]
    #[OA\Post(
        path: '/api/notifications/read-all',
        operationId: 'markAllNotificationsAsRead',
        summary: 'Marcar todas como lidas',
        tags: ['Notificações'],
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Quantidade marcada', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'count', type: 'integer')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => "{$count} notificações marcadas como lidas",
            'count' => $count,
        ]);
    }

    /**
     * Obter uma notificação específica
     */
    #[OA\PathItem(path: '/api/notifications/{id}')]
    #[OA\Get(
        path: '/api/notifications/{id}',
        operationId: 'getNotification',
        summary: 'Exibir notificação',
        tags: ['Notificações'],
        security: [['cookieAuth' => []]],
        parameters: [new OA\PathParameter(name: 'id', description: 'ID da notificação', schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Notificação', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'data', type: 'object')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrada'),
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);

        return response()->json([
            'data' => $this->presenter->transform($notification),
        ]);
    }
}
