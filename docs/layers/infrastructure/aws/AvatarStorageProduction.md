# Configuração de Armazenamento de Avatares para Produção (AWS S3)

*Documento em: `docs/layers/infrastructure/aws/` (Camada de Infraestrutura)*

Este documento explica como configurar o sistema de upload de avatares para usar Amazon S3 em produção, mantendo o armazenamento local para desenvolvimento.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Pré-requisitos](#pré-requisitos)
3. [Configuração do AWS S3](#configuração-do-aws-s3)
4. [Configuração do Laravel](#configuração-do-laravel)
5. [Atualização do AvatarService](#atualização-do-avatarservice)
6. [Configuração do CloudFront (Opcional)](#configuração-do-cloudfront-opcional)
7. [Variáveis de Ambiente](#variáveis-de-ambiente)
8. [Testes](#testes)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

O sistema de avatares foi configurado para funcionar em dois modos:

- **Desenvolvimento**: Usa `storage/app/public` (disco local)
- **Produção**: Usa Amazon S3 (com opção de CloudFront como CDN)

A transição entre os modos é feita através de variáveis de ambiente, sem necessidade de alterar código.

---

## 📦 Pré-requisitos

1. Conta AWS ativa
2. AWS CLI instalado e configurado (opcional, mas recomendado)
3. Pacote Laravel Flysystem AWS S3 instalado:

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

---

## 🪣 Configuração do AWS S3

### Passo 1: Criar Bucket S3

1. Acesse o [AWS Console](https://console.aws.amazon.com/s3/)
2. Clique em "Create bucket"
3. Configure:
   - **Bucket name**: `telemedicina-assets` (ou outro nome único)
   - **Region**: Escolha a região mais próxima dos seus usuários (ex: `us-east-1`, `sa-east-1`)
   - **Block Public Access**: Desmarque "Block all public access" (ou configure políticas específicas)
   - **Versioning**: Opcional, mas recomendado para produção
   - **Encryption**: Habilite server-side encryption (SSE-S3 ou SSE-KMS)

### Passo 2: Configurar Políticas do Bucket

1. Vá em **Permissions** → **Bucket Policy**
2. Adicione a seguinte política (ajuste o `ACCOUNT_ID` e `BUCKET_NAME`):

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowLaravelAppUpload",
      "Effect": "Allow",
      "Principal": {
        "AWS": "arn:aws:iam::ACCOUNT_ID:user/laravel-app"
      },
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:PutObjectAcl"
      ],
      "Resource": "arn:aws:s3:::BUCKET_NAME/*"
    },
    {
      "Sid": "PublicReadAvatars",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::BUCKET_NAME/avatars/*"
    }
  ]
}
```

### Passo 3: Criar IAM User para Aplicação

1. Acesse [IAM Console](https://console.aws.amazon.com/iam/)
2. Crie um novo usuário: `laravel-app`
3. Anexe a política `AmazonS3FullAccess` (ou crie uma política customizada mais restritiva)
4. Crie Access Keys:
   - Vá em **Security credentials** → **Create access key**
   - Escolha "Application running outside AWS"
   - **IMPORTANTE**: Salve as credenciais imediatamente (não será possível visualizar novamente)

### Passo 4: Configurar CORS (se necessário)

Se você precisar fazer uploads diretos do frontend para S3, configure CORS:

1. Vá em **Permissions** → **Cross-origin resource sharing (CORS)**
2. Adicione:

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
    "AllowedOrigins": ["https://seudominio.com"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
```

---

## ⚙️ Configuração do Laravel

### Passo 1: Atualizar `config/filesystems.php`

O arquivo já possui a configuração básica do S3. Verifique se está assim:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'), // URL do CloudFront se usar
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
    'report' => false,
],
```

### Passo 2: Criar Disco Específico para Avatares (Opcional)

Você pode criar um disco específico para avatares em `config/filesystems.php`:

```php
's3_avatars' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'root' => 'avatars',
    'url' => env('AWS_URL') . '/avatars',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

---

## 🔧 Atualização do AvatarService

Para usar S3 em produção, você precisa atualizar o `AvatarService` para detectar o ambiente e usar o disco apropriado.

### Modificação Necessária

Abra `app/Services/AvatarService.php` e atualize os métodos que usam `Storage::disk('public')`:

```php
// No início da classe, adicione:
private function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
{
    return Storage::disk(
        app()->environment('production') ? 's3' : 'public'
    );
}

// Atualize os métodos:
public function uploadAvatar(string $userId, UploadedFile $file): string
{
    $this->validateFile($file);
    
    $disk = $this->getDisk();
    $userDir = "avatars/{$userId}";
    $filename = Str::uuid() . '.jpg';
    $path = "{$userDir}/{$filename}";
    
    // ... resto do código usando $disk ao invés de Storage::disk('public')
    $this->processAndSaveImage($file, $path, self::AVATAR_SIZE, self::JPEG_QUALITY, $disk);
    
    // ...
}

public function deleteAvatar(string $avatarPath): bool
{
    if (empty($avatarPath)) {
        return false;
    }
    
    $disk = $this->getDisk();
    
    if ($disk->exists($avatarPath)) {
        $disk->delete($avatarPath);
    }
    
    // ...
}

public function getAvatarUrl(?string $avatarPath, bool $thumbnail = false): ?string
{
    if (empty($avatarPath)) {
        return null;
    }
    
    $path = $thumbnail ? $this->getThumbnailPath($avatarPath) : $avatarPath;
    $disk = $this->getDisk();
    
    if (!$disk->exists($path)) {
        return null;
    }
    
    return $disk->url($path);
}
```

**Nota**: Se você preferir, posso fazer essas alterações automaticamente. A lógica atual funciona apenas para desenvolvimento.

---

## ☁️ Configuração do CloudFront (Opcional)

CloudFront melhora a performance distribuindo as imagens através de uma CDN global.

### Passo 1: Criar Distribution

1. Acesse [CloudFront Console](https://console.aws.amazon.com/cloudfront/)
2. Clique em "Create Distribution"
3. Configure:
   - **Origin Domain**: Selecione seu bucket S3
   - **Origin Access**: Escolha "Origin access control settings (recommended)"
   - **Viewer Protocol Policy**: "Redirect HTTP to HTTPS"
   - **Allowed HTTP Methods**: GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE
   - **Cache Policy**: "CachingOptimized" ou customizada
   - **Price Class**: Escolha conforme sua necessidade

### Passo 2: Atualizar Bucket Policy

Após criar a distribution, o CloudFront fornecerá uma política que você deve adicionar ao bucket S3.

### Passo 3: Atualizar Variável de Ambiente

Use a URL do CloudFront como `AWS_URL`:

```env
AWS_URL=https://d1234567890.cloudfront.net
```

---

## 🔐 Variáveis de Ambiente

Adicione as seguintes variáveis no seu `.env` de produção:

```env
# AWS Configuration
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=telemedicina-assets
AWS_URL=https://d1234567890.cloudfront.net  # Ou URL do S3 se não usar CloudFront

# Filesystem (opcional, padrão é 'local')
FILESYSTEM_DISK=s3
```

**IMPORTANTE**: Nunca commite as credenciais AWS no Git. Use variáveis de ambiente ou um gerenciador de secrets (AWS Secrets Manager, Laravel Vapor, etc.).

---

## 🧪 Testes

### Teste Local (Desenvolvimento)

1. Certifique-se de que `FILESYSTEM_DISK=local` ou não está definido
2. Faça upload de um avatar através da interface
3. Verifique se o arquivo foi salvo em `storage/app/public/avatars/{user_id}/`

### Teste em Produção

1. Configure as variáveis de ambiente no servidor
2. Faça upload de um avatar
3. Verifique no console S3 se o arquivo foi criado em `avatars/{user_id}/`
4. Acesse a URL retornada para verificar se a imagem carrega corretamente

### Comando de Teste via Tinker

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();
$service = app(\App\Services\AvatarService::class);
$url = $service->getAvatarUrl($user->avatar_path);
echo $url;
```

---

## 🔍 Troubleshooting

### Erro: "Access Denied" ao fazer upload

**Causa**: Credenciais IAM incorretas ou políticas muito restritivas.

**Solução**:
1. Verifique se as credenciais estão corretas no `.env`
2. Verifique as políticas IAM do usuário
3. Verifique a bucket policy do S3

### Erro: "The bucket you are attempting to access must be addressed using the specified endpoint"

**Causa**: Região incorreta configurada.

**Solução**: Verifique se `AWS_DEFAULT_REGION` corresponde à região do bucket.

### Imagens não aparecem após upload

**Causa**: URL incorreta ou problema de permissões.

**Solução**:
1. Verifique se `AWS_URL` está configurado corretamente
2. Verifique se a bucket policy permite leitura pública de `avatars/*`
3. Teste a URL diretamente no navegador

### Erro: "Class 'League\Flysystem\AwsS3v3\AwsS3Adapter' not found"

**Causa**: Pacote não instalado.

**Solução**:
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

---

## 📊 Estrutura de Pastas no S3

Após a configuração, a estrutura no bucket será:

```
telemedicina-assets/
└── avatars/
    ├── {user_id_1}/
    │   ├── {uuid}.jpg
    │   └── thumb_{uuid}.jpg
    ├── {user_id_2}/
    │   ├── {uuid}.jpg
    │   └── thumb_{uuid}.jpg
    └── ...
```

---

## 💰 Estimativa de Custos

### S3 Storage
- Primeiros 50 TB: $0.023 por GB/mês
- Exemplo: 10.000 avatares de 200KB cada = ~2GB = ~$0.05/mês

### S3 Requests
- PUT requests: $0.005 por 1.000 requests
- GET requests: $0.0004 por 1.000 requests

### CloudFront (se usar)
- Primeiros 10 TB: $0.085 por GB transferido
- Requests: $0.0075 por 10.000 requests

**Estimativa mensal para 10.000 usuários ativos**: ~$5-15/mês (dependendo do tráfego)

---

## 🔄 Migração de Dados Existentes

Se você já tem avatares salvos localmente e quer migrar para S3:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Storage;

$localDisk = Storage::disk('public');
$s3Disk = Storage::disk('s3');

$users = \App\Models\User::whereNotNull('avatar_path')->get();

foreach ($users as $user) {
    $path = $user->avatar_path;
    
    if ($localDisk->exists($path)) {
        $content = $localDisk->get($path);
        $s3Disk->put($path, $content);
        
        // Thumbnail
        $thumbPath = 'avatars/' . $user->id . '/thumb_' . basename($path);
        if ($localDisk->exists($thumbPath)) {
            $thumbContent = $localDisk->get($thumbPath);
            $s3Disk->put($thumbPath, $thumbContent);
        }
        
        echo "Migrado: {$user->name}\n";
    }
}
```

---

## ✅ Checklist de Deploy

- [ ] Bucket S3 criado e configurado
- [ ] IAM User criado com permissões adequadas
- [ ] Bucket Policy configurada
- [ ] CORS configurado (se necessário)
- [ ] Variáveis de ambiente configuradas no servidor
- [ ] Pacote `league/flysystem-aws-s3-v3` instalado
- [ ] `AvatarService` atualizado para usar S3 em produção
- [ ] CloudFront configurado (opcional)
- [ ] Testes realizados em ambiente de staging
- [ ] Backup dos avatares locais (se houver)
- [ ] Migração de dados realizada (se aplicável)

---

## 📚 Recursos Adicionais

- [Laravel File Storage](https://laravel.com/docs/filesystem)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
- [Flysystem AWS S3 Adapter](https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/)

---

**Última atualização**: Novembro 2025

