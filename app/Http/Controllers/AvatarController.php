<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvatarUploadRequest;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AvatarController extends Controller
{
    public function __construct(
        private AvatarService $avatarService
    ) {
    }

    /**
     * Upload de avatar do usuário autenticado
     */
    #[OA\PathItem(path: '/api/avatar/upload')]
    #[OA\Post(
        path: '/api/avatar/upload',
        operationId: 'uploadAvatar',
        summary: 'Upload de avatar',
        tags: ['Avatar'],
        security: [['cookieAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(description: 'Envie como multipart/form-data com campo "avatar" (arquivo de imagem)')),
        responses: [
            new OA\Response(response: 200, description: 'Avatar atualizado', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'avatar_url', type: 'string'), new OA\Property(property: 'avatar_thumbnail_url', type: 'string')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Arquivo inválido'),
        ]
    )]
    public function upload(AvatarUploadRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $file = $request->file('avatar');

            // Deletar avatar anterior se existir
            if ($user->avatar_path) {
                $this->avatarService->deleteAvatar($user->avatar_path);
            }

            // Upload do novo avatar
            $avatarPath = $this->avatarService->uploadAvatar($user->id, $file);

            // Atualizar usuário
            $user->update(['avatar_path' => $avatarPath]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar atualizado com sucesso.',
                'avatar_url' => $this->avatarService->getAvatarUrl($avatarPath),
                'avatar_thumbnail_url' => $this->avatarService->getAvatarUrl($avatarPath, true),
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload de avatar', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o upload. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Deletar avatar do usuário autenticado
     */
    #[OA\PathItem(path: '/api/avatar/delete')]
    #[OA\Delete(
        path: '/api/avatar/delete',
        operationId: 'deleteAvatar',
        summary: 'Remover avatar',
        tags: ['Avatar'],
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Avatar removido', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Usuário sem avatar'),
        ]
    )]
    public function delete(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->avatar_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não possui avatar para deletar.',
                ], 404);
            }

            // Deletar arquivos
            $this->avatarService->deleteAvatar($user->avatar_path);

            // Atualizar usuário
            $user->update(['avatar_path' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar removido com sucesso.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar avatar', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover avatar. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Obter URL do avatar do usuário autenticado
     */
    #[OA\PathItem(path: '/api/avatar/show')]
    #[OA\Get(
        path: '/api/avatar/show',
        operationId: 'getAvatarUrl',
        summary: 'URL do avatar',
        tags: ['Avatar'],
        security: [['cookieAuth' => []]],
        parameters: [new OA\Parameter(name: 'thumbnail', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))],
        responses: [
            new OA\Response(response: 200, description: 'URL do avatar', content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'avatar_url', type: 'string')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $thumbnail = $request->boolean('thumbnail', false);

        $avatarUrl = $this->avatarService->getAvatarUrl($user->avatar_path, $thumbnail);

        return response()->json([
            'avatar_url' => $avatarUrl,
        ], 200);
    }
}
