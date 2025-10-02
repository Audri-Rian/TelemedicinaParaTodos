# 🚀 Configuração de Deploy - TelemedicinaParaTodos

## 📋 Visão Geral

Este documento contém instruções completas para configurar e fazer deploy do sistema de telemedicina em diferentes ambientes de produção, com configurações dinâmicas via variáveis de ambiente.

## 🏗️ Arquitetura do Sistema

### Stack Tecnológica
- **Backend**: Laravel 12 + PHP 8.2+
- **Frontend**: Vue.js 3 + Inertia.js + TypeScript
- **Build**: Vite + TailwindCSS
- **Autenticação**: Laravel Sanctum (tokens) + Sessões (legacy)
- **Database**: MySQL/PostgreSQL/SQLite
- **Real-time**: Laravel Reverb (WebSockets)
- **Email**: SMTP/SES/Postmark
- **Cache**: Redis/Memcached

## 🔧 Variáveis de Ambiente

### Arquivo `.env` de Produção

```bash
# ===========================================
# CONFIGURAÇÕES BÁSICAS DA APLICAÇÃO
# ===========================================
APP_NAME="TelemedicinaParaTodos"
APP_ENV=production
APP_KEY=base64:SUA_CHAVE_AQUI
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_URL=https://seu-dominio.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en

# ===========================================
# CONFIGURAÇÕES DE BANCO DE DADOS
# ===========================================
DB_CONNECTION=mysql
DB_HOST=seu-host-mysql.com
DB_PORT=3306
DB_DATABASE=telemedicina_prod
DB_USERNAME=usuario_db
DB_PASSWORD=senha_super_segura
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# ===========================================
# CONFIGURAÇÕES DE CACHE E SESSÃO
# ===========================================
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# ===========================================
# CONFIGURAÇÕES DE REDIS
# ===========================================
REDIS_CLIENT=phpredis
REDIS_HOST=seu-redis-host.com
REDIS_PASSWORD=senha_redis
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# ===========================================
# CONFIGURAÇÕES DE EMAIL
# ===========================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=senha_app_gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seu-dominio.com
MAIL_FROM_NAME="TelemedicinaParaTodos"

# ===========================================
# CONFIGURAÇÕES DE BROADCASTING (WebSockets)
# ===========================================
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=seu-app-id
REVERB_APP_KEY=seu-app-key
REVERB_APP_SECRET=seu-app-secret
REVERB_HOST=seu-dominio.com
REVERB_PORT=443
REVERB_SCHEME=https

# ===========================================
# CONFIGURAÇÕES DE FILAS
# ===========================================
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database-uuids

# ===========================================
# CONFIGURAÇÕES DE LOGS
# ===========================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ===========================================
# CONFIGURAÇÕES DE ARMAZENAMENTO
# ===========================================
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ===========================================
# CONFIGURAÇÕES DE SEGURANÇA
# ===========================================
SANCTUM_STATEFUL_DOMAINS=seu-dominio.com,www.seu-dominio.com
SESSION_DOMAIN=.seu-dominio.com
```

## 📋 Checklist de Deploy

### ✅ Pré-Deploy

#### 1. Preparação do Ambiente
- [ ] Servidor configurado com PHP 8.2+
- [ ] Composer instalado
- [ ] Node.js 18+ e npm instalados
- [ ] MySQL/PostgreSQL configurado
- [ ] Redis configurado
- [ ] SSL/TLS configurado
- [ ] Domínio apontando para o servidor

#### 2. Configuração do Servidor Web
- [ ] Nginx/Apache configurado
- [ ] PHP-FPM configurado
- [ ] Permissões de arquivos corretas
- [ ] Document root apontando para `/public`
- [ ] Configuração de SSL
- [ ] Headers de segurança configurados

#### 3. Banco de Dados
- [ ] Banco de dados criado
- [ ] Usuário com permissões adequadas
- [ ] Backup do banco atual (se houver)
- [ ] Configurações de conexão testadas

### ✅ Deploy

