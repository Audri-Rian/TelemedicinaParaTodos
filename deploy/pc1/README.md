# Deploy PC1 Consolidado

Fonte da verdade para o homelab do projeto Telemedicina Para Todos no PC1 (`ssh pc1`). Esta stack consolida app Laravel, PostgreSQL, Redis, RabbitMQ, MinIO, Nginx, Reverb, workers e scheduler em um único `docker compose`. O SFU MediaSoup no Hetzner fica fora desta stack.

## Estado Atual Verificado

Em 2026-05-16, `ssh pc1` respondeu como `pc1intelceleron`, com IPs `192.168.1.21`, `192.168.1.15` e Tailscale `100.79.212.81`. Docker `29.2.1` e Compose `v5.0.2` estão instalados. Havia um container `minio` existente publicando `9000-9001`; pare ou migre esse serviço antes de subir a stack consolidada para evitar conflito de nomes/portas.

Esse MinIO existente foi adotado como storage oficial do PC1 em 2026-05-16:

- Compose legado: `/home/pc1intelceleron/minio/docker-compose.yml`
- Dados persistentes: `/home/pc1intelceleron/minio/data`
- Bucket legado preservado: `telemedicine`
- Buckets da estrutura atual criados: `telemedicina-private`, `telemedicina-public`
- Usuario de aplicacao criado: `telemedicina-app`

O `deploy/pc1/docker-compose.yml` monta `MINIO_DATA_PATH=/home/pc1intelceleron/minio/data`, assim os dados existentes sao reaproveitados quando o MinIO passar a ser gerenciado pela stack consolidada.

## Arquivos

| Arquivo                            | Finalidade                                                 |
| ---------------------------------- | ---------------------------------------------------------- |
| `docker-compose.yml`               | Stack principal PC1.                                       |
| `.env.example`                     | Matriz de variáveis sem segredos.                          |
| `nginx/conf.d/default.conf`        | Laravel via PHP-FPM.                                       |
| `nginx/conf.d/reverb.conf`         | WebSocket Reverb via `ws.<dominio>`.                       |
| `cloudflared/config.yml.example`   | Referência de ingress do tunnel existente.                 |
| `docker-compose.observability.yml` | Overlay leve: node-exporter e cAdvisor; Promtail opcional. |
| `scripts/smoke-test.sh`            | Checklist automatizado básico.                             |

## Primeiro Deploy

```bash
ssh pc1
cd ~/TelemedicinaParaTodos/deploy/pc1
cp .env.example .env
nano .env
docker compose config --quiet
docker compose up -d postgres redis rabbitmq minio minio-init
docker compose run --rm app php artisan migrate --force
docker compose up -d app reverb queue scheduler nginx
```

Para ativar o worker RabbitMQ dedicado:

```bash
docker compose --profile rabbit-workers up -d queue-rabbit
```

Para ativar observabilidade leve:

```bash
docker compose -f docker-compose.yml -f docker-compose.observability.yml up -d node-exporter cadvisor
```

Promtail fica em profile separado porque precisa de um Loki real:

```bash
docker compose -f docker-compose.yml -f docker-compose.observability.yml --profile promtail up -d promtail
```

## Variáveis Obrigatórias

Preencha no `.env` antes de subir:

```dotenv
APP_KEY=
DB_PASSWORD=
RABBITMQ_PASSWORD=
MINIO_ROOT_USER=
MINIO_ROOT_PASSWORD=
MINIO_APP_PASSWORD=
MINIO_DATA_PATH=/home/pc1intelceleron/minio/data
REVERB_APP_KEY=
REVERB_APP_SECRET=
SFU_HTTP_URL=
SFU_WS_URL=
SFU_API_SECRET=
SFU_JWT_SECRET=
```

Use `php artisan key:generate --show` para `APP_KEY`. Gere senhas fortes para banco, RabbitMQ e MinIO. Não reutilize `secret` ou `minioadmin` em demonstração pública.

As credenciais reais do MinIO adotado estao no PC1 em `/home/pc1intelceleron/minio/.env`. Copie `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`, `MINIO_APP_USER` e `MINIO_APP_PASSWORD` desse arquivo para `deploy/pc1/.env` no deploy real. Nao commite esse `.env`.

