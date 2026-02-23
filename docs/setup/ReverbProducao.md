# Reverb em Produção (EC2)

O **Laravel Reverb** é um servidor WebSocket que roda **em processo separado** da aplicação Laravel. Em produção na EC2 você precisa configurá-lo e mantê-lo rodando junto com a app.

## Resumo rápido

| O quê | Onde |
|-------|------|
| **Laravel (PHP)** | Nginx/Apache + PHP-FPM (ou `php artisan serve` só em dev) |
| **Reverb (WebSocket)** | Processo separado: `php artisan reverb:start`, mantido pelo **Supervisor** |
| **Frontend** | Conecta em `wss://seu-dominio.com` na porta configurada (ex.: 443) |

Sem Reverb configurado, a aplicação continua funcionando; apenas notificações em tempo real, mensagens ao vivo e atualizações de agendamento ficam desativadas (comportamento já tratado no frontend).

---

## 1. Variáveis de ambiente na EC2 (.env)

No `.env` de **produção** na sua instância EC2:

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb — use valores seguros em produção (não deixe vazios se for usar WebSocket)
REVERB_APP_ID=telemedicina-prod
REVERB_APP_KEY=uma-chave-secreta-longa-e-aleatoria
REVERB_APP_SECRET=outro-secret-longo-e-aleatorio

# Host/porta que o navegador vai usar para conectar (geralmente mesmo domínio da app, porta 443)
REVERB_HOST=seu-dominio.com
REVERB_PORT=443
REVERB_SCHEME=https
```

- **REVERB_HOST**: mesmo domínio da aplicação (ex.: `telemedicina.seudominio.com`).
- **REVERB_PORT=443** e **REVERB_SCHEME=https**: em produção o tráfego WebSocket costuma ir por HTTPS (443); o Nginx faz o proxy para a porta interna do Reverb (ex.: 8080).

Gere valores seguros para `REVERB_APP_KEY` e `REVERB_APP_SECRET` (ex.: `php artisan str:random 64` ou um gerador de senhas).

---

## 2. Rodar Reverb como processo contínuo (Supervisor)

O Reverb precisa ficar rodando o tempo todo. Na EC2 o padrão é usar **Supervisor** para subir e reiniciar o processo.

### 2.1. Instalar Supervisor (se ainda não tiver)

```bash
# Amazon Linux 2 / RHEL
sudo yum install -y supervisor

# Ubuntu/Debian
sudo apt-get update && sudo apt-get install -y supervisor
```

### 2.2. Configuração do Reverb no Supervisor

Crie um arquivo de configuração para o Reverb, por exemplo:

**`/etc/supervisor/conf.d/laravel-reverb.conf`** (Linux)  
ou **`/etc/supervisord.d/laravel-reverb.ini`** (Amazon Linux):

```ini
[program:laravel-reverb]
process_name=%(program_name)s
command=php /var/www/html/artisan reverb:start
autostart=true
autorestart=true
user=nginx
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/reverb.log
stopwaitsecs=3600
```

Ajuste:

- **`/var/www/html`** para o caminho real da sua aplicação na EC2.
- **`user=nginx`** para o usuário que roda a app (pode ser `www-data` em Ubuntu).

Recarregar e iniciar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-reverb
sudo supervisorctl status
```

A partir daí o Supervisor mantém o Reverb rodando e reinicia em caso de queda.

---

## 3. Nginx: proxy WebSocket para o Reverb

O Reverb, por padrão, escuta em uma porta interna (ex.: **8080** ou a definida em `config/reverb.php`). O navegador não acessa essa porta diretamente; acessa o mesmo domínio (HTTPS 443) e o Nginx encaminha o tráfego WebSocket para o Reverb.

Exemplo de bloco no **Nginx** (dentro do `server` que já atende o Laravel):

```nginx
# Proxy WebSocket para Laravel Reverb
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Ajuste **`proxy_pass http://127.0.0.1:8080`** para a porta em que o Reverb está escutando na EC2 (veja em `config/reverb.php`: `REVERB_SERVER_PORT` ou padrão do Reverb).

Depois:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

No frontend, o **HandleInertiaRequests** já envia para o navegador `host`, `port` e `scheme` a partir do `.env`. Com `REVERB_HOST=seu-dominio.com`, `REVERB_PORT=443` e `REVERB_SCHEME=https`, a conexão WebSocket será feita para `wss://seu-dominio.com` e o Nginx encaminhará para o processo Reverb.

---

## 4. Porta em que o Reverb escuta (reverb.php)

O Reverb usa **config/reverb.php** para servidor e **config/broadcasting.php** para o que o Laravel envia ao frontend.

- **Servidor (processo `reverb:start`):** em `config/reverb.php` a porta do servidor é algo como `REVERB_SERVER_PORT` (ex.: 8090). Pode manter uma porta interna (ex.: 8080 ou 8090) e fazer o Nginx fazer proxy da 443 para ela.
- **Cliente (navegador):** em produção use `REVERB_HOST`, `REVERB_PORT=443`, `REVERB_SCHEME=https` no `.env`; o frontend usa esses valores para conectar em `wss://seu-dominio.com`.

Ou seja: Reverb escuta em uma porta interna; Nginx expõe em 443; `.env` de produção diz ao frontend para usar 443 e HTTPS.

---

## 5. Múltiplas EC2 (ALB / balanceador)

Se você tiver **várias instâncias** atrás de um Application Load Balancer (ALB):

- **Sticky sessions** são necessárias para WebSocket: o mesmo cliente deve sempre cair na mesma instância durante a conexão. Ative sticky sessions no target group do ALB (cookie-based).
- O documento **docs/aws/CloudScalabilityStrategy2.md** já descreve isso (“Sticky Sessions: Cookie-based (necessário para Reverb)”).

Com **uma única EC2**, não é preciso configurar sticky sessions; só Nginx + Supervisor + `.env` já resolvem.

---

## 6. Resumo do fluxo em produção (1 EC2)

1. **.env** na EC2: `BROADCAST_CONNECTION=reverb`, `REVERB_APP_*`, `REVERB_HOST`, `REVERB_PORT=443`, `REVERB_SCHEME=https`.
2. **Supervisor**: processo `php artisan reverb:start` rodando (ex.: `laravel-reverb`), log em `storage/logs/reverb.log`.
3. **Nginx**: proxy WebSocket (ex.: `/app/` ou o path que o cliente Reverb usar) para `http://127.0.0.1:PORTA_REVERB`.
4. **Laravel**: mesma aplicação já servida pelo Nginx; o frontend recebe `reverb.key`, `reverb.host`, etc. via Inertia e conecta em `wss://seu-dominio.com`.

Assim, o Reverb fica configurado **separadamente** como processo, mas integrado ao mesmo domínio e ao mesmo `.env` da aplicação em produção na EC2.

Para mais detalhes de scripts de deploy (parar/iniciar Reverb com Supervisor), veja **docs/aws/CloudScalabilityStrategy2.md** (trechos com `supervisorctl` e Reverb).
