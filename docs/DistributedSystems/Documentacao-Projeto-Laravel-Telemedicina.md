# Documentação direcional — Projeto Laravel Telemedicina

Este arquivo é a documentação de **contexto e direção** do sistema de telemedicina. Ele deve ficar no repositório do projeto Laravel (ex.: `docs/ARQUITETURA-E-CONTEXTO.md`). A documentação operacional completa (tutoriais de servidor, deploy, observabilidade) está no repositório de infraestrutura: **Sistemas-Distriuidos-Telemedicina**.

---

## 1. O que é este sistema

- **Produto:** plataforma de telemedicina (videochamadas, mensagens em tempo real, armazenamento de exames/imagens/documentos).
- **Stack principal:** Laravel, Laravel Reverb (WebSockets), WebRTC (SFU), PostgreSQL, Redis, RabbitMQ, MinIO (S3-compatível).
- **Ambiente:** simulação de arquitetura distribuída em homelab (3 PCs + notebook de monitoramento), espelhando conceitos de AWS (EC2, RDS, S3, ElastiCache, SQS, etc.).

**Documentação completa de infraestrutura (onde este app se encaixa):** repositório *Sistemas-Distriuidos-Telemedicina* — em especial `docs/EstruturaInicial.md` e `docs/Contexto.md`.

---

## 2. Onde este app roda na infraestrutura

| O quê | Onde |
|-------|------|
| **Esta aplicação Laravel** | **PC3** (Application Node) — IP ex.: 192.168.1.22 |
| **Entrada da internet** | **PC2** (Edge) — Cloudflare Tunnel + Nginx; tráfego chega ao PC3 pelo proxy do PC2 |
| **Armazenamento de objetos (S3)** | **PC1** (Storage) — MinIO em 192.168.1.21:9000 |
| **Banco, cache, filas** | **PC3** — PostgreSQL, Redis, RabbitMQ no mesmo host do Laravel |
| **Monitoramento** | **Notebook** — Prometheus, Grafana, Loki, etc. (fora do cluster) |

Nenhum tráfego da internet atinge o PC3 diretamente: **Internet → Cloudflare → Tunnel → PC2 (Nginx) → PC3 (Laravel)**. O Laravel acessa o MinIO no PC1 pela rede interna.

---

## 3. Diagrama de contexto (este app no centro)

```
                    [ Internet — usuário ]
                              │
                              ▼
                    [ Cloudflare — DNS + SSL + Tunnel ]
                              │
                              ▼
                    [ PC2 — Edge: cloudflared + Nginx ]
                              │ proxy :80 → PC3
                              ▼
    ┌─────────────────────────────────────────────────────────┐
    │  PC3 — Application Node (onde este Laravel roda)        │
    │  Laravel · PostgreSQL · Redis · RabbitMQ · Nginx         │
    └─────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
    [ PC1 — MinIO ]   [ Reverb/WebRTC ]   [ Redis/Queue ]
    192.168.1.21:9000  (mesmo host ou      (mesmo host)
                       servidor de mídia)
```

---

## 4. Integrações — uso no app e onde na infra/código

| Serviço | Uso no app | Onde na infra | Onde no código / config |
|---------|------------|----------------|--------------------------|
| **MinIO (S3)** | Exames, imagens, documentos de pacientes | PC1 — 192.168.1.21:9000 | `config/filesystems.php` (disk s3/minio), `.env` (AWS_ENDPOINT, bucket), código de upload/download |
| **PostgreSQL** | Dados da aplicação | PC3 — localhost:5432 (no mesmo host do Laravel) | `.env` (DB_*), migrations, models |
| **Redis** | Cache, sessões, filas (driver redis) | PC3 — localhost:6379 | `.env` (REDIS_*), `config/cache.php`, `config/session.php`, `config/queue.php` |
| **RabbitMQ** | Filas (jobs, mensageria assíncrona) | PC3 — 5672 (AMQP), 15672 (management) | `.env` (RABBITMQ_*), `config/queue.php` (driver rabbitmq), jobs |
| **Reverb** | WebSockets (mensagens em tempo real) | PC3 (ou mesmo host) | `.env` (REVERB_*), `config/reverb.php`, broadcasting |
| **WebRTC (SFU)** | Videochamadas | PC3 leve (1–3 chamadas) ou servidor dedicado | Config de signaling (Reverb/API) e cliente (frontend); SFU (Janus/Mediasoup) em outro container/host se necessário |

---

## 5. Fluxo do usuário (alto nível)

1. Usuário acessa o domínio → DNS (Cloudflare) → tráfego entra pelo **túnel** até o **PC2**.
2. **PC2** (Nginx) faz proxy para o **PC3** (Nginx + Laravel).
3. Laravel serve a aplicação; Reverb mantém WebSockets para tempo real.
4. Upload de exames/arquivos → Laravel envia ao **MinIO (PC1)** pela rede interna.
5. Jobs pesados → **RabbitMQ** (ou Redis); workers rodam no PC3.
6. Videochamada → signaling via app; mídia via WebRTC (SFU no PC3 ou servidor dedicado).

---

## 6. Ambiente e deploy

- **Onde este app é servido em “produção” homelab:** PC3, via Docker (PHP-FPM + Nginx), na rede interna; entrada pública apenas via PC2 (túnel).
- **Guias detalhados de deploy e configuração do PC3:** repositório *Sistemas-Distriuidos-Telemedicina* — `docs/Configurar-PC3.md`, `deploy/pc3/README.md`.
- **Variáveis críticas para o Laravel em produção:** além do `.env` padrão Laravel, garantir: endpoint e credenciais MinIO (PC1), REDIS_HOST, RABBITMQ_*, REVERB_*, e APP_URL compatível com o domínio que chega pelo PC2.

---

## 7. Decisões de contexto (resumo)

- **Laravel no PC3:** concentra aplicação, banco, cache e filas no nó com mais disco e CPU (i3, 4 GB, 500 GB); PC2 só faz borda (túnel + proxy).
- **MinIO no PC1:** armazenamento de objetos separado do nó de aplicação; uso de disco e crescimento de arquivos não competem com o Laravel.
- **Tunnel só no PC2:** única “porta da frente”; PC1 e PC3 não são expostos à internet.
- **Reverb + RabbitMQ:** tempo real (Reverb) e processamento assíncrono (RabbitMQ) permitem mensagens e jobs sem bloquear requisições HTTP.

---

## 8. Referências rápidas (repositório de infraestrutura)

| Documento | Conteúdo |
|-----------|----------|
| `docs/Contexto.md` | Motivação, simulação AWS, Docker, roadmap |
| `docs/EstruturaInicial.md` | Distribuição completa nos 3 PCs, rede, portas, fluxo do túnel |
| `docs/Configurar-PC3.md` | Configuração do Application Node (PC3) |
| `docs/Arquitetura-LGTM-Observabilidade.md` | Métricas, logs, traces (Prometheus, Grafana, Loki, Tempo) |
| `deploy/pc3/README.md` | Serviços e uso dos arquivos de deploy no PC3 |

---

*Documento único de contextualização para o projeto Laravel da telemedicina. Para passos de instalação e comandos de deploy, utilizar sempre a documentação do repositório de infraestrutura.*
