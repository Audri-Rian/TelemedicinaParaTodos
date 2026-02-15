<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Listar conversas do usuário atual
     */
    public function conversations(): JsonResponse
    {
        try {
            $conversations = $this->messageService->getConversations();
            
            return response()->json([
                'success' => true,
                'data' => $conversations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar conversas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar mensagens entre o usuário atual e outro usuário
     */
    public function messages(Request $request, string $userId): JsonResponse
    {
        Gate::authorize('viewConversation', $userId);

        try {
            $limit = $request->input('limit', 50);
            $beforeMessageId = $request->input('before');

            $messages = $this->messageService->getMessagesBetweenUsers($userId, $limit, $beforeMessageId);

            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar mensagens: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar uma mensagem
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        try {
            $message = $this->messageService->sendMessage(
                $request->validated()['receiver_id'],
                $request->validated()['content'],
                $request->validated()['appointment_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marcar mensagens como lidas
     */
    public function markAsRead(string $userId): JsonResponse
    {
        Gate::authorize('viewConversation', $userId);

        try {
            $count = $this->messageService->markMessagesAsRead($userId);

            return response()->json([
                'success' => true,
                'message' => 'Mensagens marcadas como lidas',
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar mensagens como lidas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar mensagem como entregue (delivered)
     */
    public function markAsDelivered(string $messageId): JsonResponse
    {
        try {
            $message = \App\Models\Message::findOrFail($messageId);
            $this->authorize('markAsDelivered', $message);

            $message->markAsDelivered();

            return response()->json([
                'success' => true,
                'message' => 'Mensagem marcada como entregue',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar mensagem como entregue: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Contar mensagens não lidas
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = $this->messageService->getUnreadCount();

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao contar mensagens não lidas: ' . $e->getMessage(),
            ], 500);
        }
    }
}
