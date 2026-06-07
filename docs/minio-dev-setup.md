# MinIO DEV Setup

Guia rápido para usar MinIO com os discos `s3_private` e `s3_public` do Laravel.

## 1) Subir containers

Com compose de desenvolvimento:

```bash
docker compose -f docker-compose.dev.yml up -d minio minio-init
```

Com compose principal:

```bash
docker compose up -d minio minio-init
```

O serviço `minio-init` é idempotente e prepara:

- bucket `telemedicina-private` (privado)
- bucket `telemedicina-public` (leitura pública)
- usuário de aplicação `MINIO_APP_USER` com policy `readwrite`

## 2) Endpoint correto por cenário

- App Laravel rodando no host: `AWS_ENDPOINT=http://localhost:9000`
- App Laravel rodando em container no mesmo compose: `AWS_ENDPOINT=http://minio:9000`
- App Laravel em outro computador da rede: `AWS_ENDPOINT=http://<IP_DO_SERVIDOR_MINIO>:9000`

Sempre manter:

```env
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## 3) Credenciais recomendadas

No Laravel, use usuário de aplicação (não root/admin):

```env
AWS_ACCESS_KEY_ID=telemedicina-app
AWS_SECRET_ACCESS_KEY=telemedicina-app-secret
```

Credenciais root ficam apenas para bootstrap/admin no compose:

```env
MINIO_ROOT_USER=minioadmin
MINIO_ROOT_PASSWORD=minioadmin
MINIO_APP_USER=telemedicina-app
MINIO_APP_PASSWORD=telemedicina-app-secret
```

## 4) Checklist de validação

```bash
php artisan storage:health-check
php artisan storage:cleanup-expired --dry-run
```

Esperado:

- todos os domínios `UP` no healthcheck
- limpeza com domínio `lgpd_exports` sem erro

## 5) Migração para MinIO em outro computador (rede local)

1. Subir MinIO no servidor remoto e liberar porta `9000`.
2. Confirmar conectividade do host Laravel até o IP remoto.
3. Trocar `AWS_ENDPOINT` no `.env` para o IP remoto.
4. Manter `AWS_USE_PATH_STYLE_ENDPOINT=true`.
5. Garantir buckets `telemedicina-private` e `telemedicina-public`.
6. Garantir credencial de app no servidor remoto.
7. Limpar cache de config:
    - `php artisan config:clear`
8. Rodar smoke tests:
    - `php artisan storage:health-check`
    - upload/download real de documento e avatar

## 6) Próximo passo para produção

- Publicar MinIO atrás de HTTPS (reverse proxy/domínio).
- Trocar secrets dev por secrets fortes.
- Restringir acesso por rede/IP.
- Aplicar lifecycle policy para `lgpd_exports` conforme retenção.
