# 🏥 Simulação de Arquitetura AWS para Telemedicina em Servidor Caseiro

Este documento descreve a arquitetura completa para simular serviços da AWS localmente usando Docker, com foco em um sistema de telemedicina (Laravel + Realtime + WebRTC), e **a distribuição recomendada nos seus 3 computadores**.

---

# 🎯 Objetivo do Projeto

- Simular infraestrutura AWS localmente
- Executar aplicação de telemedicina (Laravel, Reverb, WebRTC)
- Mapear serviços AWS → containers Docker
- Planejar consumo de CPU, RAM e disco
- Definir distribuição em múltiplas máquinas

---

# 🖥️ Seus 3 Computadores — Visão Geral

| # | CPU | RAM | Disco | Papel sugerido |
|---|-----|-----|--------|----------------|
| **1** | Intel Celeron 4ª geração | 4 GB | 500 GB | Storage Node (MinIO) |
| **2** | TV Box ARM | 3 GB | 16 GB | Edge / Gateway (Cloudflare Tunnel + Nginx + Certbot) |
| **3** | Intel i3 6000 (2C/4T) | 4 GB | 500 GB | Application Node (Laravel, DB, Redis, RabbitMQ, Nginx) |

**Nota:** O **monitoramento centralizado** (Prometheus + Grafana) roda no **seu notebook** — a máquina de controle onde você faz SSH e deploy. O notebook **não faz parte do cluster**; ele apenas consulta os 3 PCs pela rede interna e exibe health checks e métricas em um único dashboard. Consulte [Tutorial-Monitoramento-Notebook.md](./Tutorial-Monitoramento-Notebook.md).

---

# 📐 Distribuição nos Seus 3 Computadores

## Computador 1 — Storage Node (Celeron, 4 GB, 250 GB)

**Serviços:** MinIO (compatível S3) + observabilidade (exporters, Alertmanager, Promtail)

| Aspecto | Análise |
|--------|---------|
| **CPU** | Celeron atende MinIO (serviço leve, ~0,5 core) e os exporters. |
| **RAM** | 4 GB é folga para MinIO (~512 MB) + Node Exporter, cAdvisor, Blackbox, Alertmanager, Promtail. |
| **Disco** | 250 GB suficiente para laboratório (exames, imagens, documentos). Para produção futura, considerar disco externo ou NAS. |

**Containers / serviços previstos:**

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| **minio** | 9000, 9001 | Armazenamento de objetos (exames, uploads, arquivos de pacientes). Métricas em `/minio/v2/metrics/cluster`. |
| **node-exporter** | 9100 | Métricas de host (CPU, RAM, disco). |
| **cadvisor** | 8080 | Métricas de containers Docker. |
| **blackbox-exporter** | 9115 | Sondas HTTP e ICMP (uptime) para os 3 PCs; o Prometheus no notebook consulta este. |
| **alertmanager** | 9093 | Recebe alertas do Prometheus; agrupamento e notificações (e-mail, Slack, etc.). |
| **promtail** | — | Envia logs (arquivos e Docker) para o Loki no notebook. |

**Rede:** Acesso interno pela aplicação (PC3). Não precisa ser exposto à internet.

---

## Computador 2 — Edge / Gateway (TV Box ARM, 3 GB, 16 GB)

**Serviços:** Cloudflare Tunnel (cloudflared), Nginx (reverse proxy), Certbot (SSL) + observabilidade (Node Exporter, Promtail)

| Aspecto | Análise |
|--------|---------|
| **CPU** | TV Box ARM atende bem cloudflared + Nginx (proxy leve) e Node Exporter. |
| **RAM** | 3 GB suficientes para cloudflared, Nginx, Certbot, Node Exporter e Promtail. |
| **Disco** | 16 GB apenas para SO, certificados e logs — não colocar aplicação nem banco aqui. |

**Containers / processos previstos:**

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| **cloudflared** | — | Conexão **de saída** do PC2 para a Cloudflare; tráfego do domínio chega pelo túnel. Não é necessário abrir portas 80/443 no roteador. |
| **nginx** | 80 | Reverse proxy (recebe do túnel e encaminha para PC3 ou PC1). |
| **certbot** | — | Renovação de certificados Let's Encrypt (uso interno ou fallback). |
| **node-exporter** | 9100 | Métricas de host (CPU, RAM, disco). |
| **promtail** | — | Envia logs para o Loki no notebook. |

