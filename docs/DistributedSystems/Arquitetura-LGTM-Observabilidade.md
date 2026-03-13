# Arquitetura LGTM e Observabilidade — Telemedicina Homelab

Este documento descreve a **estrutura da stack LGTM** (Loki, Grafana, Tempo, Mimir) integrada aos **novos serviços de monitoramento** (exporters, Alertmanager, Blackbox) e onde cada componente roda: **notebook** (monitoramento) e **PC1, PC2, PC3**.

---

## O que é a stack LGTM

| Letra | Componente   | Função principal                    | Porta típica |
|-------|--------------|-------------------------------------|--------------|
| **L** | **Loki**     | Agregação e armazenamento de logs   | 3100         |
| **G** | **Grafana**  | Visualização, dashboards, correlação| 3000         |
| **T** | **Tempo**    | Armazenamento de traces distribuídos| 3200, 4317 (OTLP) |
| **M** | **Mimir**    | Métricas (compatível Prometheus, long-term) | 8080 (API), 9090 (Prometheus compat) |

O **Prometheus** continua como *scraper* (coleta ativa de métricas nos PCs). Opcionalmente pode enviar dados para o **Mimir** (remote write) para retenção longa e agregação; o Grafana consulta Prometheus e/ou Mimir.

---

## Visão geral: onde cada coisa roda

```
                    ┌─────────────────────────────────────────────────────────────────┐
                    │  NOTEBOOK — Computador de monitoramento (LGTM central)           │
                    │  • Prometheus (scrape dos PCs)                                    │
                    │  • Grafana (dashboards, datasources)                              │
                    │  • Loki (logs centralizados)                                      │
                    │  • Tempo (traces centralizados)                                   │
                    │  • Mimir (métricas long-term, opcional)                           │
                    │  • OpenTelemetry Collector (opcional: recebe OTLP, envia p/ L/T/M)│
                    └───────────────────────────┬───────────────────────────────────────┘
                                                │ rede 192.168.1.x
        ┌──────────────────────────────────────┼──────────────────────────────────────┐
        │                                      │                                        │
        ▼                                      ▼                                        ▼
┌───────────────────┐               ┌───────────────────┐               ┌───────────────────┐
│  PC1 — Storage    │               │  PC2 — App        │               │  PC3 — Edge       │
│  192.168.1.21     │               │  192.168.1.20     │               │  192.168.1.22     │
├───────────────────┤               ├───────────────────┤               ├───────────────────┤
│ • MinIO           │               │ • Laravel, Nginx   │               │ • Nginx, Tunnel   │
│ • Node Exporter   │               │ • PostgreSQL       │               │ • Node Exporter   │
│ • cAdvisor        │               │ • Redis            │               │ • Promtail (logs) │
│ • Blackbox Export │               │ • Node Exporter   │               │   → Loki (notebook)│
│ • Alertmanager    │               │ • Redis Exporter  │               │                   │
│ • Promtail (logs) │               │ • Postgres Export │               │                   │
│   → Loki (noteb.) │               │ • Promtail → Loki │               │                   │
└───────────────────┘               └───────────────────┘               └───────────────────┘
```

---

## Serviços por host (resumo)

### Notebook (computador de monitoramento)

| Serviço              | Porta(s)      | Descrição |
|----------------------|---------------|-----------|
| Prometheus           | 9090          | Scrape de todos os exporters e jobs (node, cadvisor, redis, postgres, minio, blackbox). |
| Grafana              | 3000          | UI única; datasources: Prometheus, Loki, Tempo, Mimir. |
| Loki                 | 3100          | Recebe logs (Push API ou via Promtail nos PCs). |
| Tempo                | 3200, 4317/4318 | Recebe traces (OTLP). Aplicações podem enviar traces para aqui. |
| Mimir                | 8080, 9090    | Métricas de longa duração; Prometheus pode fazer remote_write. (Opcional na 1ª fase.) |
| (Opcional) OTEL Collector | 4317, 4318 | Recebe OTLP dos PCs e encaminha para Loki/Tempo/Mimir. |

**Pasta no repositório:** `deploy/monitoring/` — um ou mais `docker-compose*.yml` (stack principal + opcionais).

---

### PC1 — Storage (192.168.1.21)

| Serviço           | Porta  | Descrição |
|-------------------|--------|-----------|
| MinIO             | 9000, 9001 | Já existente. Métricas em `/minio/v2/metrics/cluster`. |
| Node Exporter     | 9100   | Métricas de host (CPU, RAM, disco). |
| cAdvisor          | 8080   | Métricas de containers Docker. |
| Blackbox Exporter | 9115   | Sondas HTTP e ICMP (uptime) para os 3 PCs; Prometheus no notebook consulta este. |
| Alertmanager      | 9093   | Recebe alertas do Prometheus; pode notificar (e-mail, Slack, etc.). |
| Promtail          | —      | Envia logs (arquivos do host ou Docker) para o Loki no notebook (3100). |

**Pasta no repositório:** `deploy/pc1/` — `docker-compose.yml` atual (MinIO) + extensão ou arquivo separado para observabilidade (exporters + Promtail + Alertmanager).

---

### PC2 — Application (192.168.1.20)

| Serviço            | Porta  | Descrição |
|--------------------|--------|-----------|
| Node Exporter      | 9100   | Métricas de host. |
| Redis Exporter     | 9121   | Métricas do Redis. |
| Postgres Exporter  | 9187   | Métricas do PostgreSQL. |
| Promtail           | —      | Envia logs para o Loki no notebook. |

Laravel, Nginx, PostgreSQL, Redis, RabbitMQ já existem; os exporters apenas expõem métricas que o Prometheus (notebook) coleta.

