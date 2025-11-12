<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class AvatarService
{
    /**
     * Tamanho padrão do avatar (largura e altura)
     */
    private const AVATAR_SIZE = 400;

    /**
     * Tamanho do thumbnail
     */
    private const THUMBNAIL_SIZE = 150;

    /**
     * Qualidade de compressão JPEG
     */
    private const JPEG_QUALITY = 85;

    /**
     * Qualidade de compressão JPEG para thumbnail
     */
    private const THUMBNAIL_QUALITY = 75;

    /**
     * Tamanho máximo do arquivo (5MB)
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Tipos MIME permitidos
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Upload e processamento de avatar
     *
     * @param string $userId ID do usuário
     * @param UploadedFile $file Arquivo enviado
     * @return string Caminho do avatar salvo
     * @throws \InvalidArgumentException
     */
    public function uploadAvatar(string $userId, UploadedFile $file): string
    {
        // Validar arquivo
        $this->validateFile($file);

        // Criar diretório do usuário se não existir
        $userDir = "avatars/{$userId}";
        
        // Gerar nome único para o arquivo
        $filename = Str::uuid() . '.jpg';
        $path = "{$userDir}/{$filename}";

        // Processar e salvar imagem principal
        $this->processAndSaveImage($file, $path, self::AVATAR_SIZE, self::JPEG_QUALITY);

        // Criar e salvar thumbnail
        $thumbnailPath = "{$userDir}/thumb_{$filename}";
        $this->processAndSaveImage($file, $thumbnailPath, self::THUMBNAIL_SIZE, self::THUMBNAIL_QUALITY);

        return $path;
    }

    /**
     * Deletar avatar do usuário
     *
     * @param string $avatarPath Caminho do avatar
     * @return bool
     */
    public function deleteAvatar(string $avatarPath): bool
    {
        if (empty($avatarPath)) {
            return false;
        }

        $disk = Storage::disk('public');

        // Deletar imagem principal
        if ($disk->exists($avatarPath)) {
            $disk->delete($avatarPath);
        }

        // Deletar thumbnail
        $thumbnailPath = $this->getThumbnailPath($avatarPath);
        if ($disk->exists($thumbnailPath)) {
            $disk->delete($thumbnailPath);
        }

        return true;
    }

    /**
     * Obter URL do avatar
     *
     * @param string|null $avatarPath Caminho do avatar
     * @param bool $thumbnail Se deve retornar thumbnail
     * @return string|null URL do avatar ou null se não existir
     */
    public function getAvatarUrl(?string $avatarPath, bool $thumbnail = false): ?string
    {
        if (empty($avatarPath)) {
            return null;
        }

        $path = $thumbnail ? $this->getThumbnailPath($avatarPath) : $avatarPath;
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return null;
        }

        // Obter URL do disco
        $url = $disk->url($path);
        
        // Se a URL contém 'localhost' sem porta, adicionar porta 8000
        if (str_contains($url, 'http://localhost/') || str_contains($url, 'http://localhost/storage')) {
            $url = str_replace('http://localhost', 'http://localhost:8000', $url);
        }
        
        // Se a URL não começar com http, construir URL completa
        if (!str_starts_with($url, 'http')) {
            $baseUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
            $url = $baseUrl . '/' . ltrim($url, '/');
        }
        
        return $url;
    }

    /**
     * Processar e salvar imagem usando Intervention Image
     *
     * @param UploadedFile $file
     * @param string $path
     * @param int $size
     * @param int $quality
     * @return void
     */
    private function processAndSaveImage(UploadedFile $file, string $path, int $size, int $quality): void
    {
        try {
            // Criar instância do ImageManager
            // Tenta usar GD primeiro, se não estiver disponível usa Imagick
            if (extension_loaded('gd')) {
                $manager = ImageManager::gd();
            } elseif (extension_loaded('imagick')) {
                $manager = ImageManager::imagick();
            } else {
                throw new \RuntimeException('Nenhum driver de imagem disponível (GD ou Imagick).');
            }
            
            // Carregar e processar imagem
            $image = $manager->read($file->getRealPath());
            
            // Fazer crop e redimensionamento para tamanho quadrado
            // O método cover() faz crop centralizado automaticamente
            $image->cover($size, $size);
            
            // Converter para JPEG e aplicar qualidade
            $encoded = $image->toJpeg($quality);
            
            // Salvar no storage
            $disk = Storage::disk('public');
            $disk->put($path, $encoded->toString());
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Não foi possível processar a imagem: ' . $e->getMessage()
            );
        }
    }

    /**
     * Validar arquivo enviado
     *
     * @param UploadedFile $file
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        // Validar tipo MIME
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException(
                'Formato de imagem inválido. Apenas JPEG, PNG e WebP são permitidos.'
            );
        }

        // Validar tamanho
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                'Imagem muito grande. O tamanho máximo permitido é 5MB.'
            );
        }

        // Validar se é realmente uma imagem
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('O arquivo enviado não é uma imagem válida.');
        }
    }

    /**
     * Obter caminho do thumbnail a partir do caminho do avatar
     *
     * @param string $avatarPath
     * @return string
     */
    private function getThumbnailPath(string $avatarPath): string
    {
        $pathInfo = pathinfo($avatarPath);
        return $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
    }
}