**Fluxo com Tunnel (recomendado):**
1. Usuário acessa `seu-dominio.com` → DNS aponta para a Cloudflare (IP deles).
2. Cloudflare envia o tráfego pelo **túnel** já estabelecido (PC2 → Cloudflare, conexão de saída).
3. No **PC2**, o cloudflared entrega o tráfego para o Nginx (ex.: `localhost:80`).
4. Nginx encaminha: `/` e API → **PC3** (Laravel); objetos/arquivos → PC1 ou via PC3.

**Vantagens do Tunnel:** IP da sua casa não fica exposto; um único ponto de entrada (PC2) para a internet. A aplicação pesada (Laravel, PostgreSQL, Redis, RabbitMQ) fica no **PC3**, com mais disco e recursos.

---

## Computador 3 — Application Node (Intel i3 6000, 4 GB, 500 GB)

**Serviços:** Laravel, PostgreSQL, Redis, RabbitMQ, Nginx (servidor de aplicação) + observabilidade (Node Exporter, Redis Exporter, Postgres Exporter, Promtail)

| Aspecto | Análise |
|--------|---------|
| **CPU** | i3 6000 (2C/4T) atende aplicação + DB + filas + exporters; mais capaz que o PC2 (TV Box). |
| **RAM** | 4 GB para Laravel, PostgreSQL, Redis, RabbitMQ, Nginx e exporters; ajustar limites dos containers conforme o total. |
| **Disco** | 500 GB para sistema, dados do PostgreSQL, volumes Docker e logs. Preferir SSD se possível. |

**Containers / serviços previstos:**

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| **laravel** (PHP-FPM) | — | Backend da aplicação de telemedicina. |
| **postgres** | 5432 | Banco de dados. |
| **redis** | 6379 | Cache e sessões. |
| **rabbitmq** | 5672, 15672 | Filas (jobs, mensageria). |
| **nginx** | 80, 443 | Servidor web da aplicação (local neste host). |
| **node-exporter** | 9100 | Métricas de host (CPU, RAM, disco). |
| **redis-exporter** | 9121 | Métricas do Redis para o Prometheus. |
| **postgres-exporter** | 9187 | Métricas do PostgreSQL para o Prometheus. |
| **promtail** | — | Envia logs para o Loki no notebook. |

**WebRTC (opcional):** Para 1–3 chamadas leves pode rodar no PC3; para mais chamadas ou HD estável, usar servidor de mídia dedicado.

---

## Notebook — Nó de Monitoramento (fora do cluster)

**Serviços:** Prometheus + Grafana + stack LGTM (Loki, Tempo, Mimir) — observabilidade centralizada

| Aspecto | Análise |
|--------|---------|
| **Papel** | Máquina de controle: SSH, deploy e **visualização única** de métricas, logs e traces dos 3 PCs. Não executa aplicação nem storage. |
| **Local** | Seu notebook (Windows, Linux ou macOS), na mesma rede interna (ex.: 192.168.1.x) que PC1, PC2 e PC3. |
| **Por que no notebook?** | Se o PC2 ou o PC3 cair, o monitoramento continua funcionando — equivalente a AWS CloudWatch ou Datadog, que ficam fora da aplicação. |

**Containers / serviços previstos (no notebook):**

| Serviço | Porta(s) | Descrição |
|---------|----------|-----------|
| **Prometheus** | 9090 | Coleta métricas dos 3 PCs (Node Exporter, cAdvisor, Redis, Postgres, MinIO, Blackbox); avalia regras de alerta. |
| **Grafana** | 3000 | Dashboard único: datasources Prometheus, Loki, Tempo, Mimir. Visualização de métricas, logs e traces. |
| **Loki** | 3100 | Agregação e armazenamento de logs; recebe envios do Promtail (PC1, PC2, PC3). |
| **Tempo** | 3200, 4317, 4318 | Armazenamento de traces (OTLP); aplicações podem enviar traces para correlação no Grafana. |
| **Mimir** | 8080 | Métricas de longa duração (opcional); Prometheus pode fazer remote_write. |

