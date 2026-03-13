## 🏗️ Camada de Infraestrutura (Infrastructure Layer)

Responsável por **onde e como** o sistema roda em produção:

- Servidores, rede, DNS/CDN, segurança, deploy, monitoramento e escalabilidade.
- Integração com provedores de nuvem (AWS) e serviços de borda (Cloudflare).

### 🎯 Responsabilidades

- Provisionar e manter:
  - Instâncias (EC2), sistema operacional, patches de segurança.
  - Servidor web (Nginx) e PHP-FPM.
  - Storage de arquivos, backups e rotinas de recuperação.
- Configurar:
  - DNS e HTTPS (Cloudflare, Let’s Encrypt).
  - Balanceadores de carga e autoscaling (em evoluções futuras).
- Garantir:
  - Observabilidade (logs, métricas, alertas).
  - Hardening de segurança (ports, SSH, secrets).

### 🧩 Tecnologias Envolvidas

- **AWS EC2** – instância Ubuntu que hospeda o app (produção atual).
- **Nginx + PHP-FPM** – servidor HTTP + runtime PHP.
- **Cloudflare** – DNS, CDN e proteção.
- **Docker** – padronização de ambiente (stack em `deploy/`; arquitetura em `docs/DistributedSystems`).
- **CI/CD (GitHub Actions)** – pipeline de build/deploy (recomendado).

### 📂 Arquitetura de referência: docs/DistributedSystems

A **estrutura oficial** de infraestrutura (homelab em 3 PCs + notebook) está documentada em:

| Documento | Conteúdo |
|-----------|----------|
| [Contexto.md](../../DistributedSystems/Contexto.md) | Motivação, simulação AWS local, Docker, custos. |
| [EstruturaInicial.md](../../DistributedSystems/EstruturaInicial.md) | Distribuição nos 3 PCs (PC1 Storage, PC2 Edge, PC3 Application) + Notebook LGTM; rede, portas, fluxo do túnel. |
| [Arquitetura-LGTM-Observabilidade.md](../../DistributedSystems/Arquitetura-LGTM-Observabilidade.md) | Stack LGTM (Loki, Grafana, Tempo, Mimir), Prometheus, exporters; onde cada componente roda. |
| [Documentacao-Projeto-Laravel-Telemedicina.md](../../DistributedSystems/Documentacao-Projeto-Laravel-Telemedicina.md) | Como o projeto Laravel se encaixa na infraestrutura distribuída. |

Use esses documentos como referência para deploy no homelab e para alinhar a stack Docker ao **node de aplicação (PC3)**.

### 📂 Documentos Relacionados (camada de infraestrutura)

- Infraestrutura atual (produção EC2):
  - [Infraestrutura.md](./Infraestrutura.md) – descrição detalhada da infra atual (EC2, Nginx, DNS).
  - [aws/AvatarStorageProduction.md](./aws/AvatarStorageProduction.md) – armazenamento de avatares em produção.
  - [aws/CloudScalabilityStrategy.md](./aws/CloudScalabilityStrategy.md) e [CloudScalabilityStrategy2.md](./aws/CloudScalabilityStrategy2.md) – estratégias de escalabilidade.
- Pendências e migrações:
  - [Pending Issues/TransitionPostgreeSQL.md](../../Pending%20Issues/TransitionPostgreeSQL.md)
  - [Pending Issues/TransitionRedis.md](../../Pending%20Issues/TransitionRedis.md)
  - [Tasks/TASK_11_MIGRACAO_CONFIG_TELEMEDICINE.md](../../Tasks/TASK_11_MIGRACAO_CONFIG_TELEMEDICINE.md)
  - [Tasks/TASK_11_GOVERNANCA_BACKEND.md](../../Tasks/TASK_11_GOVERNANCA_BACKEND.md)

> Importante: `domainconfig.md` contém informações sensíveis (chave SSH e IPs). Trate esse arquivo como **secreto** e planeje removê-lo do repositório público, rotacionando as chaves na AWS.

### 🔐 Boas Práticas de Segurança

- Nunca versionar chaves privadas ou `.pem` no repositório.
- Usar **Secrets** do GitHub Actions e variáveis de ambiente da AWS para credenciais.
- Habilitar:
  - HTTPS com Let’s Encrypt/Certbot.
  - HTTP/2 e compressão (Gzip/Brotli).
  - Logs estruturados de Nginx e Laravel.
- Restringir portas no Security Group (apenas 80/443 públicos, SSH restrito).

### 📈 Escalabilidade e Monitoramento

- Escalabilidade:
  - Separar banco de dados em RDS (future-proof).
  - Colocar um ALB/NLB na frente do EC2 (quando houver múltiplas instâncias).
  - Servir assets estáticos por Cloudflare com cache agressivo.
- Monitoramento:
  - **CloudWatch** para métricas básicas (CPU, memória, disco, logs).
  - Uptime Robot / Healthchecks para disponibilidade HTTP.
  - Alertas em canais de incidentes (email/Slack).

### 🔮 Evoluções Futuras

- Containerização:
  - Empacotar app Laravel + Node em imagens Docker.
  - Orquestrar com ECS/Fargate ou Kubernetes (se a complexidade justificar).
- Observabilidade avançada:
  - APM (New Relic, Datadog, Elastic APM) para rastrear requisições lentas.
  - Dashboards de métricas de videoconferência (latência, falhas).
- Multi- região / alta disponibilidade:
  - Replicação de banco.
  - Failover automático e DNS com health checks.

