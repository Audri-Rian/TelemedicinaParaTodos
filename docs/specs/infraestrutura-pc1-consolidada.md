# Feature Spec — Infraestrutura PC1 consolidada (laboratório / homelab)

> Status: `review`
> Autor: Tech Lead Agent · Data: 2026-05-16 · Refinado: 2026-05-16 (respostas do operador)
> Referências: `deploy/pc2/`, `docker-compose.yml`, `docs/minio-dev-setup.md`, repositório `Sistemas-Distriuidos-Telemedicina` (`deploy/pc1/`, `deploy/pc3/`, `deploy/monitoring/`), ADR `docs/adr/0001-pades-icp-brasil-assinatura-digital.md`

---

## Objetivo

Consolidar **toda a infraestrutura local** do projeto **Telemedicina Para Todos** em um único host (**PC1**, acesso `ssh pc1`), mantendo **apenas o SFU MediaSoup no Hetzner** inalterado, com exposição pública via **Cloudflare Tunnel** e stack completo: Laravel, PostgreSQL, Redis, RabbitMQ, MinIO, Nginx, Reverb e workers de fila.

## Motivação

| Fator                                                                      | Benefício                                                                                                       |
| -------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Arquitetura distribuída atual (PC1 storage + PC3 app/edge + notebook LGTM) | Complexidade operacional, múltiplos `.env`, dependência de rede LAN entre nós                                   |
| Consolidação em PC1                                                        | Um único `docker compose`, um ponto de backup, deploy e troubleshooting simplificados                           |
| Laboratório / custo                                                        | Adequado para homelab e validação de features sem cluster caseiro                                               |
| Hetzner isolado                                                            | SFU WebRTC permanece com IP público estável e `SFU_ANNOUNCED_IP` já documentado — não misturar com carga do PC1 |

---

## Regras de negócio (infra)

1. **Hetzner (SFU MediaSoup) não é alterado** nesta spec — apenas conectividade PC1↔Hetzner (VPN/Tailscale) e variáveis Laravel `SFU_*` / `config/services.php` apontando para o endpoint remoto.
2. **Dados clínicos e documentos assinados (PAdES)** residem em PostgreSQL + MinIO no PC1 — exigem backup, controle de acesso e alinhamento LGPD (retenção, minimização).
3. **Exposição à internet** somente via **Cloudflare Tunnel** (sem port forwarding 80/443 no roteador residencial).
4. **MinIO** é o backend S3 para discos `s3_private` e `s3_public` (`config/filesystems.php`) — credencial de aplicação (`MINIO_APP_USER`), não root.
5. **Filas**: Redis para fila padrão (`QUEUE_CONNECTION=redis`); RabbitMQ para integrações e jobs críticos (`INTEGRATION_QUEUE_CONNECTION=rabbitmq`, exportações, assinatura PAdES conforme ADR-0001).
6. **Workers obrigatórios em produção**: `queue:work` (e opcionalmente fila dedicada RabbitMQ), `reverb:start`, `schedule:run` via cron no host ou container scheduler.
7. **`deploy/pc2/` permanece** como referência histórica / nó app isolado até migração documentada; **fonte da verdade de produção homelab passa a ser `deploy/pc1/`**.

---

## Arquitetura alvo

### Diagrama textual

```
[Usuário Web/Mobile]
        │
        ▼
[Cloudflare DNS + WAF]
        │
        ▼ (outbound tunnel)
[cloudflared @ PC1 host — systemd]
  túnel existente: pc3-telemedicina (ID 6499541c-…)
        │
        ├── app.<domínio>  ──► http://127.0.0.1:80  ──► [nginx] ──► PHP-FPM (app)
        │
        └── ws.<domínio>   ──► http://127.0.0.1:80  ──► [nginx server ws] ──► [reverb:8080]

[PC1 Docker network]
  postgres · redis · rabbitmq · minio · minio-init
  app (php-fpm) · reverb · queue-worker(s) · nginx

[MinIO :9000] ◄── Laravel AWS_ENDPOINT=http://minio:9000 (rede Docker)
  buckets: telemedicina-private | telemedicina-public

[PC1] ── Tailscale (ativo) ──► [Hetzner SFU]
  SFU_HTTP_URL / SFU_WS_URL via IP Tailscale do Hetzner (preferencial)
  Browser ICE via SFU_ANNOUNCED_IP no Hetzner (inalterado)
```