#### 1. Código
```bash
# 1. Clonar repositório
git clone https://github.com/seu-usuario/TelemedicinaParaTodos.git
cd TelemedicinaParaTodos

# 2. Instalar dependências PHP
composer install --optimize-autoloader --no-dev

# 3. Instalar dependências Node.js
npm ci

# 4. Build dos assets
npm run build

# 5. Configurar arquivo .env
cp .env.example .env
# Editar .env com as configurações de produção
```

#### 2. Configuração Laravel
```bash
# 1. Gerar chave da aplicação
php artisan key:generate

# 2. Limpar e otimizar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Executar migrations
php artisan migrate --force

# 4. Executar seeders (se necessário)
php artisan db:seed --force

# 5. Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

#### 3. Configuração de Permissões
```bash
# Definir permissões corretas
sudo chown -R www-data:www-data /var/www/telemedicina
sudo chmod -R 755 /var/www/telemedicina
sudo chmod -R 775 /var/www/telemedicina/storage
sudo chmod -R 775 /var/www/telemedicina/bootstrap/cache
```

### ✅ Pós-Deploy

#### 1. Testes de Funcionamento
- [ ] Aplicação carregando corretamente
- [ ] Login funcionando (sistema web)
- [ ] API funcionando (sistema de tokens)
- [ ] Banco de dados conectado
- [ ] Emails sendo enviados
- [ ] WebSockets funcionando
- [ ] Upload de arquivos funcionando

#### 2. Monitoramento
- [ ] Logs configurados
- [ ] Monitoramento de performance
- [ ] Backup automático configurado
- [ ] Alertas de erro configurados

#### 3. Segurança
- [ ] Firewall configurado
- [ ] Rate limiting ativo
- [ ] Headers de segurança
- [ ] SSL/TLS funcionando
- [ ] Tokens de autenticação funcionando

## 🌐 Configurações por Ambiente

### Desenvolvimento
```bash
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
CACHE_STORE=file
SESSION_DRIVER=file
MAIL_MAILER=log
BROADCAST_CONNECTION=log
```

### Staging
```bash
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.telemedicina.com
DB_CONNECTION=mysql
CACHE_STORE=redis
SESSION_DRIVER=redis
MAIL_MAILER=smtp
BROADCAST_CONNECTION=reverb
```

### Produção
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://telemedicina.com
DB_CONNECTION=mysql
CACHE_STORE=redis
SESSION_DRIVER=redis
MAIL_MAILER=smtp
BROADCAST_CONNECTION=reverb
```

## 🔧 Configurações Específicas

### Nginx Configuration
```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name seu-dominio.com www.seu-dominio.com;
    root /var/www/telemedicina/public;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Laravel Configuration
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # WebSocket Support
    location /ws {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com
    ServerAlias www.seu-dominio.com
    DocumentRoot /var/www/telemedicina/public

    <Directory /var/www/telemedicina/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "no-referrer-when-downgrade"

    # WebSocket Support
    ProxyPreserveHost On
    ProxyPass /ws ws://127.0.0.1:8080/ws
    ProxyPassReverse /ws ws://127.0.0.1:8080/ws
</VirtualHost>
```

## 🚀 Scripts de Deploy

### Deploy Automático (deploy.sh)
```bash
#!/bin/bash

# Configurações
PROJECT_DIR="/var/www/telemedicina"
BACKUP_DIR="/var/backups/telemedicina"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🚀 Iniciando deploy do TelemedicinaParaTodos..."

# 1. Backup do banco de dados
echo "📦 Fazendo backup do banco de dados..."
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $BACKUP_DIR/backup_$DATE.sql

# 2. Backup dos arquivos
echo "📦 Fazendo backup dos arquivos..."
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $PROJECT_DIR

# 3. Atualizar código
echo "📥 Atualizando código..."
cd $PROJECT_DIR
git pull origin main

# 4. Instalar dependências
echo "📦 Instalando dependências..."
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# 5. Configurar Laravel
echo "⚙️ Configurando Laravel..."
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 6. Reiniciar serviços
echo "🔄 Reiniciando serviços..."
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart redis

echo "✅ Deploy concluído com sucesso!"
```