**Fluxo:** O notebook consulta, pela rede interna, os endpoints de métricas de cada PC; os PCs enviam logs via Promtail para o Loki. Tudo é visualizado no Grafana (ex.: `http://localhost:3000`). Guia completo em [Tutorial-Monitoramento-Notebook.md](./Tutorial-Monitoramento-Notebook.md) e [Arquitetura-LGTM-Observabilidade.md](./Arquitetura-LGTM-Observabilidade.md).

---

# 📋 Distribuição completa de serviços e aplicações

Lista consolidada de **todos** os serviços por host (aplicação + observabilidade). Ajuste IPs e portas conforme sua rede.

## Por host

| Host | Serviço | Porta(s) | Função |
|------|---------|----------|--------|
| **PC1** | minio | 9000, 9001 | Armazenamento de objetos (S3-compatível). |
| **PC1** | node-exporter | 9100 | Métricas de host (CPU, RAM, disco). |
| **PC1** | cadvisor | 8080 | Métricas de containers Docker. |
| **PC1** | blackbox-exporter | 9115 | Sondas HTTP/ICMP (uptime) para PC1, PC2, PC3. |
| **PC1** | alertmanager | 9093 | Recebe alertas do Prometheus; notificações. |
| **PC1** | promtail | — | Envia logs → Loki (notebook). |
| **PC2** | cloudflared | — | Tunnel de saída para Cloudflare (entrada internet). |
| **PC2** | nginx | 80 | Reverse proxy (túnel → PC3 / PC1). |
| **PC2** | certbot | — | Renovação certificados Let's Encrypt. |
| **PC2** | node-exporter | 9100 | Métricas de host. |
| **PC2** | promtail | — | Envia logs → Loki (notebook). |
| **PC3** | laravel (PHP-FPM) | — | Backend aplicação telemedicina. |
| **PC3** | postgres | 5432 | Banco de dados. |
| **PC3** | redis | 6379 | Cache e sessões. |
| **PC3** | rabbitmq | 5672, 15672 | Filas (jobs, mensageria). |
| **PC3** | nginx | 80, 443 | Servidor web da aplicação. |
| **PC3** | node-exporter | 9100 | Métricas de host. |
| **PC3** | redis-exporter | 9121 | Métricas Redis → Prometheus. |
| **PC3** | postgres-exporter | 9187 | Métricas PostgreSQL → Prometheus. |
| **PC3** | promtail | — | Envia logs → Loki (notebook). |
| **Notebook** | prometheus | 9090 | Scrape de métricas (PC1, PC2, PC3); regras de alerta. |
| **Notebook** | grafana | 3000 | Dashboards; datasources: Prometheus, Loki, Tempo, Mimir. |
| **Notebook** | loki | 3100 | Armazenamento de logs (recebe Promtail). |
| **Notebook** | tempo | 3200, 4317, 4318 | Armazenamento de traces (OTLP). |
| **Notebook** | mimir | 8080 | Métricas long-term (opcional). |

## Resumo por tipo

| Tipo | Onde | Serviços |
|------|------|----------|
| **Aplicação / negócio** | PC1 | MinIO. |
| **Aplicação / negócio** | PC2 | cloudflared, Nginx (proxy), Certbot. |
| **Aplicação / negócio** | PC3 | Laravel, PostgreSQL, Redis, RabbitMQ, Nginx (app). |
| **Observabilidade (métricas)** | Notebook | Prometheus, Grafana, Mimir. |
| **Observabilidade (logs)** | Notebook | Loki. PC1, PC2, PC3: Promtail. |
| **Observabilidade (traces)** | Notebook | Tempo. |
| **Exporters / alertas** | PC1 | Node Exporter, cAdvisor, Blackbox, Alertmanager. |
| **Exporters** | PC2 | Node Exporter. |
| **Exporters** | PC3 | Node Exporter, Redis Exporter, Postgres Exporter. |

---

# 📊 Resumo da Arquitetura nos 3 PCs

