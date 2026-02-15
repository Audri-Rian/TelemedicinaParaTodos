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

- **AWS EC2** – instância Ubuntu que hospeda o app.
- **Nginx + PHP-FPM** – servidor HTTP + runtime PHP.
- **Cloudflare** – DNS, CDN e proteção.
- **Docker** (planejado/possível) – padronização de ambiente.
- **CI/CD (GitHub Actions)** – pipeline de build/deploy (recomendado).

### 📂 Documentos Relacionados

- Infraestrutura atual:
  - `../../../Infraestrutra.md` – descrição detalhada da infra atual (EC2, Nginx, DNS).
  - `../../aws/AvatarStorageProduction.md` – armazenamento de avatares em produção.
  - `../../aws/CloudScalabilityStrategy.md` e `CloudScalabilityStrategy2.md` – estratégias de escalabilidade.
- Pendências e migrações:
  - `../../Pending Issues/TransitionPostgreeSQL.md`
  - `../../Pending Issues/TransitionRedis.md`
  - `../../Tasks/TASK_11_MIGRACAO_CONFIG_TELEMEDICINE.md`
  - `../../Tasks/TASK_11_GOVERNANCA_BACKEND.md`

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

