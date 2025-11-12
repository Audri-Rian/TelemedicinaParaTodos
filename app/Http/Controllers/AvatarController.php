<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvatarUploadRequest;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AvatarController extends Controller
{
    public function __construct(
        private AvatarService $avatarService
    ) {
    }

    /**
     * Upload de avatar do usuário autenticado
     */
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