**Pasta no repositório:** `deploy/pc2/` — novo ou estendido `docker-compose` com exporters + Promtail.

---

### PC3 — Edge (192.168.1.22)

| Serviço        | Porta  | Descrição |
|----------------|--------|-----------|
| Node Exporter  | 9100   | Métricas de host. |
| Promtail       | —      | Envia logs para o Loki no notebook. |

**Pasta no repositório:** `deploy/pc3/` — adicionar observabilidade (Node Exporter + Promtail) ao que já existir.

---

## Fluxo de dados

1. **Métricas**
   - Prometheus (notebook) faz **scrape** nos endpoints dos PCs (Node Exporter, cAdvisor, Redis, Postgres, MinIO, Blackbox) conforme `prometheus.yml`.
   - Opcional: Prometheus faz **remote_write** para Mimir (notebook); Grafana consulta Mimir para métricas de longo prazo.

2. **Logs**
   - **Promtail** em cada PC lê logs (arquivos e/ou containers) e envia para **Loki** (notebook) via HTTP.
   - No Grafana você consulta Loki por rótulo (job, host, app).

3. **Traces**
   - Aplicações (ex.: Laravel com OpenTelemetry) enviam traces em OTLP para **Tempo** (notebook) na porta 4317 ou 4318.
   - Grafana usa Tempo como datasource para visualizar e correlacionar com logs (Loki) e métricas (Prometheus/Mimir).

4. **Alertas**
   - Prometheus (notebook) avalia regras em `rules/*.yml` e envia alertas para **Alertmanager** (PC1:9093).
   - Alertmanager aplica agrupamento, silences e envia notificações (e-mail, Slack, etc.).

---

## Estrutura de pastas no repositório (proposta)

```
deploy/
├── monitoring/                    # Notebook — stack central
│   ├── docker-compose.yml         # Prometheus + Grafana (base atual)
│   ├── docker-compose.lgtm.yml     # Loki + Tempo + Mimir (+ OTEL opcional)
│   ├── .env.example
│   ├── prometheus/
│   │   ├── prometheus.yml
│   │   └── rules/
│   │       └── alerts.yml.example
│   ├── loki/
│   │   └── loki-config.yml
│   ├── tempo/
│   │   └── tempo-config.yml
│   ├── mimir/
│   │   └── mimir-config.yml
│   └── grafana/
│       └── provisioning/          # datasources.yaml (Prometheus, Loki, Tempo, Mimir)
│
├── pc1/                           # Storage
│   ├── docker-compose.yml         # MinIO (existente)
│   ├── docker-compose.observability.yml  # Node Exporter, cAdvisor, Blackbox, Alertmanager, Promtail
│   └── promtail/
│       └── promtail-config.yml
│
├── pc2/                           # Application
│   ├── docker-compose.yml         # Laravel, Postgres, Redis, RabbitMQ, Nginx (existente quando houver)
│   ├── docker-compose.observability.yml  # Node Exporter, Redis Exporter, Postgres Exporter, Promtail
│   └── promtail/
│       └── promtail-config.yml
│
└── pc3/                           # Edge
    ├── (nginx, cloudflared existentes)
    ├── docker-compose.observability.yml  # Node Exporter, Promtail
    └── promtail/
        └── promtail-config.yml
```

**Uso no notebook:**  
`docker compose -f docker-compose.yml -f docker-compose.lgtm.yml up -d`  
(ou um único `docker-compose.yml` com todos os serviços, se preferir.)

**Uso no PC1:**  
`docker compose -f docker-compose.yml -f docker-compose.observability.yml up -d`

---

## Portas consolidadas (referência)

| Host      | Serviço           | Porta(s)   |
|-----------|-------------------|------------|
| Notebook  | Prometheus        | 9090       |
| Notebook  | Grafana           | 3000       |
| Notebook  | Loki              | 3100       |
| Notebook  | Tempo             | 3200, 4317, 4318 |
| Notebook  | Mimir             | 8080, 9090 (ou só 8080 se Prometheus ficar 9090) |
| PC1       | MinIO             | 9000, 9001 |
| PC1       | Node Exporter     | 9100       |
| PC1       | cAdvisor          | 8080       |
| PC1       | Blackbox Exporter | 9115       |
| PC1       | Alertmanager      | 9093       |
| PC2       | Node Exporter     | 9100       |
| PC2       | Redis Exporter    | 9121       |
| PC2       | Postgres Exporter | 9187       |
| PC3       | Node Exporter     | 9100       |

---

## Ordem sugerida de implementação

1. **Fase 1 — Base métricas (já em parte feita)**  
   - Notebook: Prometheus + Grafana + `prometheus.yml` com todos os jobs.  
   - PC1: Node Exporter, cAdvisor, Blackbox, Alertmanager.  
   - PC2: Node Exporter, Redis Exporter, Postgres Exporter.  
   - PC3: Node Exporter.  
   - Criar `prometheus/rules/` e um exemplo de `alerts.yml`.

2. **Fase 2 — LGTM no notebook**  
   - Adicionar Loki, Tempo e (opcional) Mimir no notebook.  
   - Configurar Grafana (datasources: Loki, Tempo, Mimir).  
   - Manter Prometheus como principal fonte de métricas em tempo real.

3. **Fase 3 — Logs (Loki + Promtail)**  
   - Promtail em PC1, PC2 e PC3 apontando para Loki no notebook.  
   - Dashboards no Grafana para logs por host/serviço.

4. **Fase 4 — Traces (Tempo)**  
   - Instrumentar aplicação (ex.: Laravel) com OpenTelemetry e enviar para Tempo.  
   - Correlação no Grafana (métrica → log → trace).

Este documento serve como referência para a implementação; os arquivos em `deploy/` podem ser criados ou ajustados conforme essa estrutura.
