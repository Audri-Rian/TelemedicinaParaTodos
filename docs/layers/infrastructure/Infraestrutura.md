# Documentação Técnica — Infraestrutura Atual do TelemedicinaParaTodos

*Documento em: `docs/layers/infrastructure/` (Camada de Infraestrutura)*

## 🧭 Visão Geral da Arquitetura

O projeto TelemedicinaParaTodos está hospedado em uma instância Amazon EC2 Ubuntu 24.04 LTS, utilizando Nginx como servidor web e Laravel como backend principal. O domínio público é gerenciado via Cloudflare DNS e aponta diretamente para o IP público da instância.

## ☁️ Infraestrutura AWS

### EC2

- **Tipo**: t3.micro
- **Sistema Operacional**: Ubuntu 24.04 (Noble)
- **Região**: us-east-1
- **IP Público**: 35.175.200.28
- **DNS Público**: ec2-35-175-200-28.compute-1.amazonaws.com
- **VPC privada**: IP interno 172.31.67.92

### 🔐 Security Group (Inbound)

| Serviço | Porta | Origem   |
|---------|-------|----------|
| SSH     | 22    | IP restrito |
| HTTP    | 80    | 0.0.0.0/0 |
| HTTPS   | 443   | 0.0.0.0/0 |

## 🌐 Domínio e DNS (Cloudflare)

### Registros DNS

| Tipo | Nome | Conteúdo |
|------|------|----------|
| A    | telemedicinaparatodos.com | 35.175.200.28 |
| A    | www.telemedicinaparatodos.com | 35.175.200.28 |

➡ O domínio aponta diretamente para o EC2 (modo DNS-only ou proxy Cloudflare opcional).

## 🧱 Servidor Web (Nginx)

### Configuração principal

```nginx
server {
    listen 80;
    server_name telemedicinaparatodos.com www.telemedicinaparatodos.com;

    root /var/www/TelemedicinaParaTodos/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 📌 Explicação

- Laravel roda com document root no `/public`
- Reescrita automática para index.php
- PHP processado via PHP-FPM 8.3
- Arquivos sensíveis protegidos

## ⚙️ Stack Backend

### Laravel

- **Projeto clonado em**: `/var/www/TelemedicinaParaTodos`
- Estrutura padrão Laravel: `app/`, `routes/`, `resources/`, `database/`, `public/`
- Configuração via `.env`
- Permissões ajustadas em: `storage/`, `bootstrap/cache/`

## 🎨 Frontend (Vite + Node.js)

### Build Assets

- Frontend compilado com: `npm run build`
- Manifest gerado em: `public/build/manifest.json`

## 🚀 Deploy Workflow Atual

1. Desenvolvimento local
2. Build frontend local (`npm run build`)
3. Envio do projeto para EC2 via SCP ou Git Clone
4. Backend rodando via Nginx + PHP-FPM
5. DNS apontando para IP público

## 🔐 Acesso SSH

Conexão via chave PEM:

```bash
ssh -i telemedicine-key.pem ubuntu@35.175.200.28
```

## 📡 Disponibilidade Pública

| URL | Status |
|-----|--------|
| http://telemedicinaparatodos.com | Público |
| http://www.telemedicinaparatodos.com | Público |
| HTTPS | Preparado (porta 443 aberta, pronto para SSL) |

## 🧩 Boas Práticas já aplicadas

- ✔ Document root isolado (`public/`)
- ✔ PHP-FPM separado do Nginx
- ✔ DNS profissional via Cloudflare
- ✔ EC2 com Security Group controlado
- ✔ Build frontend separado de backend
- ✔ Laravel em modo servidor real (não artisan serve)

## 🚀 Próximos passos recomendados (produção enterprise)

### Infraestrutura

- Let's Encrypt SSL (Certbot)
- HTTP/2 e Gzip
- Elastic IP (evitar IP mudar)
- Auto-renew SSL

### Laravel

- OPcache
- Queue Worker (Supervisor)
- Redis Cache
- Horizon (se usar jobs)

### DevOps

- CI/CD (GitHub Actions)
- Zero-downtime deploy (Envoy/Deployer)
- Backup automático
- Monitoring (CloudWatch + UptimeRobot)
