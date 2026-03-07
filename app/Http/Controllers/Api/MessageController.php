<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Listar conversas do usuário atual
     */
    #[OA\PathItem(path: '/api/messages/conversations')]
    #[OA\Get(path: '/api/messages/conversations', summary: 'Listar conversas', tags: ['Mensagens'], responses: [new OA\Response(response: 200, description: 'Lista de conversas'), new OA\Response(response: 401, description: 'Não autenticado')])]
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
    #[OA\PathItem(path: '/api/messages/{userId}')]
    #[OA\Get(path: '/api/messages/{userId}', summary: 'Mensagens com um usuário', tags: ['Mensagens'], parameters: [new OA\PathParameter(name: 'userId', description: 'ID do outro usuário'), new OA\Parameter(name: 'limit', in: 'query', required: false), new OA\Parameter(name: 'before', in: 'query', required: false)], responses: [new OA\Response(response: 200, description: 'Lista de mensagens'), new OA\Response(response: 401, description: 'Não autenticado')])]
    public function messages(Request $request, string $userId): JsonResponse
    {
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
    #[OA\PathItem(path: '/api/messages')]
    #[OA\Post(path: '/api/messages', summary: 'Enviar mensagem', tags: ['Mensagens'], responses: [new OA\Response(response: 201, description: 'Mensagem enviada'), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 422, description: 'Dados inválidos')])]
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
    #[OA\PathItem(path: '/api/messages/{userId}/read')]
    #[OA\Post(path: '/api/messages/{userId}/read', summary: 'Marcar mensagens como lidas', tags: ['Mensagens'], parameters: [new OA\PathParameter(name: 'userId', description: 'ID do usuário remetente')], responses: [new OA\Response(response: 200, description: 'Mensagens marcadas como lidas'), new OA\Response(response: 401, description: 'Não autenticado')])]
    public function markAsRead(string $userId): JsonResponse
    {
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
    #[OA\PathItem(path: '/api/messages/{messageId}/delivered')]
    #[OA\Post(path: '/api/messages/{messageId}/delivered', summary: 'Marcar mensagem como entregue', tags: ['Mensagens'], parameters: [new OA\PathParameter(name: 'messageId', description: 'ID da mensagem')], responses: [new OA\Response(response: 200, description: 'Mensagem marcada como entregue'), new OA\Response(response: 401, description: 'Não autenticado'), new OA\Response(response: 403, description: 'Sem permissão')])]
    public function markAsDelivered(string $messageId): JsonResponse
    {
        try {
            $message = \App\Models\Message::findOrFail($messageId);
            
            // Verificar se o usuário atual é o destinatário
            if ($message->receiver_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para marcar esta mensagem como entregue',
                ], 403);
            }

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
    #[OA\PathItem(path: '/api/messages/unread/count')]
    #[OA\Get(path: '/api/messages/unread/count', summary: 'Contagem de mensagens não lidas', tags: ['Mensagens'], responses: [new OA\Response(response: 200, description: 'Contagem'), new OA\Response(response: 401, description: 'Não autenticado')])]
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