Para demonstracao local/LAN sem certificado, use `APP_ENV=local`, `APP_DEBUG=false`, `APP_URL=http://192.168.1.21` e mantenha `SIGNATURE_DRIVER=null`. Para producao real, nao use `SIGNATURE_DRIVER=null`; configure o certificado A1/ICP-Brasil e volte `APP_ENV=production`.

## Transicao do MinIO Standalone

O MinIO atual roda fora da stack consolidada com container `minio`. Para transferir o gerenciamento para `deploy/pc1`, use o mesmo `MINIO_DATA_PATH` e faca uma transicao controlada:

```bash
ssh pc1
cd ~/minio
docker compose ps
docker compose stop minio

cd ~/TelemedicinaParaTodos/deploy/pc1
docker compose up -d minio minio-init
docker compose ps minio minio-init
```

Nao rode o MinIO standalone e o MinIO da stack consolidada ao mesmo tempo apontando para o mesmo diretorio de dados.

## Cloudflare Tunnel

O tunnel existente é `pc3-telemedicina`, ID `6499541c-ddd9-4208-b7a7-efea91448bcf`. Mantenha o nome até a migração estabilizar.

1. Instale `cloudflared` no host PC1.
2. No painel Cloudflare Zero Trust, gere o token do tunnel existente e rode `sudo cloudflared service install <TOKEN>` no PC1.
3. Configure Public Hostnames:
    - `app.<dominio>` -> `http://127.0.0.1:80`
    - `ws.<dominio>` -> `http://127.0.0.1:80`
4. Quando o connector PC1 aparecer conectado e o app responder, pare o connector antigo no PC3:

```bash
sudo systemctl stop cloudflared
sudo systemctl disable cloudflared
```

Enquanto PC1 e PC3 ficarem conectados ao mesmo tunnel, a Cloudflare pode distribuir tráfego entre os dois connectors.

O Nginx respeita `X-Forwarded-Proto` quando vier do tunnel e usa o scheme direto quando o acesso for LAN. Assim `http://192.168.1.21` gera assets HTTP, enquanto o tunnel `https://app.<dominio>` gera assets HTTPS e HSTS.

## Permissoes de Runtime

Os containers `app`, `reverb`, `queue`, `queue-rabbit` e `scheduler` ajustam `storage` e `bootstrap/cache` ao iniciar. Os comandos Artisan de longa duracao rodam como `www-data` via `su-exec`, evitando que logs/cache sejam recriados como `root` e causem erro HTTP 500 no PHP-FPM.

## SFU Hetzner

Não rode MediaSoup no PC1 nesta spec. Configure o Laravel para falar com o Hetzner por Tailscale ou endpoint público:

```dotenv
SFU_HTTP_URL=http://<tailscale-hetzner>:3080
SFU_WS_URL=wss://sfu.<dominio>
SFU_API_SECRET=<mesmo secret do Hetzner>
SFU_JWT_SECRET=<mesmo secret do Hetzner>
```

`SFU_ANNOUNCED_IP` pertence ao servidor SFU no Hetzner, não ao PC1.

## Smoke Tests

Depois do deploy e tunnel:

```bash
APP_URL=https://app.<dominio> WS_URL=https://ws.<dominio> ./scripts/smoke-test.sh
docker compose logs -f app queue reverb nginx
```

Checklist manual:

- `curl -I https://app.<dominio>` retorna HTTP 200/302.
- Login médico/paciente funciona.
- Upload cria objeto no bucket `telemedicina-private`.
- Worker consome jobs Redis.
- Reverb conecta via `ws.<dominio>`.
- Página de vídeo usa SFU Hetzner.

## Backup

Faça backup correlacionado de PostgreSQL e MinIO no mesmo ponto lógico de tempo:

```bash
docker compose exec -T postgres pg_dump -U "$DB_USERNAME" "$DB_DATABASE" > backup-postgres.sql
docker compose exec -T minio mc mirror /data /backup/minio
```

O segundo comando é apenas referência operacional; ajuste destino, criptografia e retenção no host antes de automatizar.
