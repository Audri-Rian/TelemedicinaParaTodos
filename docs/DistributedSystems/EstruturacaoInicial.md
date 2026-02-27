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
| **1** | Intel Celeron 4ª geração | 4 GB | 250 GB | Storage Node (MinIO) |
| **2** | Intel i5 8400 (6 núcleos) | 16 GB DDR4 | 500 GB HD | Application Node (Laravel, DB, Redis, RabbitMQ, Nginx) |
| **3** | Intel i3 6000 (2C/4T) | 4 GB | 250 GB | Edge / Gateway (Cloudflare Tunnel + Nginx + Certbot) |

---

# 📐 Distribuição nos Seus 3 Computadores

## Computador 1 — Storage Node (Celeron, 4 GB, 250 GB)

**Serviços:** MinIO (compatível S3)

| Aspecto | Análise |
|--------|---------|
| **CPU** | Celeron atende MinIO (serviço leve, ~0,5 core). |
| **RAM** | 4 GB é folga para MinIO (~512 MB). |
| **Disco** | 250 GB suficiente para laboratório (exames, imagens, documentos). Para produção futura, considerar disco externo ou NAS. |

**Containers previstos:**
- `minio` — armazenamento de objetos (exames, uploads, arquivos de pacientes)

**Rede:** Acesso interno pela aplicação (PC2). Não precisa ser exposto à internet.

---

## Computador 2 — Application Node (i5 8400, 16 GB, 500 GB)

**Serviços:** Laravel, PostgreSQL, Redis, RabbitMQ, Nginx (servidor de aplicação)

| Aspecto | Análise |
|--------|---------|
| **CPU** | i5 8400 (6 núcleos) atende bem a stack aplicação + DB + filas. |
| **RAM** | 16 GB cobre: Laravel ~1 GB, PostgreSQL ~2 GB, Redis ~1 GB, RabbitMQ ~1 GB, Nginx ~256 MB + SO → sobra para picos e 1–2 chamadas WebRTC leves. |
| **Disco** | 500 GB para sistema, dados do PostgreSQL, volumes Docker e logs. Preferir SSD se possível. |

**Containers previstos:**
- `laravel` (PHP-FPM) — backend da aplicação
- `postgres` — banco de dados
- `redis` — cache e sessões
- `rabbitmq` — filas (jobs, mensageria)
- `nginx` — servidor web da aplicação (porta 80/443 local neste host)

**WebRTC (opcional neste PC):** É possível rodar Janus ou Mediasoup no mesmo PC2 para **1–3 chamadas simultâneas** em qualidade moderada, usando a sobra de CPU/RAM. Para mais chamadas ou HD estável, o ideal seria um servidor de mídia dedicado.

---

## Computador 3 — Edge / Gateway (i3 6000, 4 GB, 250 GB)

**Serviços:** Cloudflare Tunnel (cloudflared), Nginx (reverse proxy), Certbot (SSL)

| Aspecto | Análise |
|--------|---------|
| **CPU** | i3 6000 (2C/4T) basta para cloudflared + Nginx como proxy e terminação SSL. |
| **RAM** | 4 GB suficientes para cloudflared, Nginx e Certbot. |
| **Disco** | 250 GB para SO, certificados e logs. |

**Containers / processos previstos:**
- **Cloudflare Tunnel (cloudflared)** — conexão **de saída** do PC3 para a Cloudflare; todo tráfego da internet que chega no seu domínio passa primeiro pela Cloudflare e depois pelo túnel até o PC3. **Não é necessário abrir portas 80/443 no roteador**; seu IP residencial fica oculto e a entrada na sua rede fica mais segura.
- `nginx` — reverse proxy (recebe o tráfego que vem pelo túnel e encaminha para PC2 ou PC1)
- `certbot` — renovação de certificados Let's Encrypt (para uso interno ou fallback)

**Fluxo com Tunnel (recomendado):**
1. Usuário acessa `seu-dominio.com` → DNS aponta para a Cloudflare (IP deles).
2. Cloudflare envia o tráfego pelo **túnel** já estabelecido (PC3 → Cloudflare, conexão de saída).
3. No PC3, o cloudflared entrega o tráfego para o Nginx (ex.: `localhost:80`).
4. Nginx encaminha: `/` e API → PC2 (Laravel); objetos/arquivos → PC1 ou via PC2.

**Vantagens do Tunnel:** IP da sua casa não fica exposto; não depende de port forwarding nem de o provedor liberar portas; SSL e proteção (DDoS, etc.) na borda da Cloudflare; um único ponto de entrada (PC3) para a internet.

---

# 📊 Resumo da Arquitetura nos 3 PCs