### Padrões reutilizados

| Origem                | Artefato                               | Uso em PC1                                           |
| --------------------- | -------------------------------------- | ---------------------------------------------------- |
| TelemedicinaParaTodos | `deploy/pc2/docker-compose.yml`        | Base: postgres, redis, rabbitmq, app, reverb, nginx  |
| TelemedicinaParaTodos | `docker-compose.yml` (raiz)            | MinIO + `minio-init` (buckets/policies idempotentes) |
| TelemedicinaParaTodos | `deploy/pc2/nginx/conf.d/default.conf` | Proxy Laravel; **estender** com upstream Reverb      |
| TelemedicinaParaTodos | `config/filesystems.php`               | Discos `s3`, `s3_private`, `s3_public`               |
| TelemedicinaParaTodos | `.env.example`                         | Matriz de variáveis produção                         |
| Sistemas-Distriuidos  | `deploy/pc1/`                          | MinIO + observabilidade leve (exporters, promtail)   |
| Sistemas-Distriuidos  | `docs/Tutorial-Servidor3-PC3.md`       | **cloudflared** como serviço host (não container)    |
| Sistemas-Distriuidos  | `deploy/monitoring/`                   | LGTM opcional (overlay)                              |
| Sistemas-Distriuidos  | `docs/VPN-MediaSoup-Hetzner.md`        | Conectividade PC1↔Hetzner                           |

### Ausente hoje (criar em `deploy/pc1/`)

- Pasta `deploy/pc1/` no repositório TelemedicinaParaTodos
- Serviço **queue-worker** no compose de produção (`deploy/pc2` também não define — gap conhecido)
- Proxy Nginx WebSocket para Reverb (Reverb exposto na 8080 direto quebra CSP/tunnel unificado)
- Documentação Cloudflare Tunnel específica do projeto (hoje em `docs/DistributedSystems/EstruturaInicial.md` cita PC2)
- Scheduler (`php artisan schedule:work` ou cron)

---

## Mapa de serviços no PC1

| Serviço                 | Container                          | Porta host       | Porta interna | Volume                      | Observação                                    |
| ----------------------- | ---------------------------------- | ---------------- | ------------- | --------------------------- | --------------------------------------------- |
| PostgreSQL              | `telemedicina_postgres`            | `5432`¹          | 5432          | `postgres_data`             | Produção: não publicar host se só Docker      |
| Redis                   | `telemedicina_redis`               | `6379`¹          | 6379          | `redis_data`                | Cache + sessão + fila default                 |
| RabbitMQ                | `telemedicina_rabbitmq`            | `5672`, `15672`¹ | 5672, 15672   | `rabbitmq_data`             | Management UI só LAN/VPN                      |
| MinIO API               | `telemedicina_minio`               | `9000`¹          | 9000          | `minio_data`                | Laravel: `http://minio:9000`                  |
| MinIO Console           | idem                               | `9001`¹          | 9001          | —                           | Admin; restringir UFW                         |
| minio-init              | `telemedicina_minio_init`          | —                | —             | —                           | One-shot; buckets + app user                  |
| Laravel (PHP-FPM)       | `telemedicina_app`                 | —                | 9000          | bind repo ou imagem         | `migrate --force` no start (avaliar job init) |
| Reverb                  | `telemedicina_reverb`              | —²               | 8080          | bind repo                   | Preferir só rede Docker + Nginx               |
| Queue worker            | `telemedicina_queue`               | —                | —             | bind repo                   | `queue:work --sleep=3 --tries=3`              |
| Queue worker (RabbitMQ) | `telemedicina_queue_rabbit` (opc.) | —                | —             | —                           | `--queue=integrations,default` se separar     |
| Nginx                   | `telemedicina_nginx`               | `80`             | 80            | conf em `deploy/pc1/nginx/` | Entrada do cloudflared                        |
| cloudflared             | **host systemd**                   | —                | —             | `/etc/cloudflared/`         | Não expor 80/443 no roteador                  |