### Rollback (rollback.sh)
```bash
#!/bin/bash

# Configurações
PROJECT_DIR="/var/www/telemedicina"
BACKUP_DIR="/var/backups/telemedicina"

echo "🔄 Iniciando rollback..."

# Listar backups disponíveis
echo "📦 Backups disponíveis:"
ls -la $BACKUP_DIR

# Solicitar backup para restaurar
read -p "Digite a data do backup (YYYYMMDD_HHMMSS): " BACKUP_DATE

# Restaurar banco de dados
echo "📦 Restaurando banco de dados..."
mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < $BACKUP_DIR/backup_$BACKUP_DATE.sql

# Restaurar arquivos
echo "📦 Restaurando arquivos..."
tar -xzf $BACKUP_DIR/files_$BACKUP_DATE.tar.gz -C /

# Reiniciar serviços
echo "🔄 Reiniciando serviços..."
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm

echo "✅ Rollback concluído com sucesso!"
```

## 📊 Monitoramento

### Logs Importantes
```bash
# Logs da aplicação
tail -f /var/www/telemedicina/storage/logs/laravel.log

# Logs do Nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Logs do PHP-FPM
tail -f /var/log/php8.2-fpm.log

# Logs do Redis
tail -f /var/log/redis/redis-server.log
```

### Comandos de Monitoramento
```bash
# Status dos serviços
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status redis
sudo systemctl status mysql

# Uso de recursos
htop
df -h
free -h

# Conexões de banco
mysql -u root -p -e "SHOW PROCESSLIST;"

# Conexões Redis
redis-cli info clients
```

## 🔒 Segurança

### Checklist de Segurança
- [ ] SSL/TLS configurado e funcionando
- [ ] Firewall configurado (portas 80, 443, 22)
- [ ] Senhas fortes para todos os serviços
- [ ] Backup automático configurado
- [ ] Logs de segurança monitorados
- [ ] Rate limiting ativo
- [ ] Headers de segurança configurados
- [ ] Tokens de autenticação seguros
- [ ] Upload de arquivos validado
- [ ] SQL injection prevenido

### Comandos de Segurança
```bash
# Verificar SSL
openssl s_client -connect seu-dominio.com:443

# Verificar headers de segurança
curl -I https://seu-dominio.com

# Verificar configurações do PHP
php -m | grep -E "(openssl|curl|gd|mbstring|pdo_mysql)"

# Verificar permissões
find /var/www/telemedicina -type f -perm 777
```

## 🆘 Troubleshooting

### Problemas Comuns

#### 1. Erro 500 - Internal Server Error
```bash
# Verificar logs
tail -f /var/www/telemedicina/storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# Verificar permissões
sudo chown -R www-data:www-data /var/www/telemedicina
sudo chmod -R 755 /var/www/telemedicina
sudo chmod -R 775 /var/www/telemedicina/storage
```

#### 2. Erro de Conexão com Banco
```bash
# Testar conexão
php artisan tinker
DB::connection()->getPdo();

# Verificar configurações
php artisan config:show database
```

#### 3. Assets não Carregando
```bash
# Rebuild dos assets
npm run build

# Verificar permissões
sudo chown -R www-data:www-data /var/www/telemedicina/public
```

#### 4. WebSockets não Funcionando
```bash
# Verificar se o Reverb está rodando
php artisan reverb:start

# Verificar configurações
php artisan config:show broadcasting
```

## 📞 Suporte

### Contatos
- **Desenvolvedor**: [Seu Nome] - [seu-email@exemplo.com]
- **Infraestrutura**: [Email da equipe de infra]
- **Emergência**: [Telefone de emergência]

### Documentação Relacionada
- [📋 Regras do Sistema](../requirements/SystemRules.md)
- [🔐 Autenticação](../modules/auth/RegistrationLogic.md)
- [📅 Agendamentos](../modules/appointments/AppointmentsImplementationStudy.md)
- [🏗️ Arquitetura](../Architecture/Arquitetura.md)

---

**Última atualização**: $(date)
**Versão**: 1.0.0
**Ambiente**: Produção
