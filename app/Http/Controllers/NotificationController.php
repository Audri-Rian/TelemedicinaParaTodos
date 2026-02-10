<?php

namespace App\Http\Controllers;

use App\Presenters\NotificationPresenter;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        return response()->json([
            'data' => $this->presenter->transform($notification),
        ]);
    }
}

