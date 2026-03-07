<?php

namespace App\Http\Controllers;

use App\Presenters\NotificationPresenter;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private NotificationPresenter $presenter
    ) {
        $this->middleware('auth');
        
        // Forçar JSON para todas as requisições desta API
        $this->middleware(function ($request, $next) {
            $request->headers->set('Accept', 'application/json');
            return $next($request);
        });
    }

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
        $user = Auth::user();
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
    public function unread(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'data' => [],
                    'count' => 0,
                ], 401);
            }
            
            // Usar count direto primeiro
            try {
                $count = \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();
            } catch (\Throwable $countError) {
                \Log::error('Erro ao contar notificações: ' . $countError->getMessage());
                return response()->json([
                    'data' => [],
                    'count' => 0,
                ]);
            }
            
            // Se não houver notificações, retornar vazio
            if ($count === 0) {
                return response()->json([
                    'data' => [],
                    'count' => 0,
                ]);
            }
            
            // Carregar notificações não lidas com tratamento de erro
            try {
                // Usar query builder direto para evitar problemas com relacionamento
                $listLimit = (int) config('telemedicine.notifications.list_limit', 10);
                $notificationIds = \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->orderBy('created_at', 'desc')
                    ->limit($listLimit)
                    ->pluck('id');
                
                if ($notificationIds->isEmpty()) {
                    return response()->json([
                        'data' => [],
                        'count' => $count,
                    ]);
                }
                
                // Carregar notificações pelos IDs
                $notifications = \App\Models\Notification::whereIn('id', $notificationIds)
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Transformar notificações com tratamento de erro individual
                $transformedData = [];
                foreach ($notifications as $notification) {
                    try {
                        $transformedData[] = $this->presenter->transform($notification);
                    } catch (\Throwable $transformError) {
                        \Log::warning('Erro ao transformar notificação individual: ' . $transformError->getMessage(), [
                            'notification_id' => $notification->id ?? null,
                            'trace' => $transformError->getTraceAsString(),
                        ]);
                        // Continuar com as outras notificações
                    }
                }

                return response()->json([
                    'data' => $transformedData,
                    'count' => $count,
                ]);
            } catch (\Throwable $loadError) {
                \Log::error('Erro ao carregar notificações: ' . $loadError->getMessage(), [
                    'trace' => $loadError->getTraceAsString(),
                    'file' => $loadError->getFile(),
                    'line' => $loadError->getLine(),
                    'user_id' => $user->id,
                ]);
                // Se houver erro ao carregar, retornar vazio
                return response()->json([
                    'data' => [],
                    'count' => 0,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Erro geral ao carregar notificações não lidas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);
            // Retornar 200 com dados vazios em caso de erro, para não quebrar o frontend
            return response()->json([
                'data' => [],
                'count' => 0,
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ]);
        }
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
    public function unreadCount(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['count' => 0], 401);
            }
            
            // Verificar se a tabela existe
            if (!\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                \Log::warning('Tabela notifications não existe');
                return response()->json(['count' => 0]);
            }
            
            // Usar DB facade diretamente para evitar problemas com o modelo
            try {
                $count = \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();

                return response()->json(['count' => (int) $count]);
            } catch (\Illuminate\Database\QueryException $dbError) {
                \Log::error('Erro de banco de dados ao contar notificações: ' . $dbError->getMessage(), [
                    'sql' => $dbError->getSql() ?? null,
                    'bindings' => $dbError->getBindings() ?? null,
                    'user_id' => $user->id,
                ]);
                return response()->json(['count' => 0]);
            }
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar contador de notificações: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);
            // Retornar 200 com count 0 em caso de erro, para não quebrar o frontend
            return response()->json([
                'count' => 0,
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ]);
        }
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
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();
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
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user);

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
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        return response()->json([
            'data' => $this->presenter->transform($notification),
        ]);
    }
}