¹ Portas no host **opcionais em produção** — publicar apenas se admin via LAN; caso contrário manter só `expose` na rede Docker.

² Reverb **não** expõe `8080` na WAN — subdomínio `ws.*` termina no Nginx (:80) e faz proxy para `reverb:8080` na rede Docker.

### Perfil de recursos (RAM)

**Decisão operador:** hardware básico, ambiente **apenas demonstração** — não bloquear implementação por limites de RAM. Subir stack completa; se houver OOM, reduzir workers ou desligar RabbitMQ temporariamente.

| Overlay                        | Escolha                                                                          |
| ------------------------------ | -------------------------------------------------------------------------------- |
| LGTM (Prometheus/Grafana/Loki) | **Não** — fora de escopo                                                         |
| Observabilidade                | **Leve** — node-exporter + cAdvisor (+ promtail opcional, sem Loki remoto na v1) |

---

## Estrutura de pastas proposta (`deploy/pc1/`)

```
deploy/pc1/
├── README.md                 # Runbook PC1 (ssh pc1, ordem de subida, smoke tests)
├── .env.example              # Variáveis consolidadas (app + infra + tunnel + SFU remoto)
├── docker-compose.yml        # Stack principal (infra + app + edge interno)
├── docker-compose.observability.yml   # Overlay opcional (de Sistemas-Distriuidos pc1)
├── docker-compose.lgtm.yml            # Overlay opcional (de deploy/monitoring/) — pesado
├── nginx/
│   └── conf.d/
│       ├── default.conf      # server app.<domínio> — Laravel + fastcgi
│       └── reverb.conf       # server ws.<domínio> — proxy WebSocket → reverb:8080
├── cloudflared/
│   └── config.yml.example    # Referência ingress (instalação real no host)
├── promtail/                 # Se overlay observabilidade
│   └── promtail-config.yml
└── scripts/                  # Opcional: smoke-test.sh, backup-minio.sh (fora do escopo código)
```

### Migração de artefatos

| De                                                        | Para                                                      | Ação                                                   |
| --------------------------------------------------------- | --------------------------------------------------------- | ------------------------------------------------------ |
| `deploy/pc2/docker-compose.yml`                           | `deploy/pc1/docker-compose.yml`                           | Mesclar + adicionar minio, minio-init, queue-worker(s) |
| `docker-compose.yml` (minio-init)                         | `deploy/pc1/`                                             | Copiar serviços MinIO verbatim                         |
| `Sistemas-Distriuidos/deploy/pc1/`                        | `deploy/pc1/docker-compose.observability.yml`             | Adaptar Promtail → Loki local ou desabilitar           |
| `Sistemas-Distriuidos/deploy/pc3/` nginx/cloudflared docs | `deploy/pc1/README.md` + `cloudflared/config.yml.example` | Documentar ingress                                     |
| `deploy/pc2/nginx/conf.d/default.conf`                    | `deploy/pc1/nginx/`                                       | Estender WS                                            |

**Não remover `deploy/pc2/`** na primeira entrega — marcar README como deprecated após validação PC1.

---

## Docker Compose — estratégia

### Abordagem recomendada: compose base + overlays

| Arquivo                            | Conteúdo                                                                                              |
| ---------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `docker-compose.yml`               | Serviços obrigatórios: postgres, redis, rabbitmq, minio, minio-init, app, reverb, nginx, queue-worker |
| `docker-compose.observability.yml` | node-exporter, cAdvisor, promtail (métricas host/container)                                           |
| `docker-compose.lgtm.yml`          | **Não usar** neste projeto (operador optou por exporters leves)                                       |

Comando típico:

```bash
cd deploy/pc1 && cp .env.example .env
# Editar .env (APP_KEY, senhas, domínios, SFU Hetzner)
docker compose up -d
# Com observabilidade leve:
docker compose -f docker-compose.yml -f docker-compose.observability.yml up -d
```

### Rede Docker

- Rede bridge única `telemedicina` — todos os serviços na mesma rede.
- **MinIO**: hostname `minio` — `AWS_ENDPOINT=http://minio:9000` no container `app` e workers.
- **Sem dependência de IP LAN** para storage após consolidação (diferente do cenário PC2→PC1 remoto em `deploy/pc2/.env.example`).

