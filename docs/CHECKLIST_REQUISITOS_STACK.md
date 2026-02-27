# Checklist: ajustes e configurações para a stack (STATUS + EstruturacaoInicial)

Este documento cruza **docs/STATUS_STACK_DOCKER.md** e **docs/DistributedSystems/EstruturacaoInicial.md** e lista o que falta no projeto para atender aos requisitos da documentação.  
Use como guia para implementar as etapas na ordem desejada.

---

## Resumo rápido

| Área | Status | Prioridade sugerida |
|------|--------|---------------------|
| Sessão Redis | Falta configurar | Alta (rápido) |
| MinIO no .env | Falta documentar `AWS_ENDPOINT` | Alta (rápido) |
| Reverb no Docker | Falta incluir no compose | Alta |
| Nginx + PHP-FPM | Falta serviço e config | Alta (PC2) |
| RabbitMQ | Falta serviço e driver Laravel | Média |
| Estrutura portável (deploy/pc2/) | Falta criar pasta e compose | Média |
| .env.example completo | Falta variáveis (RabbitMQ, SFU, etc.) | Média |
| SFU (WebRTC) | Falta doc + variáveis reservadas | Baixa |

---

## 1. Sessão e cache em Redis (PC2)

**Requisito (EstruturacaoInicial):** PC2 — Redis para cache e **sessões**.  
**Requisito (STATUS):** Laravel com cache e sessão em Redis.

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| `docker-compose.yml` (app) | `SESSION_DRIVER: database` | Trocar para `SESSION_DRIVER: redis` |
| `.env.example` | `SESSION_DRIVER=database`, `CACHE_STORE=database` | Trocar para `SESSION_DRIVER=redis`, `CACHE_STORE=redis` e documentar que no Docker/PC2 usa Redis |
| `config/session.php` | Já usa `env('SESSION_DRIVER', 'database')` | Nenhum; só garantir .env/compose com `redis` |

**Arquivos a alterar:** `docker-compose.yml`, `.env.example`.

---

## 2. MinIO (S3) — documentação e uso só via .env

**Requisito (EstruturacaoInicial):** PC1 — MinIO; Laravel (PC2) acessa via rede interna (ex.: `http://IP_PC1:9000`).  
**Requisito (STATUS):** MinIO via variáveis de ambiente; **sem IP fixo no código**; documentar `AWS_ENDPOINT`.

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| `config/filesystems.php` | Já usa `env('AWS_ENDPOINT')` | Nenhum |
| `.env.example` | Não documenta `AWS_ENDPOINT` | Adicionar `AWS_ENDPOINT=` (e comentário: MinIO no PC1, ex. `http://192.168.1.10:9000`) |
| Código (projeto) | Nenhum IP fixo encontrado em código | Manter; usar sempre .env para endpoint MinIO |

**Arquivos a alterar:** `.env.example`.

---

## 3. Reverb no Docker (PC2)

**Requisito (EstruturacaoInicial):** PC2 — Laravel + Reverb (WebSockets).  
**Requisito (STATUS):** Reverb no Docker Compose (serviço ou processo no app).

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| `docker-compose.yml` | Só `php artisan serve` + migrate | Incluir Reverb: ou serviço separado ou no `command` do app (ex.: `reverb:start` em background) e expor porta (ex.: 8080) |
| `.env.example` | Reverb já documentado | Garantir variáveis no deploy (REVERB_HOST, REVERB_PORT, etc.); opcional `REVERB_SERVER_PORT` |

**Arquivos a alterar:** `docker-compose.yml`, eventualmente `.env.example` do deploy.

---

## 4. Nginx + PHP-FPM (PC2)

**Requisito (EstruturacaoInicial):** PC2 — Nginx como servidor web da aplicação (porta 80/443 local).  
**Requisito (STATUS):** Nginx na frente do PHP-FPM, porta 80.

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| `docker-compose.yml` | App usa `php artisan serve` na porta 8000 | Adicionar serviço **nginx**; alterar app para **PHP-FPM** (sem `artisan serve`); Nginx faz proxy para PHP-FPM (ex.: `fastcgi_pass app:9000`) |
| `Dockerfile` | CMD com `artisan serve` | Ajustar para PHP-FPM (ex.: `php-fpm`) quando usar com Nginx |
| Config Nginx | Não existe | Criar config (ex.: `nginx/default.conf` ou em `deploy/pc2/nginx/`) com server porta 80 e `location ~ \.php$` para FPM |

**Arquivos a criar/alterar:** `docker-compose.yml`, `Dockerfile` (ou Dockerfile específico para FPM), config Nginx (novo arquivo).

---

## 5. RabbitMQ (PC2)

**Requisito (EstruturacaoInicial):** PC2 — RabbitMQ para filas (jobs, mensageria).  
**Requisito (STATUS):** Serviço RabbitMQ no Docker e, se for usar para filas, driver no Laravel.

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| `docker-compose.yml` | Não há serviço RabbitMQ | Adicionar serviço **rabbitmq** (imagem oficial), porta 5672 (AMQP) e opcional 15672 (management); app `depends_on: rabbitmq` se usar |
| `config/queue.php` | Só sync, database, beanstalkd, sqs, redis | Adicionar conexão **rabbitmq** (ex.: pacote `vladimir-yuldashev/laravel-queue-rabbitmq` ou similar) e `QUEUE_CONNECTION=rabbitmq` opcional |
| `.env.example` | Não documenta RabbitMQ | Adicionar `RABBITMQ_HOST=`, `RABBITMQ_PORT=5672`, `RABBITMQ_USER=`, `RABBITMQ_PASSWORD=`, `RABBITMQ_VHOST=/` e comentário de uso |

