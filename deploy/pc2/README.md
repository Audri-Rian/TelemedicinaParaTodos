# Deploy PC2 — Application Node

Pasta de deploy para o **PC2 (Application Node)** da arquitetura de telemedicina: Laravel, PostgreSQL, Redis, RabbitMQ, Nginx e Reverb.

## Pré-requisitos

- Repositório clonado na máquina (PC2).
- Docker e Docker Compose instalados.
- (Opcional) MinIO no PC1 para armazenamento S3.

## Uso rápido

1. **Entre na pasta de deploy** (a partir da raiz do repositório):

   ```bash
   cd deploy/pc2
   ```

2. **Crie o `.env` a partir do exemplo**:

   ```bash
   cp .env.example .env
   ```

3. **Edite o `.env`** e defina pelo menos:

   - `APP_KEY` — gere com `php artisan key:generate --show` (execute na raiz do repo).
   - `DB_PASSWORD` — senha do PostgreSQL.
   - `RABBITMQ_PASSWORD` — senha do RabbitMQ (ou use o padrão `secret`).
   - `REVERB_APP_KEY` e `REVERB_APP_SECRET` — para WebSocket (podem ser strings aleatórias).
   - `APP_URL` — URL pública do app (ex.: `http://localhost` ou `https://seu-dominio.com`).
   - Se usar MinIO (PC1): `AWS_ENDPOINT`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`.

4. **Suba os serviços**:

   ```bash
   docker compose up -d
   ```

5. Acesse a aplicação em **http://localhost** (porta 80). Reverb (WebSocket) em **http://localhost:8080**.

## Conteúdo desta pasta

| Item | Descrição |
|------|-----------|
| `docker-compose.yml` | Compose do PC2 (app, postgres, redis, rabbitmq, nginx, reverb). Build usa contexto na raiz do repo. |
| `.env.example` | Exemplo de variáveis para deploy (DB, Redis, RabbitMQ, Reverb, MinIO, SFU reservado). |
| `nginx/conf.d/default.conf` | Configuração Nginx (Laravel + PHP-FPM). |

O **Dockerfile** e o código da aplicação ficam na **raiz do repositório**; o compose em `deploy/pc2` referencia `../..` como contexto de build.

## Portas

| Serviço   | Porta  |
|-----------|--------|
| Nginx (app) | 80   |
| Reverb (WebSocket) | 8080 |
| PostgreSQL | 5432 |
| Redis | 6379 |
| RabbitMQ | 5672 (AMQP), 15672 (Console) |

## Variáveis obrigatórias / recomendadas

- **APP_KEY** — obrigatório (Laravel).
- **DB_PASSWORD** — recomendado alterar em produção (padrão no compose: `secret`).
- **RABBITMQ_PASSWORD** — recomendado alterar em produção (padrão: `secret`).
- **APP_URL** — deve refletir a URL em que o app é acessado (ex.: domínio ou IP do PC2).
- **REVERB_HOST** — hostname que o navegador usa para conectar ao WebSocket (ex.: mesmo domínio do app ou `localhost`).

SFU (WebRTC) está reservado no `.env.example` (`SFU_URL`, `SFU_WS_URL`) para uso futuro.