### Build e código

- **Decisão:** runtime **100% Docker** (mesmo padrão `deploy/pc2`) — cloudflared é a única exceção (systemd no host).
- `build.context: ../..` (raiz do repo) + `Dockerfile` na raiz — igual `deploy/pc2`.
- Volume bind do código: aceitável em **demonstração/homelab**; imagem versionada sem bind fica para fase posterior.

### Serviços a adicionar (gap vs `deploy/pc2`)

| Serviço                   | Comando                                                                       | Dependências                 |
| ------------------------- | ----------------------------------------------------------------------------- | ---------------------------- |
| `queue`                   | `php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600`            | postgres, redis, app healthy |
| `queue-rabbit` (opcional) | `php artisan queue:work rabbitmq --queue=integrations,medical-records`        | rabbitmq healthy             |
| `scheduler`               | cron no host: `* * * * * docker compose exec -T app php artisan schedule:run` | app                          |

### Init e migrations

- **Problema**: `migrate --force` no `command` do `app` a cada restart pode ser lento.
- **Recomendado**: job one-shot `app-init` ou documentar `docker compose run --rm app php artisan migrate --force` na fase de deploy; manter comportamento atual apenas se aceito pelo operador.

---

## Cloudflare Tunnel

### Túnel existente (migrar PC3 → PC1)

| Campo              | Valor atual                                           | Ação                                                                                      |
| ------------------ | ----------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| Nome               | `pc3-telemedicina`                                    | Manter nome por ora; renomear no painel para `pc1-telemedicina` quando estável (opcional) |
| ID                 | `6499541c-ddd9-4208-b7a7-efea91448bcf`                | Reutilizar o mesmo túnel                                                                  |
| Connector ativo    | `pc3inteli3` (`07531df1-7a91-4e6d-8cba-fbf4768d015c`) | **Desligar** após PC1 conectar                                                            |
| Versão cloudflared | `2026.3.0` (desatualizado no PC3)                     | Instalar release atual no PC1                                                             |

### Decisão: cloudflared no **host** do PC1 (systemd)

Alinhado a `Sistemas-Distriuidos-Telemedicina/docs/Tutorial-Servidor3-PC3.md`:

- Tunnel **outbound** — UFW no PC1 **não** precisa liberar 80/443 da internet.
- Entrega tráfego em `http://127.0.0.1:80` (Nginx container com `ports: "80:80"`).

### Migração do connector (runbook)

1. No **PC1** (`ssh pc1`): instalar `cloudflared` e registrar connector com o **mesmo token** do túnel (Zero Trust → Tunnels → `pc3-telemedicina` → Install connector / token).
2. Validar no painel: novo connector PC1 **Conectado** e app responde em LAN `:80`.
3. No **PC3**: `sudo systemctl stop cloudflared && sudo systemctl disable cloudflared` (evitar dois connectors competindo).
4. Confirmar Public Hostnames no painel apontam para `http://127.0.0.1:80` (não alterar DNS se hostnames já existem).
5. Smoke test HTTPS nos hostnames públicos.

> **Nota:** Enquanto PC3 e PC1 estiverem conectados ao mesmo túnel, a Cloudflare pode balancear entre connectors — migrar rápido e desligar PC3.

### Ingress rules (Public Hostnames no painel)

Substituir `seudominio.com` pelo domínio real já configurado na zona Cloudflare:

| Hostname público | Service (painel CF)   | Nginx no PC1                                          |
| ---------------- | --------------------- | ----------------------------------------------------- |
| `app.<domínio>`  | `http://127.0.0.1:80` | `server_name app.<domínio>` → Laravel                 |
| `ws.<domínio>`   | `http://127.0.0.1:80` | `server_name ws.<domínio>` → proxy WS → `reverb:8080` |

Arquivo de referência local: `deploy/pc1/cloudflared/config.yml.example` (espelha ingress; **não** commitar token/credentials).

Exemplo `config.yml` (se usar modo manual em vez de token):

