<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use League\Flysystem\Local\LocalFilesystemAdapter;

class AvatarService
{
    public function __construct(
        private readonly FileStorageManager $fileStorageManager,
    ) {}

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
     * @param  string  $userId  ID do usuário
     * @param  UploadedFile  $file  Arquivo enviado
     * @return string Caminho do avatar salvo
     *
     * @throws \InvalidArgumentException
     */
    public function uploadAvatar(string $userId, UploadedFile $file): string
    {
        // Validar arquivo
        $this->validateFile($file);
        $contents = file_get_contents($file->getRealPath());

        if (! is_string($contents)) {
            throw new \InvalidArgumentException('Não foi possível ler a imagem enviada.');
        }

        // Criar diretório do usuário se não existir
        $userDir = "avatars/{$userId}";

        // Gerar nome único para o arquivo
        $filename = Str::uuid().'.jpg';
        $path = "{$userDir}/{$filename}";

        // Processar e salvar imagem principal
        $this->processAndSaveImage($contents, $path, self::AVATAR_SIZE, self::JPEG_QUALITY);

        // Criar e salvar thumbnail
        $thumbnailPath = "{$userDir}/thumb_{$filename}";
        $this->processAndSaveImage($contents, $thumbnailPath, self::THUMBNAIL_SIZE, self::THUMBNAIL_QUALITY);

        return $path;
    }

    /**
     * Deletar avatar do usuário
     *
     * @param  string  $avatarPath  Caminho do avatar
     */
    public function deleteAvatar(string $avatarPath): bool
    {
        if (empty($avatarPath)) {
            return false;
        }

        $disk = $this->publicImagesDisk();

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
     * @param  string|null  $avatarPath  Caminho do avatar
     * @param  bool  $thumbnail  Se deve retornar thumbnail
     * @return string|null URL do avatar ou null se não existir
     */
    public function getAvatarUrl(?string $avatarPath, bool $thumbnail = false): ?string
    {
        if (empty($avatarPath)) {
            return null;
        }

        $path = $thumbnail ? $this->getThumbnailPath($avatarPath) : $avatarPath;
        $disk = $this->publicImagesDisk();

        if (! $disk->exists($path)) {
            return null;
        }

        // Obter URL do disco
        $url = $disk->url($path);

        // Se a URL contém 'localhost' sem porta, adicionar porta 8000
        if (str_contains($url, 'http://localhost/') || str_contains($url, 'http://localhost/storage')) {
            $url = str_replace('http://localhost', 'http://localhost:8000', $url);
        }

        // Se a URL não começar com http, construir URL completa
        if (! str_starts_with($url, 'http')) {
            $baseUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
            $url = $baseUrl.'/'.ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Processar e salvar imagem usando Intervention Image
     */
    private function processAndSaveImage(string $contents, string $path, int $size, int $quality): void
    {
        try {
            // Criar instância do ImageManager
            // Tenta usar GD primeiro, se não estiver disponível usa Imagick
            if (extension_loaded('gd')) {
                $manager = ImageManager::gd();
            } elseif (extension_loaded('imagick')) {
                $manager = ImageManager::imagick();
            } else {
                throw new \RuntimeException(
                    'Nenhum driver de imagem disponível (GD ou Imagick). '
                    .'Para upload de avatar, habilite a extensão GD no PHP: em php.ini descomente "extension=gd" e reinicie o servidor.'
                );
            }

            // Carregar e processar imagem
            $image = $manager->read($contents);

            // Fazer crop e redimensionamento para tamanho quadrado
            // O método cover() faz crop centralizado automaticamente
            $image->cover($size, $size);

            // Converter para JPEG e aplicar qualidade
            $encoded = $image->toJpeg($quality);

            // Salvar no storage
            $disk = $this->publicImagesDisk();
            $disk->put($path, $encoded->toString());
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Não foi possível processar a imagem: '.$e->getMessage()
            );
        }
    }

    /**
     * Validar arquivo enviado
     *
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        // Validar tipo MIME
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
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
     * Resolver o caminho completo no disco para servir um avatar.
     *
     * @return array{path: string, mimeType: string}|null
     */
    public function resolveAvatarFile(string $userId, string $filename): ?array
    {
        $filename = basename($filename);
        $userId = basename($userId);

        $path = "avatars/{$userId}/{$filename}";
        $disk = $this->publicImagesDisk();

        if (! $disk->exists($path)) {
            return null;
        }

        if (! $disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return null;
        }

        return [
            'path' => $disk->path($path),
            'mimeType' => $disk->mimeType($path),
        ];
    }

    private function publicImagesDisk(): FilesystemAdapter
    {
        return $this->fileStorageManager->disk(FileStorageManager::DOMAIN_PUBLIC_IMAGES);
    }

    /**
     * Obter caminho do thumbnail a partir do caminho do avatar
     */
    private function getThumbnailPath(string $avatarPath): string
    {
        $pathInfo = pathinfo($avatarPath);

        return $pathInfo['dirname'].'/thumb_'.$pathInfo['basename'];
    }
}
