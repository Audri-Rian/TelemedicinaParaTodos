<?php

namespace App\Http\Controllers;

use App\Services\AvatarService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvatarFileController extends Controller
{
    public function __construct(
        private AvatarService $avatarService
    ) {}

    public function show(string $userId, string $filename): BinaryFileResponse
    {
        $resolved = $this->avatarService->resolveAvatarFile($userId, $filename);

        if (! $resolved) {
            abort(404);
        }

        return response()->file($resolved['path'], [
            'Content-Type' => $resolved['mimeType'],
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