```yaml
tunnel: 6499541c-ddd9-4208-b7a7-efea91448bcf
credentials-file: /etc/cloudflared/credentials.json

ingress:
    - hostname: app.<domínio>
      service: http://127.0.0.1:80
    - hostname: ws.<domínio>
      service: http://127.0.0.1:80
    - service: http_status:404
```

### Variáveis Laravel impactadas

| Variável           | Valor PC1                                               |
| ------------------ | ------------------------------------------------------- |
| `APP_URL`          | `https://app.<domínio>`                                 |
| `REVERB_HOST`      | `ws.<domínio>` (subdomínio dedicado — decisão operador) |
| `REVERB_SCHEME`    | `https`                                                 |
| `REVERB_PORT`      | `443`                                                   |
| `VITE_REVERB_HOST` | igual `REVERB_HOST` (build frontend)                    |
| `ASSET_URL`        | igual `APP_URL` se necessário                           |

### Headers e segurança

- Confiar em headers Cloudflare (`CF-Connecting-IP`) — configurar `TrustProxies` no Laravel (implementação futura).
- Rate limit básico no Nginx (`limit_req_zone`) — ver seção Nginx.

---

## MinIO

### Buckets e políticas (via `minio-init`)

| Bucket                 | Disco Laravel | Política                                                          |
| ---------------------- | ------------- | ----------------------------------------------------------------- |
| `telemedicina-private` | `s3_private`  | Privado — prontuários, PDFs assinados, documentos LGPD            |
| `telemedicina-public`  | `s3_public`   | Download anônimo apenas onde aplicável (avatars, assets públicos) |

Bootstrap idempotente — copiar de `docker-compose.yml` / `docs/minio-dev-setup.md`.

### Integração Laravel

| Variável                      | Valor consolidado PC1             |
| ----------------------------- | --------------------------------- |
| `AWS_ENDPOINT`                | `http://minio:9000`               |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true`                            |
| `AWS_ACCESS_KEY_ID`           | `MINIO_APP_USER` (não root)       |
| `AWS_SECRET_ACCESS_KEY`       | `MINIO_APP_PASSWORD`              |
| `AWS_PRIVATE_BUCKET`          | `telemedicina-private`            |
| `AWS_PUBLIC_BUCKET`           | `telemedicina-public`             |
| `AWS_DEFAULT_REGION`          | `us-east-1` (compatibilidade SDK) |
| `FILESYSTEM_DISK`             | `s3_private` (default sensível)   |

### Backup

- Volume `minio_data` — snapshot filesystem ou `mc mirror` agendado no host.
- Documentos PAdES e prontuário: **backup MinIO + PostgreSQL correlacionado** (mesmo RPO).

### Console

- Porta `9001` — acesso apenas LAN ou SSH tunnel; **não** publicar via Cloudflare sem autenticação forte.

---

## Nginx

### Responsabilidades

1. Reverse proxy PHP-FPM → container `app:9000` (config existente `deploy/pc2/nginx/conf.d/default.conf`).
2. **Proxy WebSocket** Reverb — evitar exposição direta da porta 8080 na internet.
3. Headers de segurança básicos (`X-Frame-Options`, `X-Content-Type-Options`) — alinhar com middleware `SecurityHeaders` (ver `feature-interoperability-pendencias.md` para CSP/Reverb).
4. Rate limit básico em rotas de login/API (ex.: `limit_req_zone` 10r/s burst 20).

### WebSocket Reverb — subdomínio `ws.<domínio>`

- **Server block separado** (`reverb.conf`): `server_name ws.<domínio>`; `proxy_pass http://reverb:8080` com headers `Upgrade` / `Connection "upgrade"`.
- Laravel Echo usa `REVERB_HOST=ws.<domínio>`, `REVERB_SCHEME=https`, `REVERB_PORT=443` — origem distinta do app (CORS/Reverb: validar `allowed_origins` se necessário).
- Public Hostname Cloudflare para `ws.<domínio>` → `http://127.0.0.1:80` (mesmo Nginx, roteamento por `server_name`).

### TLS

- Terminação TLS na **Cloudflare** (modo Full ou Full Strict).
- Entre cloudflared e Nginx: HTTP localhost (aceitável em homelab).

---

## Rede e conectividade

### PC1 — LAN