```
     [ Notebook — Monitoramento ]   ← Prometheus + Grafana (fora do cluster)
     │  SSH + deploy + dashboard único
     │  consulta pela rede interna
     │
     └──────────────┬───────────────┬───────────────┐
                    │               │               │
                    ▼               ▼               ▼
                    [ Internet ]
                         │
                         ▼
              ┌──────────────────────┐
              │     Cloudflare       │  ← DNS + SSL + Tunnel (IP público deles)
              │  (seu domínio aqui)  │
              └──────────┬───────────┘
                         │ tunnel (tráfego vem pelo túnel, não por porta aberta)
                         ▼
              ┌──────────────────────┐
              │  PC2 — Edge/Gateway  │
              │  cloudflared +       │
              │  Nginx + Certbot     │
              │  (TV Box ARM, 3GB, 16GB) │
              └──────────┬───────────┘
                         │ rede interna
         ┌──────────────┼──────────────┐
         ▼              ▼              ▼
┌────────────────┐ ┌────────────────┐ ┌────────────────┐
│  PC3 — App     │ │  PC1 — Storage │ │  (futuro)      │
│  Laravel       │ │  MinIO         │ │  Media Server  │
│  PostgreSQL    │ │  (Celeron,     │ │  (opcional)    │
│  Redis         │ │   4GB, 250GB)  │ │                │
│  RabbitMQ      │ └────────────────┘ └────────────────┘
│  Nginx (app)   │
│  (i3, 4GB, 500GB) │
└────────────────┘
```

---

# 📡 Diagrama de rede — IPs, portas e quem fala com quem

Visão única para **deploy**, **firewall** e entendimento da “VPC” interna (rede 192.168.1.0/24). Ajuste os IPs conforme seu roteador e reservas DHCP.

## Endereços e portas por nó

| Nó | IP de exemplo | Hostname sugerido | Portas principais | Serviço |
|----|----------------|-------------------|-------------------|---------|
| **PC1** | 192.168.1.21 | pc1-storage | 22 (SSH), **9000** (MinIO API), **9001** (MinIO Console), 8080 (cAdvisor), 9100 (Node Exporter), 9093 (Alertmanager), 9115 (Blackbox Exporter) | Storage + observabilidade |
| **PC2** | 192.168.1.20 | pc2-edge | 22 (SSH), **80** (Nginx reverse proxy), 9100 (Node Exporter) | Edge + observabilidade |
| **PC3** | 192.168.1.22 | pc3-app | 22 (SSH), **80** (Nginx app), 5432 (PostgreSQL), 6379 (Redis), 5672/15672 (RabbitMQ), 9100 (Node Exporter), 9121 (Redis Exporter), 9187 (Postgres Exporter) | Application + observabilidade |
| **Notebook** | 192.168.1.x (DHCP ou fixo) | — | 3000 (Grafana), 9090 (Prometheus), 3100 (Loki), 3200/4317/4318 (Tempo), 8080 (Mimir), 22 (SSH cliente) | Monitoramento / LGTM |
| **Cloudflare** | (internet) | — | 443 (HTTPS); túnel = conexão **de saída** PC2 → Cloudflare | DNS, SSL, Tunnel |

**Rede interna sugerida:** `192.168.1.0/24` — gateway ex.: `192.168.1.1`. PC1, PC2 e PC3 com IP fixo (reserva DHCP ou Netplan).

## Quem fala com quem

| Origem | Destino | Porta / meio | O quê |
|--------|---------|--------------|--------|
| **Internet** (usuário) | **Cloudflare** | 443 (HTTPS) | Acesso ao domínio (ex.: seu-dominio.com) |
| **Cloudflare** | **PC2** | Túnel (tráfego enviado pela conexão de saída PC2→Cloudflare) | Requisições do domínio entregues ao cloudflared |
| **PC2** (cloudflared) | **PC2** (Nginx) | localhost:80 | Tráfego recebido do túnel repassado ao Nginx |
| **PC2** (Nginx) | **PC3** | 192.168.1.22:80 | Proxy para Laravel (páginas, API) |
| **PC2** (Nginx) | **PC1** | 192.168.1.21:9000 | Proxy para MinIO (opcional; ou tráfego de arquivos via PC3) |
| **PC3** (Laravel) | **PC1** (MinIO) | 192.168.1.21:9000 | Upload/download de objetos (S3) |
| **Notebook** | **PC1, PC2, PC3** | 22 (SSH), 9100 (Node Exporter), 8080 (cAdvisor PC1), 9115 (Blackbox PC1), 9121/9187 (PC3) | SSH, deploy; Prometheus coleta métricas |
| **PC1, PC2, PC3** | **Notebook** (Loki) | 3100 | Promtail envia logs |
| **Notebook** | **Prometheus / Grafana / Loki / Tempo / Mimir** | localhost:9090, 3000, 3100, 3200, 8080 | Acesso aos dashboards e APIs de observabilidade |