```text
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
              │  PC3 — Edge/Gateway  │
              │  cloudflared +       │
              │  Nginx + Certbot     │
              │  (i3, 4GB, 250GB)   │
              └──────────┬───────────┘
                         │ rede interna
         ┌──────────────┼──────────────┐
         ▼              ▼              ▼
┌────────────────┐ ┌────────────────┐ ┌────────────────┐
│  PC2 — App     │ │  PC1 — Storage │ │  (futuro)      │
│  Laravel       │ │  MinIO         │ │  Media Server  │
│  PostgreSQL    │ │  (Celeron,     │ │  (opcional)    │
│  Redis         │ │   4GB, 250GB)  │ │                │
│  RabbitMQ      │ └────────────────┘ └────────────────┘
│  Nginx (app)   │
│  (i5, 16GB,    │
│   500GB)       │
└────────────────┘
```

---

# 🔄 Por que o Tunnel está só no PC3? E como funciona o fluxo?

## Por que apenas o PC3 tem o Tunnel?

O Tunnel **não** existe porque o PC3 é o “mais forte” — ele está no PC3 porque o PC3 é o **único ponto de entrada** da internet na sua rede.

- **Um único “portão”:**
  - Você quer **uma única porta** pela qual todo o tráfego da internet entre: o **PC3**.
  - Quem acessa `seu-dominio.com` sempre bate na Cloudflare; a Cloudflare manda o tráfego **só** para o PC3 (pelo túnel).
  - O PC3 então **encaminha** internamente: “isso é app” → manda para o PC2; “isso é arquivo” → manda para o PC1 (ou via PC2).
- **PC2 e PC1 não precisam de Tunnel:**
  - Eles **nunca** são acessados diretamente pela internet. Só o PC3 “conversa” com a internet (via túnel).
  - O PC2 (Laravel) e o PC1 (MinIO) são acessados **só pela rede interna** (ex.: 192.168.1.x), pelo PC3 e entre si.
  - Colocar túnel no PC2 ou no PC1 seria redundante e complicaria a arquitetura (três portas de entrada em vez de uma).

Resumo: **Tunnel no PC3 = uma única “porta da frente”.** O servidor pesado (PC2) fica atrás dessa porta; o PC3 só faz o papel de **gateway** (recebe pelo túnel e repassa na rede interna).

---

## Fluxo de comunicação (passo a passo)

### 1. Usuário na internet acessa seu sistema (ex.: site ou API)

```text
Usuário (navegador)
    │
    ▼
Cloudflare (IP deles, DNS do seu domínio)
    │  ← tráfego chega na Cloudflare, não na sua casa
    ▼
Tunnel (conexão de saída PC3 → Cloudflare)
    │  ← Cloudflare envia o tráfego pelo túnel já estabelecido
    ▼
PC3 — cloudflared recebe e entrega para Nginx (localhost:80)
    │
    ▼
PC3 — Nginx (reverse proxy) decide:
    │  • Requisição de página/API?  → encaminha para PC2 (ex.: http://192.168.1.20:80)
    │  • Requisição de arquivo (MinIO)? → encaminha para PC1 ou via PC2
    ▼
PC2 (Laravel) ou PC1 (MinIO) responde
    │  ← resposta volta pela rede interna para o PC3
    ▼
PC3 → Tunnel → Cloudflare → Usuário
```

Ou seja: **entrada** = Internet → Cloudflare → Tunnel → **PC3** → (rede interna) → **PC2** ou **PC1**. Só o PC3 “vê” a internet; PC2 e PC1 só “vêem” o PC3 e a rede local.

### 2. Comunicação entre os próprios servidores (PC1 ↔ PC2 ↔ PC3)

Toda essa conversa acontece **só na rede interna** (sem passar pela internet nem pelo Tunnel):

- **PC3 → PC2:** Nginx no PC3 faz proxy para o Laravel no PC2 (ex.: `http://192.168.1.20`).
- **PC3 → PC1:** Nginx no PC3 pode encaminhar pedidos de arquivo para o MinIO no PC1 (ex.: `http://192.168.1.10:9000`) ou o Laravel no PC2 pode falar direto com o MinIO no PC1.
- **PC2 → PC1:** Laravel (PC2) acessa MinIO (PC1) direto pela rede interna (ex.: SDK apontando para `http://192.168.1.10:9000`).
- **PC2 → PC3:** Só se precisar (ex.: callback); em geral o fluxo é PC3 → PC2.

Nenhum desses acessos passa pelo Tunnel; o Tunnel só existe entre **Cloudflare** e **PC3**.

### 3. Resumo visual do fluxo