**Nota:** Hoje o compose já usa `QUEUE_CONNECTION=redis`. RabbitMQ é opcional; a doc pede o serviço no PC2. Se quiser filas em RabbitMQ, além do serviço é preciso driver Laravel.

**Arquivos a alterar:** `docker-compose.yml`, `config/queue.php` (se implementar driver), `.env.example`.

---

## 6. Estrutura portável (deploy no PC2)

**Requisito (EstruturacaoInicial):** No PC2, stack sobe com Docker (Laravel, PostgreSQL, Redis, RabbitMQ, Nginx).  
**Requisito (STATUS):** Pasta de deploy (ex.: `deploy/pc2/` ou `docker/`) com compose e configs; “no PC2 só clonar, copiar .env e rodar `docker compose up -d`”.

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| Raiz do projeto | Apenas `docker-compose.yml` na raiz | Criar pasta **deploy/pc2/** (ou `docker/`) com: `docker-compose.yml` (ou referência ao da raiz), Dockerfile ou referência, configs Nginx/PHP, **.env.example** completo para o PC2 |
| `.env.example` do deploy | — | Incluir todas as variáveis: DB_*, REDIS_*, RABBITMQ_* ou QUEUE_*, AWS_* (MinIO), APP_URL, Reverb, SFU reservadas; sem localhost fixo para serviços externos (MinIO = IP/hostname do PC1 via .env) |

**Arquivos a criar:** `deploy/pc2/docker-compose.yml` (ou symlink/cópia), `deploy/pc2/.env.example`, `deploy/pc2/nginx/` (configs), eventualmente `deploy/pc2/README.md` com instruções.

---

## 7. .env.example completo (raiz e/ou deploy)

**Requisito (STATUS):** Todas as variáveis documentadas: DB_*, REDIS_*, RABBITMQ_* ou QUEUE_*, AWS_*/MinIO, APP_URL, Reverb, SFU reservadas.

| Variável / grupo | No .env.example atual | Ajuste |
|------------------|------------------------|--------|
| DB_* | OK (PostgreSQL) | Manter |
| REDIS_* | OK | Manter; alinhar SESSION_DRIVER e CACHE_STORE com redis quando for deploy Docker |
| QUEUE_CONNECTION | database | Documentar redis (e rabbitmq se implementar) |
| AWS_ENDPOINT | Falta | Adicionar + comentário MinIO (PC1) |
| RABBITMQ_* | Falta | Adicionar (host, port, user, password, vhost) |
| Reverb | Parcial | Garantir REVERB_HOST, REVERB_PORT, REVERB_SCHEME, REVERB_APP_* |
| SFU (reservado) | Falta | Adicionar SFU_URL=, SFU_WS_URL= (comentado como reservado para WebRTC) |

**Arquivos a alterar:** `.env.example` (raiz), e quando existir, `deploy/pc2/.env.example`.

---

## 8. SFU (WebRTC) — documentação e variáveis reservadas

**Requisito (EstruturacaoInicial):** SFU (Janus/Mediasoup) opcional no PC2 ou servidor dedicado; não incluir no compose agora.  
**Requisito (STATUS):** Documentar no README que o SFU será adicionado depois; variáveis reservadas no `.env.example`.

| Onde | Atual | Ajuste necessário |
|------|--------|-------------------|
| README (raiz ou docs) | — | Adicionar breve seção: SFU (WebRTC) será adicionado depois; hoje não está no Docker |
| `.env.example` | — | Adicionar ao final: `# SFU (WebRTC — reservado)`, `SFU_URL=`, `SFU_WS_URL=` |

**Arquivos a alterar:** `README.md` (ou `docs/README_STACK.md`), `.env.example`.

---

## 9. Outros alinhamentos

| Item | Observação |
|------|------------|
| Filas | Compose já usa `QUEUE_CONNECTION=redis`; `.env.example` ainda tem `QUEUE_CONNECTION=database`. Alinhar .env.example para redis quando for deploy Docker (ou documentar as duas opções). |
| STATUS_STACK_DOCKER.md | Atualizar conforme cada item acima for concluído (marcar checkboxes e tabela de resumo). |
| EstruturacaoInicial | Não exige alteração; este checklist garante que o projeto atenda ao que a doc descreve para PC1, PC2 e PC3. |

---

## Ordem sugerida de implementação

1. **Sessão Redis** — docker-compose + .env.example (rápido).  
2. **MinIO no .env** — adicionar `AWS_ENDPOINT` e comentário no .env.example.  
3. **Reverb no Docker** — incluir no compose (e variáveis no .env do deploy).  
4. **Nginx + PHP-FPM** — serviço Nginx, app como FPM, config Nginx.  
5. **RabbitMQ** — serviço no compose + variáveis no .env; driver Laravel opcional.  
6. **Estrutura deploy/pc2/** — pasta, compose e .env.example do PC2.  
7. **.env.example completo** — RabbitMQ, SFU, Reverb e demais variáveis.  
8. **SFU** — README + variáveis reservadas.

Este arquivo pode ser atualizado conforme os itens forem implementados (marcar concluídos e ajustar observações).