Nenhum tráfego da internet chega diretamente ao PC1 ou ao PC3; tudo entra pela Cloudflare e pelo túnel até o PC2, que então encaminha na rede interna.

## Visão em diagrama (rede interna + borda)

```
                    [ Internet ]
                         │ :443 (usuário → Cloudflare)
                         ▼
              ┌──────────────────────┐
              │     Cloudflare       │  DNS + SSL + Tunnel
              └──────────┬───────────┘
                         │ túnel (Cloudflare → PC2)
                         ▼
   ┌─────────────────────────────────────────────────────────┐
   │  PC2  192.168.1.20  │  :80 Nginx (recebe do túnel)     │
   │  pc2-edge           │  → proxy para PC3 :80, PC1 :9000  │
   └─────────────────────┬──────────────────────────────────┘
                         │ rede 192.168.1.0/24
         ┌───────────────┼───────────────┐
         ▼               ▼               ▼
   ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐
   │ PC3         │ │ PC1         │ │ Notebook                 │
   │ 192.168.1.22│ │ 192.168.1.21│ │ 192.168.1.x              │
   │ :80 Nginx   │ │ :9000 MinIO │ │ :9090 Prometheus         │
   │ :5432 PG    │ │ :9001 Console│ │ :3000 Grafana            │
   │ :6379 Redis │ │             │ │ → consulta :9100 (PCs)  │
   │ :5672 MQ    │ │             │ │ → SSH :22 (PCs)           │
   └──────┬──────┘ └──────▲──────┘ └─────────────────────────┘
          │               │
          └───────────────┘  Laravel → MinIO (API S3)
```

---

# 🔄 Por que o Tunnel está só no PC2? E como funciona o fluxo?

## Por que apenas o PC2 tem o Tunnel?

O Tunnel está no **PC2** (TV Box / Edge) porque o PC2 é o **único ponto de entrada** da internet na sua rede.

- **Um único “portão”:**
  - Todo o tráfego da internet entra pelo **PC2** (cloudflared + Nginx).
  - Quem acessa `seu-dominio.com` bate na Cloudflare; a Cloudflare manda o tráfego **só** para o PC2 (pelo túnel).
  - O PC2 então **encaminha** internamente: “isso é app” → manda para o **PC3** (Laravel); “isso é arquivo” → manda para o PC1 (ou via PC3).
- **PC3 e PC1 não precisam de Tunnel:**
  - Eles **nunca** são acessados diretamente pela internet. Só o PC2 “conversa” com a internet (via túnel).
  - O PC3 (Laravel) e o PC1 (MinIO) são acessados **só pela rede interna**, pelo PC2 e entre si.
  - Colocar túnel no PC3 ou no PC1 seria redundante e complicaria a arquitetura.

Resumo: **Tunnel no PC2 = uma única “porta da frente”.** O servidor pesado (PC3 — aplicação) e o storage (PC1) ficam atrás; o PC2 só faz o papel de **gateway** (recebe pelo túnel e repassa na rede interna).

---

## Fluxo de comunicação (passo a passo)

### 1. Usuário na internet acessa seu sistema (ex.: site ou API)

```
Usuário (navegador)
    │
    ▼
Cloudflare (IP deles, DNS do seu domínio)
    │  ← tráfego chega na Cloudflare, não na sua casa
    ▼
Tunnel (conexão de saída PC2 → Cloudflare)
    │  ← Cloudflare envia o tráfego pelo túnel já estabelecido
    ▼
PC2 — cloudflared recebe e entrega para Nginx (localhost:80)
    │
    ▼
PC2 — Nginx (reverse proxy) decide:
    │  • Requisição de página/API?  → encaminha para PC3 (ex.: http://192.168.1.22:80)
    │  • Requisição de arquivo (MinIO)? → encaminha para PC1 ou via PC3
    ▼
PC3 (Laravel) ou PC1 (MinIO) responde
    │  ← resposta volta pela rede interna para o PC2
    ▼
PC2 → Tunnel → Cloudflare → Usuário
```

Ou seja: **entrada** = Internet → Cloudflare → Tunnel → **PC2** → (rede interna) → **PC3** ou **PC1**. Só o PC2 “vê” a internet; PC3 e PC1 só “vêem” o PC2 e a rede local.