| Item         | Recomendação                                                                                                                                                              |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| IP fixo      | **Obrigatório** — aplicar Netplan (`Sistemas-Distriuidos/deploy/pc1/netplan-wifi.yaml.example`); confirmar IP com `hostname -I` no PC1 (doc histórica usa `192.168.1.21`) |
| Hostname SSH | `ssh pc1` no `~/.ssh/config` do operador                                                                                                                                  |
| UFW          | Default deny incoming; allow SSH (22) de rede admin; allow 80 **apenas localhost**; liberar 9000/9001/15672 só de sub-rede admin se necessário                            |

### PC1 ↔ Hetzner (SFU)

- **Tailscale já ativo** — usar IPs MagicDNS ou `100.x.x.x` do Hetzner para tráfego app→SFU (latência e firewall simplificados).
- Laravel no PC1:

| Variável                            | Origem                                                                                  |
| ----------------------------------- | --------------------------------------------------------------------------------------- |
| `SFU_HTTP_URL`                      | `http://<tailscale-ip-hetzner>:3080` (ou hostname Tailscale do SFU)                     |
| `SFU_WS_URL`                        | `ws://<tailscale-ip-hetzner>:4443` ou `wss://sfu.<domínio>` se Nginx público no Hetzner |
| `SFU_API_SECRET` / `SFU_JWT_SECRET` | Sincronizados com `mediasoup-server` no Hetzner — **inalterados**                       |
| `SFU_ANNOUNCED_IP`                  | **IP público Hetzner** — só no servidor SFU, não no PC1                                 |

- **Não** mover MediaSoup para PC1 nesta spec.
- Browser do paciente/médico continua usando domínio público do SFU (`wss://sfu.<domínio>`) para WebRTC; PC1 só chama API de signaling conforme `.env`.

### DNS

- Domínio na Cloudflare — registros CNAME para tunnel; sem A record para IP residencial.

---

## Observabilidade

### Decisão: **exporters leves** (opção B)

| Componente              | Incluir     | Notas                                                    |
| ----------------------- | ----------- | -------------------------------------------------------- |
| node-exporter           | Sim         | Métricas host `:9100` — scrape futuro ou consulta manual |
| cAdvisor                | Sim         | Métricas containers `:8080`                              |
| promtail                | Opcional v1 | Sem Loki no PC1 — logs via `docker compose logs`         |
| Prometheus/Grafana/Loki | **Não**     | LGTM omitido                                             |

Overlay: `docker-compose.observability.yml` (adaptar de `Sistemas-Distriuidos/deploy/pc1/`).

### Logs aplicação

- Laravel `LOG_CHANNEL=stack` → stdout Docker (`docker compose logs -f app queue`).

---

## Matriz de variáveis `.env` — produção PC1

Arquivo único: `deploy/pc1/.env` (copiar de `.env.example`).

| Grupo      | Variáveis chave                                                           | Valor / notas                   |
| ---------- | ------------------------------------------------------------------------- | ------------------------------- |
| App        | `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL=https://...` | Obrigatório                     |
| DB         | `DB_*` → host `postgres`                                                  | Senha forte                     |
| Redis      | `REDIS_HOST=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`           | —                               |
| Filas      | `QUEUE_CONNECTION=redis`, `INTEGRATION_QUEUE_CONNECTION=rabbitmq`         | Worker rabbit separado se usado |
| RabbitMQ   | `RABBITMQ_*` → host `rabbitmq`                                            | Senha ≠ default `secret`        |
| Reverb     | `BROADCAST_CONNECTION=reverb`, `REVERB_*`                                 | Scheme https, host público      |
| MinIO      | `AWS_*`, `MINIO_*`                                                        | Endpoint Docker interno         |
| SFU        | `SFU_HTTP_URL`, `SFU_WS_URL`, secrets                                     | Apontam Hetzner                 |
| Mail       | `MAIL_*`                                                                  | Produção: SMTP real             |
| Assinatura | vars PAdES / certificado                                                  | ADR-0001 — storage `s3_private` |
| Opcional   | `TELESCOPE_ENABLED=false`, `DEBUGBAR` off                                 | Homelab produção                |