| Origem        | Destino       | Caminho                          | Passa pelo Tunnel? |
|---------------|---------------|-----------------------------------|--------------------|
| Internet      | Seu sistema   | Cloudflare → Tunnel → PC3 → Nginx → PC2 ou PC1 | Sim (até o PC3)    |
| PC3           | PC2 (Laravel) | Rede interna (192.168.x.x)       | Não                |
| PC3           | PC1 (MinIO)   | Rede interna                     | Não                |
| PC2           | PC1 (MinIO)   | Rede interna                     | Não                |
| PC2           | Internet (ex.: API do CRM) | PC2 → roteador → provedor → internet | Não (saída direta) |

Assim, o Tunnel fica **só no PC3** porque ele é a única “porta da frente”; o servidor pesado (PC2) e o storage (PC1) ficam atrás, acessíveis apenas pela rede interna.

---

# ⚠️ Limitações com Este Hardware

| Limitação | Comentário |
|-----------|------------|
| **WebRTC** | i5 no PC2 aguenta 1–3 chamadas. Para 5+ chamadas HD estável, o ideal é um servidor dedicado (ex.: máquina com 8+ GB RAM e 4+ cores só para Janus/Mediasoup). |
| **Disco no PC1** | 250 GB é suficiente para desenvolvimento e testes; para muitos exames/imagens em “produção”, planejar expansão (disco externo, NAS ou nuvem). |
| **PC3** | Só proxy/SSL; não colocar Laravel ou banco aqui para não sobrecarregar os 4 GB. |

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
| EC2 | Laravel + PHP-FPM | Custom image | **PC2** |

---

## 🗃️ Banco de Dados (RDS)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| RDS | PostgreSQL | postgres | **PC2** |

---

## ⚡ Cache e Mensageria (ElastiCache / SQS)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| ElastiCache | Redis | redis | **PC2** |
| SQS | RabbitMQ | rabbitmq | **PC2** |

---

## 🌐 Load Balancer / Edge (ALB + CloudFront)
| AWS | Docker Local | Onde |
|-----|--------------|------|
| ALB / Edge | Cloudflare Tunnel + Nginx Reverse Proxy | **Cloudflare** (DNS, SSL, túnel) → **PC3** (cloudflared + Nginx), **PC2** (app) |

---

## 🔐 Certificados SSL (ACM)
| AWS | Docker Local | Onde |
|-----|--------------|------|
| ACM | Certbot + Let's Encrypt | **PC3** |

---

## 🎥 Media Server (WebRTC SFU)
| AWS | Docker Local | Container | Onde |
|-----|--------------|-----------|------|
| Media Services | Janus / Mediasoup | janus / mediasoup | **PC2** (leve, 1–3 chamadas) ou servidor dedicado futuro |

---

## 🔑 Secrets Manager
| AWS | Docker Local |
|-----|--------------|
| Secrets Manager | .env + Docker Secrets |

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
- **PC2:** Nginx (app), Redis, PostgreSQL, RabbitMQ
- **PC3:** Cloudflare Tunnel (cloudflared), Nginx (reverse proxy), Certbot

## Fase 4 — Telemedicina
- **PC2:** Laravel + Reverb, opcional Janus/Mediasoup (1–3 chamadas)
- Integração MinIO (PC1) e gateway (PC3)
- Observabilidade (Prometheus + Grafana), se desejado

---

# ⚠️ Observações Importantes

- **Exposição à internet:** A abordagem recomendada é **Cloudflare Tunnel** (cloudflared no PC3). O domínio fica na Cloudflare; o tráfego entra pela Cloudflare e chega ao PC3 pelo túnel (conexão de saída), sem abrir portas 80/443 no roteador e sem expor o IP residencial. Alternativa: VPN ou port forwarding (menos seguro em IP residencial).
- Upload residencial limita chamadas WebRTC simultâneas.
- Com Tunnel, **nenhuma** porta do roteador precisa ser aberta para a aplicação.
- Manter PC1 e PC2 em rede privada (ex.: 192.168.x.x) e acessíveis apenas pelo PC3 e entre si.

---

# 🧠 Conclusão

Com essa distribuição, você usa os 3 computadores de forma coerente: **PC1** como storage, **PC2** como cérebro da aplicação (e opcionalmente mídia leve), e **PC3** como borda única com **Cloudflare Tunnel** + Nginx e SSL. O Tunnel garante que o tráfego da internet chegue ao seu ambiente sem expor o IP residencial e sem depender de port forwarding. Simular a AWS assim é uma ótima forma de aprender arquitetura cloud, DevOps e sistemas distribuídos em hardware real.