### 2. Comunicação entre os próprios servidores (PC1 ↔ PC2 ↔ PC3)

Toda essa conversa acontece **só na rede interna** (sem passar pela internet nem pelo Tunnel):

- **PC2 → PC3:** Nginx no PC2 faz proxy para o Laravel no PC3 (ex.: `http://192.168.1.22`).
- **PC2 → PC1:** Nginx no PC2 pode encaminhar pedidos de arquivo para o MinIO no PC1 (ex.: `http://192.168.1.21:9000`) ou o Laravel no PC3 pode falar direto com o MinIO no PC1.
- **PC3 → PC1:** Laravel (PC3) acessa MinIO (PC1) direto pela rede interna (ex.: SDK apontando para `http://192.168.1.21:9000`).
- **PC3 → PC2:** Só se precisar (ex.: callback); em geral o fluxo é PC2 → PC3.

Nenhum desses acessos passa pelo Tunnel; o Tunnel só existe entre **Cloudflare** e **PC2**.

### 3. Resumo visual do fluxo

| Origem        | Destino       | Caminho                          | Passa pelo Tunnel? |
|---------------|---------------|-----------------------------------|--------------------|
| Internet      | Seu sistema   | Cloudflare → Tunnel → PC2 → Nginx → PC3 ou PC1 | Sim (até o PC2)    |
| PC2           | PC3 (Laravel) | Rede interna (192.168.x.x)       | Não                |
| PC2           | PC1 (MinIO)   | Rede interna                     | Não                |
| PC3           | PC1 (MinIO)   | Rede interna                     | Não                |
| PC3           | Internet (ex.: API do CRM) | PC3 → roteador → provedor → internet | Não (saída direta) |

Assim, o Tunnel fica **só no PC2** porque ele é a única “porta da frente”; o servidor pesado (PC3) e o storage (PC1) ficam atrás, acessíveis apenas pela rede interna.

---

# ⚠️ Limitações com Este Hardware

| Limitação | Comentário |
|-----------|------------|
| **PC2 (TV Box)** | Apenas Edge: cloudflared + Nginx + Certbot. 3 GB RAM e 16 GB de disco — não colocar aplicação nem banco aqui. |
| **PC3 (Application)** | i3 6000, 4 GB RAM, 500 GB: Laravel, Postgres, Redis, RabbitMQ cabem; ajustar limites de containers. WebRTC leve (1–3 chamadas) possível; para mais, usar servidor dedicado. |
| **Disco no PC1** | 500 GB (ou 250 GB conforme seu caso) para MinIO; para muitos exames em “produção”, planejar expansão (disco externo, NAS ou nuvem). |

---

# 🧩 Mapeamento AWS → Docker Local

## 🗄️ Armazenamento de Objetos (S3)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| S3 | MinIO | minio/minio | **PC1** |

Uso: exames médicos, uploads de imagens, arquivos de pacientes.

---

## 🧠 Computação (EC2)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| EC2 | Laravel + PHP-FPM | Custom image | **PC3** |

---

## 🗃️ Banco de Dados (RDS)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| RDS | PostgreSQL | postgres | **PC3** |

---

## ⚡ Cache e Mensageria (ElastiCache / SQS)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| ElastiCache | Redis | redis | **PC3** |
| SQS | RabbitMQ | rabbitmq | **PC3** |

---

## 🌐 Load Balancer / Edge (ALB + CloudFront)
| AWS | Docker Local | Onde |
|-----|--------------|------|
| ALB / Edge | Cloudflare Tunnel + Nginx Reverse Proxy | **Cloudflare** (DNS, SSL, túnel) → **PC2** (cloudflared + Nginx), **PC3** (app) |

---

## 🔐 Certificados SSL (ACM)
| AWS | Docker Local | Onde |
|-----|--------------|------|
| ACM | Certbot + Let's Encrypt | **PC2** |

---

## 🎥 Media Server (WebRTC SFU)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| Media Services | Janus / Mediasoup | janus / mediasoup | **PC3** (leve, 1–3 chamadas) ou servidor dedicado futuro |

---

## 🔑 Secrets Manager
| AWS | Docker Local |
|-----|--------------|
| Secrets Manager | .env + Docker Secrets |

---

