# Status da stack Docker e requisitos do projeto

Este documento compara o **estado atual** do projeto com o **prompt de requisitos** da stack completa (Docker, Laravel, PostgreSQL, Redis, RabbitMQ, Nginx, MinIO, Reverb, estrutura portável).  
Serve para saber **o que já foi feito** e **o que ainda falta fazer** antes de qualquer alteração.

---

## 1. O que já foi feito

### 1.1 Docker Compose (raiz do projeto)

- **Arquivo:** `docker-compose.yml` na raiz.
- **Serviços existentes:**
  - **MySQL 8.0** – banco em uso (variáveis no compose e no `.env` do app).
  - **PostgreSQL 16** – já está no compose (alternativa; não é o padrão do Laravel hoje).
  - **Redis 7** – cache/fila já disponível.
  - **App (Laravel)** – build via `Dockerfile`, PHP 8.3-FPM; sobe com PHP-FPM (porta 9000 interna); **Nginx** na porta **80** faz proxy para o app e serve arquivos estáticos.
- **Volumes:** `mysql_data`, `postgres_data`, `redis_data`.
- **Observação:** Existe pasta `docker/nginx/conf.d/` com a config do Nginx; o compose está na raiz (pasta `deploy/pc2/` ainda não criada).

### 1.2 Banco de dados no Laravel

- **Em uso:** **PostgreSQL** (`.env.example`: `DB_CONNECTION=pgsql`; no compose o app usa `DB_CONNECTION: pgsql` e host `postgres`).
- **MySQL:** Mantido no compose como alternativa; variáveis comentadas no compose e no `.env.example` para quem quiser usar MySQL.
- **Conclusão:** O requisito “banco PostgreSQL com variáveis DB_* no .env” está **atendido**.

### 1.3 Cache e sessão

- **Cache:** `config/cache.php` usa `CACHE_STORE` (env). `.env.example` tem `CACHE_STORE=redis`. No **docker-compose** o app está com `CACHE_DRIVER: redis` e `REDIS_HOST/REDIS_PORT` definidos.
- **Sessão:** `config/session.php` usa `SESSION_DRIVER` (env). `.env.example` e compose têm `SESSION_DRIVER=redis`.
- **Redis:** Configuração completa em `config/database.php` (redis) e variáveis `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` no `.env.example`. No compose: `REDIS_HOST: redis`, `REDIS_PORT: 6379`.
- **Conclusão:** Redis para **cache** e **sessão** está configurado no Docker e no `.env.example`. “cache e sessão em Redis” — 
### 1.4 Filas (queues)

- **Laravel:** `config/queue.php` tem `database`, `redis` e `rabbitmq`. `.env.example` e compose usam `QUEUE_CONNECTION=redis` (padrão); opcional `rabbitmq` para filas críticas.
- **RabbitMQ:** Serviço no compose (imagem `rabbitmq:3-management-alpine`, portas 5672 e 15672). Driver no Laravel via pacote `hamed-jaahngir/laravel12-queue-rabbitmq`; conexão em `config/queue.php` e variáveis `RABBITMQ_*` no `.env.example` e no compose.
- **Conclusão:** Filas com **Redis** (padrão) e **RabbitMQ** (opcional) implementados. Opção B: uso diário em Redis; RabbitMQ disponível para filas críticas ou mensageria futura.

### 1.5 Armazenamento de arquivos (S3 / MinIO)

- **Laravel:** `config/filesystems.php` tem disco `s3` com:
  - `key`, `secret`, `region`, `bucket` e **`endpoint`** via `env('AWS_ENDPOINT')`.
  - Ou seja, MinIO já pode ser usado configurando no `.env`: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, e opcionalmente `AWS_USE_PATH_STYLE_ENDPOINT`.