**Não commitar** `.env` com secrets. Rotacionar credenciais migradas de tutoriais antigos (`Sistemas-Distriuidos/docs/Publicacao-GitHub.md`).

---

## Segurança

| Área              | Medida                                                                                          |
| ----------------- | ----------------------------------------------------------------------------------------------- |
| Exposição         | Apenas Cloudflare Tunnel; sem port forward WAN                                                  |
| Dados médicos     | MinIO private bucket; backups criptografados em repouso (disciplina operacional)                |
| LGPD              | Dados em storage local — mapear base legal, retenção, direito de exclusão (jobs + MinIO delete) |
| MinIO             | Usuário app com policy mínima; root só bootstrap                                                |
| RabbitMQ/Postgres | Sem portas públicas WAN; senhas fortes                                                          |
| SFU               | Segredos JWT/API apenas em `.env` e Hetzner                                                     |
| Rotação           | Token cloudflared e senhas DB em incidente                                                      |

---

## Edge Cases

| Cenário                 | Comportamento esperado                                                                                                                        |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| PC1 offline             | App indisponível; tunnel Cloudflare retorna 5xx; vídeo SFU pode continuar se apenas signaling via Hetzner e sessão já estabelecida (limitado) |
| Tunnel down             | Mesmo que offline externo; LAN pode acessar `http://192.168.x.x` se UFW permitir                                                              |
| MinIO indisponível      | Upload/download falha; jobs de PDF/assinatura entram em retry/failed; monitorar fila                                                          |
| PostgreSQL cheio        | Migrate/ writes falham; alerta disco                                                                                                          |
| Hetzner inacessível     | Videochamada não inicia; app deve degradar com mensagem (já parcial em `VideoCall.vue`)                                                       |
| Worker morto            | Jobs acumulam em Redis/RabbitMQ; notificações e PAdES atrasam — healthcheck + restart policy `always`                                         |
| RAM insuficiente        | OOM killer mata containers — usar perfil mínimo e limits                                                                                      |
| Backup restaurado só DB | Inconsistência com objetos MinIO — restaurar ambos no mesmo ponto no tempo                                                                    |

---

## Riscos técnicos

| Risco                                      | Prob. | Impacto | Mitigação                                              |
| ------------------------------------------ | ----- | ------- | ------------------------------------------------------ |
| RAM insuficiente em demo                   | Média | Médio   | Aceito pelo operador; reiniciar containers se OOM      |
| Single point of failure (PC1)              | Alta  | Alto    | Backups automatizados; documentar RTO; aceitar homelab |
| Sem queue-worker no compose atual          | Alta  | Alto    | Adicionar serviço na implementação                     |
| WS Reverb atrás tunnel mal configurado     | Média | Alto    | Nginx WS + teste Echo; subdomínio dedicado             |
| LGPD dados sensíveis em disco caseiro      | Média | Alto    | Criptografia disco, acesso físico, política retenção   |
| Latência PC1→Hetzner VPN                   | Média | Médio   | Monitorar; SFU media path direto browser↔Hetzner      |
| `migrate --force` em todo restart          | Baixa | Médio   | Separar init job                                       |
| Credenciais default (`secret`, minioadmin) | Média | Alto    | Checklist deploy; gerador senhas                       |

---

## Plano de implementação

Ordenado por dependência — **sem código nesta spec**; cada fase é entregável operacional.