## 📊 Monitoramento e Observabilidade (CloudWatch)
| AWS | Docker Local | Onde |
|-----|--------------|------|
| CloudWatch (métricas + dashboards) | Prometheus + Grafana (+ stack LGTM: Loki, Tempo, Mimir) | **Notebook** (fora do cluster) |

O Prometheus coleta métricas e health checks dos 3 PCs pela rede interna; o Grafana exibe tudo em um único dashboard. Opcionalmente a stack LGTM (Loki para logs, Tempo para traces, Mimir para métricas de longa duração) roda no notebook; os PCs enviam logs via Promtail. Ver [Tutorial-Monitoramento-Notebook.md](./Tutorial-Monitoramento-Notebook.md) e [Arquitetura-LGTM-Observabilidade.md](./Arquitetura-LGTM-Observabilidade.md).

---

# 🧨 Estimativa de Consumo de Recursos (referência)

## Stack por serviço (referência geral)
| Serviço | RAM | CPU | Disco |
|---------|-----|-----|--------|
| Laravel | 1 GB | 1 core | 1 GB |
| PostgreSQL | 2 GB | 1 core | 10 GB+ |
| Redis | 1 GB | 0,5 core | — |
| MinIO | 512 MB | 0,5 core | conforme uso |
| Nginx | 256 MB | 0,2 core | — |
| RabbitMQ | 1 GB | 0,5 core | 1 GB |
| Janus/Mediasoup (leve) | 2–4 GB | 2–4 cores | — |
| Prometheus + Grafana (notebook) | ~512 MB | 0,5 core | 1–2 GB |

## Stack com WebRTC (referência para servidor dedicado)
Para **5 chamadas simultâneas HD**: ~8 GB RAM, ~8 cores (servidor de mídia dedicado).

---

# 🚀 Roadmap de Implementação

## Fase 1 — Base Linux (nos 3 PCs)
- Ubuntu Server LTS
- SSH
- UFW Firewall
- Usuário sem root
- Rede interna estável (IPs fixos ou DHCP reservado)

## Fase 2 — Docker
- Docker Engine
- Docker Compose
- Teste de containers básicos em cada máquina

## Fase 3 — Simulação AWS
- **PC1:** MinIO
- **PC2:** Cloudflare Tunnel (cloudflared), Nginx (reverse proxy), Certbot
- **PC3:** Nginx (app), Redis, PostgreSQL, RabbitMQ

## Fase 4 — Telemedicina
- **PC3:** Laravel + Reverb, opcional Janus/Mediasoup (1–3 chamadas)
- Integração MinIO (PC1) e gateway (PC2)
- **Notebook:** Observabilidade centralizada (Prometheus + Grafana) — health checks e métricas dos 3 PCs em um único dashboard. Ver [Tutorial-Monitoramento-Notebook.md](./Tutorial-Monitoramento-Notebook.md).

---

# ⚠️ Observações Importantes

- **Exposição à internet:** A abordagem recomendada é **Cloudflare Tunnel** (cloudflared no **PC2**). O domínio fica na Cloudflare; o tráfego entra pela Cloudflare e chega ao PC2 pelo túnel (conexão de saída), sem abrir portas 80/443 no roteador e sem expor o IP residencial. Alternativa: VPN ou port forwarding (menos seguro em IP residencial).
- Upload residencial limita chamadas WebRTC simultâneas.
- Com Tunnel, **nenhuma** porta do roteador precisa ser aberta para a aplicação.
- Manter PC1 e PC3 em rede privada (ex.: 192.168.x.x) e acessíveis apenas pelo PC2 e entre si.

---

# 🧠 Conclusão

Com essa distribuição, você usa os 3 computadores de forma coerente: **PC1** como storage, **PC2** (TV Box) como borda única com **Cloudflare Tunnel** + Nginx e SSL, e **PC3** (i3, 4 GB, 500 GB) como cérebro da aplicação (Laravel, PostgreSQL, Redis, RabbitMQ e opcionalmente mídia leve). O **notebook** funciona como central de controle (SSH, deploy) e **nó de monitoramento** (Prometheus + Grafana), exibindo health checks e métricas de todos os serviços em um único lugar — sem depender do cluster. O Tunnel garante que o tráfego da internet chegue ao seu ambiente sem expor o IP residencial e sem depender de port forwarding. Simular a AWS assim é uma ótima forma de aprender arquitetura cloud, DevOps e sistemas distribuídos em hardware real.