- **.env.example:** Documenta `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, **`AWS_ENDPOINT`** (obrigatório para MinIO), `AWS_USE_PATH_STYLE_ENDPOINT=true` e comentário sobre MinIO (Storage Node PC1) e uso opcional de `MINIO_ENDPOINT`; instrução de usar só .env (sem IP fixo no código).
- **Conclusão:** MinIO documentado no `.env.example`; código usa apenas `env()` para S3/MinIO (sem IP fixo).

### 1.6 Reverb (WebSockets)

- **Pacote:** `laravel/reverb` no `composer.json`.
- **Config:** `config/reverb.php` e `config/broadcasting.php` com `BROADCAST_CONNECTION` e variáveis `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`.
- **.env.example:** Reverb já documentado (variáveis acima; comentário sobre ativar/desativar).
- **Uso no projeto:** Frontend (Echo) e docs referenciam Reverb para notificações e sinalização.
- **Docker:** Serviço **reverb** no `docker-compose.yml` (imagem do app, `php artisan reverb:start`), porta **8080**; variáveis REVERB_* no compose e no app; `.env.example` documenta Reverb e variáveis de deploy (REVERB_SERVER_HOST/PORT).
- **Conclusão:** Reverb está **no Docker Compose** e documentado no `.env.example` para deploy.

### 1.7 Servidor web (Nginx)

- **Situação:** Serviço **Nginx** no compose (imagem `nginx:alpine`), porta **80**; config em `docker/nginx/conf.d/default.conf` com root em `/var/www/html/public` e `fastcgi_pass app:9000`. O app sobe com PHP-FPM (sem `artisan serve`).
- **Conclusão:** O requisito “Nginx na frente do PHP-FPM, porta 80” está **implementado**.

### 1.8 Estrutura portável e .env.example

- **Pasta de deploy:** Existe **`deploy/pc2/`** com `docker-compose.yml` (build contexto `../..`, Dockerfile na raiz), `nginx/conf.d/default.conf`, `.env.example` completo e `README.md` com instruções.
- **.env.example (deploy):** Inclui DB_*, REDIS_*, RABBITMQ_*, QUEUE_*, AWS_*/MinIO, APP_URL, Reverb (REVERB_*), SFU reservado (SFU_URL, SFU_WS_URL) e Mail/Log; uso de `env_file: .env` no compose.
- **Conclusão:** Estrutura portável implementada; no PC2: clonar repo, `cd deploy/pc2`, copiar `.env.example` para `.env`, preencher e rodar `docker compose up -d`.

### 1.9 SFU (WebRTC)

- **Requisito do prompt:** Não incluir Janus/Mediasoup no compose; apenas comentar/documentar no README e, se fizer sentido, variáveis reservadas (ex.: `SFU_URL`, `SFU_WS_URL`) no `.env.example`.
- **Situação:** Nada disso está feito no repositório.
- **Conclusão:** Falta documentação e variáveis reservadas para SFU.

---

## 2. Resumo: o que já está pronto x o que falta

| Requisito | Status | Observação |
|-----------|--------|------------|
| Docker Compose com Laravel (PHP-FPM) | Feito | App sobe com PHP-FPM; Nginx na porta 80 em frente |
| PostgreSQL no compose | Feito | Serviço existe; padrão do app é PostgreSQL |
| Redis no compose | Feito | Cache e sessão em Redis no compose e .env.example |
| RabbitMQ no compose | Feito | Serviço + driver Laravel; padrão de filas continua Redis |
| Nginx (porta 80, na frente do PHP) | Feito | Nginx no compose; config em `docker/nginx/conf.d/`; app em PHP-FPM |
| Laravel → PostgreSQL (DB_* no .env) | Feito | Padrão: pgsql no compose e .env.example |
| Laravel → Redis (cache + sessão) | Feito | Cache e sessão em Redis no Docker e .env.example |
| Laravel → Filas (Redis ou RabbitMQ) | Feito | Redis padrão; RabbitMQ opcional (driver + compose) |
| Laravel → MinIO (S3 via .env) | Feito | `filesystems.php` + AWS_*; `AWS_ENDPOINT` e MinIO documentados no .env.example |
| Reverb no Docker | Feito | Serviço reverb no compose (porta 8080); variáveis no compose e .env.example |
| Pasta deploy (ex.: deploy/pc2/) | Feito | `deploy/pc2/` com compose, nginx, .env.example e README |
| .env.example completo (DB, Redis, RabbitMQ, MinIO, Reverb, SFU) | Feito | Raiz + deploy/pc2/.env.example com SFU reservado |
| SFU comentado/documentado + variáveis reservadas | Não feito | — |

---

## 3. O que ainda falta fazer (checklist)

- [x] **Banco:** Migração para PostgreSQL concluída: `DB_CONNECTION=pgsql` no compose e no `.env.example`; app depende apenas do serviço `postgres`.
- [x] **Laravel:** Configurar sessão para Redis no Docker e no `.env.example` (`SESSION_DRIVER=redis`).
- [x] **RabbitMQ:** Serviço RabbitMQ no Docker (portas 5672/15672); driver no Laravel (`hamed-jaahngir/laravel12-queue-rabbitmq`); conexão em `config/queue.php`; variáveis no `.env.example` e no compose. Padrão de filas: Redis (Opção B).
- [x] **Nginx:** Adicionar serviço Nginx no compose, configurar para porta 80 e proxy para PHP-FPM; alterar o serviço app para PHP-FPM (sem `artisan serve`).
- [x] **MinIO:** Documentar no `.env.example`: `AWS_ENDPOINT` (e opcionalmente `MINIO_ENDPOINT`/bucket/keys); garantir uso só via .env (sem IP fixo no código).
- [x] **Reverb:** Incluir Reverb no Docker Compose (serviço ou processo no app) e documentar variáveis no `.env.example` do deploy.
- [x] **Estrutura portável:** Criar pasta (ex.: `deploy/pc2/` ou `docker/`) com `docker-compose.yml`, Dockerfile (ou referência), configs Nginx/PHP e `.env.example` completo.
- [x] **.env.example do deploy:** Incluir todas as variáveis: `DB_*`, `REDIS_*`, `RABBITMQ_*` ou `QUEUE_*`, `AWS_*`/MinIO, `APP_URL`, Reverb, e reservas para SFU (`SFU_URL`, `SFU_WS_URL`).
- [ ] **SFU:** Documentar no README que o SFU será adicionado depois; adicionar variáveis reservadas no `.env.example`.

---

## 4. Observações importantes

- **MySQL x PostgreSQL:** O projeto está configurado para **PostgreSQL** como padrão. MySQL permanece no compose como serviço opcional; para usar MySQL, altere `DB_CONNECTION` e variáveis no `.env` e adicione `mysql` em `depends_on` do serviço `app`. Migração de dados existentes de MySQL para PostgreSQL deve ser feita com ferramentas específicas (ex.: pgloader) se necessário.
- **Reverb:** Já está integrado na aplicação (Echo, canais); falta apenas colocá-lo no fluxo Docker e no `.env.example` do deploy.
- **RabbitMQ:** O prompt pede RabbitMQ para “filas/jobs e mensageria”. Serviço e driver implementados (Opção B: Redis padrão; RabbitMQ para filas críticas). Use `QUEUE_CONNECTION=rabbitmq` no `.env` quando quiser ; worker: `php artisan queue:work rabbitmq`.
- **Portabilidade:** O objetivo é “no PC2 só clonar, criar/copiar o .env e rodar `docker compose up -d`”. Isso exige que o compose e as configs estejam em uma pasta dedicada (ex.: `deploy/pc2/`) com `.env.example` completo e sem localhost fixo para serviços externos (ex.: MinIO em `192.168.1.18`).

Este arquivo pode ser atualizado conforme os itens forem implementados.