| Fase | Atividade                                                                                     | Critério de aceite                   |
| ---- | --------------------------------------------------------------------------------------------- | ------------------------------------ |
| 0    | IP fixo Netplan; `ssh pc1`; confirmar `hostname -I`                                           | Host acessível                       |
| 1    | Instalar Docker + Compose; clonar repo; criar `deploy/pc1/` conforme estrutura                | `docker compose config` válido       |
| 2    | Subir infra: postgres, redis, rabbitmq, minio + minio-init                                    | Buckets existem; `mc ls` ok          |
| 3    | Configurar `.env` (APP_KEY, senhas, AWS, SFU Hetzner)                                         | `php artisan config:show` coerente   |
| 4    | Subir app, nginx, reverb, queue-worker(s)                                                     | Home responde em LAN :80             |
| 5    | `migrate --seed` (se homelab) ou migrate produção                                             | Tabelas ok                           |
| 6    | Build frontend (`npm run build`) dentro da imagem ou CI                                       | Assets servidos                      |
| 7    | Migrar cloudflared: instalar no PC1 (túnel `6499541c-…`); desligar no PC3; hostnames app + ws | HTTPS app + ws ok                    |
| 8    | Validar Reverb em `https://ws.<domínio>`                                                      | Evento broadcast recebido no browser |
| 9    | Smoke: login, upload S3, job fila, consulta vídeo SFU Hetzner                                 | Checklist manual                     |
| 10   | Backup postgres + minio; documentar restore                                                   | Restore testado 1×                   |
| 11   | Overlay observabilidade leve (node-exporter, cAdvisor)                                        | `:9100` / `:8080` respondem na LAN   |
| 12   | Deprecar docs PC2/PC3 distribuídos; atualizar `docs/DistributedSystems/EstruturaInicial.md`   | Doc única PC1                        |

### Smoke test (checklist operador)

- [ ] `curl -I https://app.<domínio>` → 200
- [ ] `curl -I https://ws.<domínio>` → 101/upgrade ou resposta Nginx WS
- [ ] Login médico/paciente
- [ ] Upload arquivo → bucket private
- [ ] Disparar job (notificação ou PDF) → worker consome
- [ ] Conexão Reverb (notificação tempo real)
- [ ] Página vídeo / SFU test — ICE conecta Hetzner
- [ ] `docker compose ps` todos healthy

---

## Decisões validadas (operador — 2026-05-16)

| #   | Pergunta          | Decisão                                                                                                            |
| --- | ----------------- | ------------------------------------------------------------------------------------------------------------------ |
| 1   | Hardware          | Básico, **só demonstração** — não bloquear por RAM                                                                 |
| 2   | Cloudflare Tunnel | **Existe** — nome `pc3-telemedicina`, ID `6499541c-ddd9-4208-b7a7-efea91448bcf`; migrar connector de PC3 → **PC1** |
| 3   | Observabilidade   | **Exporters leves** (node-exporter, cAdvisor); sem LGTM                                                            |
| 4   | Runtime           | **100% Docker** (cloudflared no host) — decidido pelo tech lead                                                    |
| 5   | IP LAN            | **IP fixo** via Netplan; confirmar valor no PC1 (`hostname -I`)                                                    |
| 6   | Hetzner           | **Tailscale ativo** — `SFU_*` via rede Tailscale                                                                   |
| 7   | Reverb            | **Subdomínio** `ws.<domínio>` (ingress + Nginx `server_name` dedicado)                                             |

### Pendente (não bloqueia implementação)

- Domínio exato (`app.*` / `ws.*`) — preencher no `.env` e no painel Cloudflare (hostnames já podem existir apontando ao túnel).
- Renomear túnel `pc3-telemedicina` → `pc1-telemedicina` no painel (cosmético).

---

## Checklist (implementação futura)

### Infra `deploy/pc1/`

- [ ] `docker-compose.yml` unificado (pc2 + minio + workers)
- [ ] `.env.example` completo (SFU, MinIO, Reverb, tunnel)
- [ ] Nginx com proxy WebSocket Reverb
- [ ] README runbook PC1
- [ ] `cloudflared/config.yml.example`
- [ ] Overlay observabilidade opcional
- [ ] Limits de memória por serviço (perfil 4 GB)
- [ ] Script smoke test documentado

### Operação

- [ ] cloudflared systemd no host
- [ ] UFW configurado
- [ ] Backup DB + MinIO agendado
- [ ] Cron scheduler
- [ ] Senhas não-default
- [ ] Teste restore

### Documentação

- [ ] Atualizar `docs/DistributedSystems/EstruturaInicial.md` (PC1 único)
- [ ] Referenciar esta spec no README deploy

---

## Fora de escopo

- Alteração do MediaSoup / SFU no Hetzner
- CI/CD cloud (GitHub Actions deploy remoto)
- Kubernetes / Swarm
- S3 externo (AWS real) — homelab usa MinIO local
- Implementação de código Laravel/PHP/Vue (apenas infra)
