# Guia de Transição: Database → Redis

Este documento fornece instruções completas para migrar o projeto **TelemedicinaParaTodos** de armazenamento em banco de dados (database) para Redis em Cache, Sessions e Queue.

## 📋 Índice

1. [Pré-requisitos](#pré-requisitos)
2. [Instalação e Configuração do Redis](#instalação-e-configuração-do-redis)
3. [Configuração do PHP](#configuração-do-php)
4. [Configuração do Laravel](#configuração-do-laravel)
5. [Migração Gradual](#migração-gradual)
6. [Configuração do Ambiente](#configuração-do-ambiente)
7. [Checklist de Testes](#checklist-de-testes)
8. [Scripts de Teste](#scripts-de-teste)
9. [Estratégia de Migração](#estratégia-de-migração)
10. [Troubleshooting](#troubleshooting)
11. [Próximos Passos](#próximos-passos)

---

## 🔧 Pré-requisitos

### Software Necessário

- **Redis 7+** instalado e rodando
- **PHP 8.2+** com extensão `redis` ou `phpredis` habilitada
- **Composer** atualizado
- **Laravel 12+** com suporte a Redis

### Verificar Extensão PHP

```bash
php -m | grep redis
```

Se não estiver instalada, veja a seção [Configuração do PHP](#configuração-do-php).

---

## 🗄️ Instalação e Configuração do Redis

### 1. Instalar Redis

#### Windows

**Opção 1: Docker (Recomendado)**
```bash
docker run -d -p 6379:6379 --name redis redis:7-alpine
```

**Opção 2: Memurai (Redis para Windows)**
- Baixe do site oficial: https://www.memurai.com/
- Instale e inicie o serviço
- Por padrão, roda na porta `6379`

**Opção 3: WSL2 (Recomendado para desenvolvimento)**
```bash
# Instalar WSL2
wsl --install

# No WSL, instalar Redis
sudo apt update
sudo apt install redis-server
sudo service redis-server start
```

#### Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

#### macOS

```bash
brew install redis
brew services start redis
```

### 2. Verificar Instalação

```bash
# Verificar se Redis está rodando
redis-cli ping
# Deve retornar: PONG

# Ou verificar porta
netstat -an | grep 6379
# Ou no Windows:
netstat -an | findstr 6379
```

### 3. Configuração Básica do Redis

**Linux/macOS:**
```bash
# Editar arquivo de configuração
sudo nano /etc/redis/redis.conf
# Ou no macOS:
nano /usr/local/etc/redis.conf

# Verificar configurações importantes:
# bind 127.0.0.1 (apenas localhost para desenvolvimento)
# port 6379
# requirepass (opcional, para senha)
```

**Windows (Docker):**
```bash
# Redis já vem configurado, mas você pode personalizar:
docker run -d -p 6379:6379 --name redis \
  -v redis_data:/data \
  redis:7-alpine redis-server --appendonly yes
```

---

## 🔨 Configuração do PHP

### 1. Instalar Extensão Redis

#### Windows

**Opção 1: Usar extensão pré-compilada**
1. Baixe a extensão de: https://pecl.php.net/package/redis
2. Extraia `php_redis.dll` para a pasta `ext` do PHP
3. Edite `php.ini` e adicione: `extension=redis`
4. Reinicie o servidor web

**Opção 2: Usar XAMPP/WAMP com extensão**
- Baixe a extensão compatível com sua versão do PHP
- Coloque na pasta `ext` e ative no `php.ini`

#### Linux (Ubuntu/Debian)

```bash
sudo apt-get install php-redis
sudo systemctl restart php8.2-fpm  # ou sua versão do PHP
```

#### macOS

```bash
pecl install redis
# Ou via Homebrew:
brew install php-redis
```

### 2. Verificar Instalação

```bash
php -m | grep redis
# Deve mostrar: redis

php -i | grep redis
# Deve mostrar informações da extensão
```

### 3. Testar Conexão

```bash
php artisan tinker
>>> Redis::connection()->ping()
# Deve retornar: "PONG"
```

---

## ⚙️ Configuração do Laravel

### 1. Verificar Configurações Atuais

O Laravel já vem com suporte a Redis configurado. Verifique os arquivos:

- `config/database.php` - Configuração do Redis
- `config/cache.php` - Configuração do Cache
- `config/session.php` - Configuração das Sessions
- `config/queue.php` - Configuração da Queue

### 2. Estrutura de Databases Redis

O Laravel usa databases separados do Redis para diferentes propósitos:

- **Database 0 (default)**: Queue, Sessions, Broadcasting
- **Database 1 (cache)**: Cache

Isso é configurado em `config/database.php`:

```php
'redis' => [
    'default' => [
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

---

## 🔄 Migração Gradual

### Estratégia de Migração em 3 Etapas

Recomendamos migrar gradualmente para minimizar riscos:

1. **Etapa 1: Cache** (Sem impacto no usuário)
2. **Etapa 2: Queue** (Processar jobs pendentes antes)
3. **Etapa 3: Sessions** (Usuários precisarão fazer login novamente)

### Etapa 1: Migrar Cache

#### 1.1. Processar Jobs Pendentes (se houver)

```bash
# Verificar se há jobs pendentes
php artisan queue:work database --once

# Ou processar todos os jobs pendentes
php artisan queue:work database --stop-when-empty
```

#### 1.2. Atualizar `.env`

```env
# Cache
CACHE_STORE=redis

# Redis (já configurado)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

#### 1.3. Limpar Cache

```bash
php artisan config:clear
php artisan cache:clear
```

#### 1.4. Testar Cache

```bash
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
# Deve retornar: "value"

>>> Cache::store('redis')->put('test2', 'value2', 60)
>>> Cache::store('redis')->get('test2')
# Deve retornar: "value2"
```

#### 1.5. Verificar Redis

```bash
redis-cli
> SELECT 1
> KEYS *
# Deve mostrar as chaves de cache
> GET laravel_cache:test
# Deve retornar o valor do cache
```

### Etapa 2: Migrar Queue

#### 2.1. Processar Todos os Jobs Pendentes

```bash
# Processar todos os jobs pendentes no banco
php artisan queue:work database --stop-when-empty

# Verificar se há jobs falhos
php artisan queue:failed
```

#### 2.2. Atualizar `.env`

```env
# Queue
QUEUE_CONNECTION=redis
```

#### 2.3. Limpar Cache de Configuração

```bash
php artisan config:clear
```

#### 2.4. Testar Queue

```bash
# Criar um job de teste
php artisan tinker
>>> dispatch(new \App\Jobs\TestJob());
# Ou criar um job simples:
>>> \Illuminate\Support\Facades\Queue::push('test', ['data' => 'test']);

# Processar jobs
php artisan queue:work redis --once
```

#### 2.5. Verificar Redis

```bash
redis-cli
> SELECT 0
> KEYS *
# Deve mostrar as chaves de queue
> LLEN queues:default
# Deve mostrar o número de jobs na fila
```

#### 2.6. Atualizar Scripts de Desenvolvimento

Se você usa scripts como `composer dev`, atualize para usar Redis:

```json
"dev": [
    "php artisan serve",
    "php artisan queue:work redis --tries=1",
    "npm run dev"
]
```

### Etapa 3: Migrar Sessions

#### 3.1. Avisar Usuários (se em produção)

⚠️ **IMPORTANTE**: Migrar sessions vai deslogar todos os usuários ativos. Em desenvolvimento, isso é aceitável.

#### 3.2. Atualizar `.env`

```env
# Sessions
SESSION_DRIVER=redis
```

#### 3.3. Limpar Cache de Configuração

```bash
php artisan config:clear
php artisan cache:clear
```

#### 3.4. Testar Sessions

```bash
# Fazer login na aplicação
# Verificar se a sessão está sendo salva no Redis

redis-cli
> SELECT 0
> KEYS *session*
# Deve mostrar as chaves de sessão
```

#### 3.5. Verificar Funcionamento

1. Fazer login na aplicação
2. Verificar se a sessão persiste após refresh
3. Verificar se o logout funciona corretamente
4. Testar em múltiplos navegadores/dispositivos

---

## 📝 Configuração do Ambiente

### 1. Arquivo `.env` Completo

#### Desenvolvimento Local

```env
APP_NAME="Telemedicina Para Todos"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=telemedicina_para_todos
DB_USERNAME=telemedicina_user
DB_PASSWORD=secret

# Cache - Redis
CACHE_STORE=redis

# Sessions - Redis
SESSION_DRIVER=redis

# Queue - Redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Broadcasting - Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SCALING_ENABLED=true

# Filesystem
FILESYSTEM_DISK=local
```

#### Produção AWS

```env
APP_NAME="Telemedicina Para Todos"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://telemedicina.example.com

# Database - RDS
DB_CONNECTION=pgsql
DB_HOST=telemedicina-db.xxxxx.us-east-1.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=telemedicina_prod
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

# Cache - ElastiCache Redis
CACHE_STORE=redis

# Sessions - ElastiCache Redis
SESSION_DRIVER=redis

# Queue - ElastiCache Redis
QUEUE_CONNECTION=redis

# Redis - ElastiCache
REDIS_HOST=telemedicina-cache.xxxxx.0001.use1.cache.amazonaws.com
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Broadcasting - Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=${REVERB_APP_ID}
REVERB_APP_KEY=${REVERB_APP_KEY}
REVERB_APP_SECRET=${REVERB_APP_SECRET}
REVERB_HOST=telemedicina.example.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SCALING_ENABLED=true

# Filesystem - S3
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=telemedicina-files-prod
AWS_USE_PATH_STYLE_ENDPOINT=false

# CloudFront
CLOUDFRONT_URL=https://xxxxx.cloudfront.net
```

### 2. Docker Compose (Opcional)

Crie um arquivo `docker-compose.yml` para desenvolvimento:

```yaml
version: '3.8'

services:
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: redis-server --appendonly yes
    restart: unless-stopped

  # Opcional: PostgreSQL também
  postgres:
    image: postgres:15-alpine
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: telemedicina_para_todos
      POSTGRES_USER: telemedicina_user
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: unless-stopped

volumes:
  redis_data:
  postgres_data:
```

Iniciar serviços:
```bash
docker-compose up -d
```

### 3. Limpar Cache Após Configuração

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## ✅ Checklist de Testes

Execute todos os testes abaixo para garantir que a migração foi bem-sucedida.

### 🔐 Autenticação e Sessions

- [ ] **Login de Usuário (Paciente)**
  - Fazer login como paciente
  - Verificar se a sessão persiste após refresh
  - Verificar se o logout funciona corretamente
  - Verificar se a sessão expira após o tempo definido
  - Navegar pelas páginas autenticadas (dashboard, appointments, etc.)
  - Verificar se os dados do usuário são mantidos na sessão

- [ ] **Login de Médico**
  - Fazer login como médico
  - Verificar se a sessão persiste
  - Verificar se o logout funciona
  - Testar em múltiplos navegadores
  - Navegar pelas páginas do médico (dashboard, appointments, consultations, etc.)
  - Verificar se os dados do médico são mantidos na sessão

- [ ] **Sessões Múltiplas**
  - Fazer login em múltiplos dispositivos
  - Verificar se as sessões são independentes
  - Verificar se o logout em um dispositivo não afeta o outro
  - Testar em navegadores diferentes (Chrome, Firefox, Edge)
  - Testar em dispositivos diferentes (desktop, mobile)

- [ ] **Expiração de Sessão**
  - Verificar se a sessão expira após `SESSION_LIFETIME` minutos
  - Verificar se o usuário é deslogado após expiração
  - Verificar se o usuário precisa fazer login novamente

- [ ] **Verificar Redis**
  ```bash
  redis-cli
  > SELECT 0
  > KEYS *session*
  > GET laravel_database_session:xxxxx
  > TTL laravel_database_session:xxxxx
  # Verificar tempo de expiração da sessão
  ```

### 💾 Cache

- [ ] **Cache Básico**
  ```bash
  php artisan tinker
  >>> Cache::put('test', 'value', 60)
  >>> Cache::get('test')
  # Deve retornar: "value"
  >>> Cache::forget('test')
  >>> Cache::get('test')
  # Deve retornar: null
  ```

- [ ] **Cache de Configuração**
  ```bash
  # Criar cache de configuração
  php artisan config:cache
  
  # Limpar cache de configuração
  php artisan config:clear
  
  # Verificar se a configuração está sendo cacheada
  php artisan config:show cache
  ```

- [ ] **Cache de Rotas**
  ```bash
  # Criar cache de rotas
  php artisan route:cache
  
  # Limpar cache de rotas
  php artisan route:clear
  
  # Listar rotas (deve usar cache se estiver ativado)
  php artisan route:list
  ```

- [ ] **Cache de Views**
  ```bash
  # Criar cache de views
  php artisan view:cache
  
  # Limpar cache de views
  php artisan view:clear
  
  # Verificar se as views estão sendo cacheadas
  ```

- [ ] **Cache de Dados do Projeto**
  ```bash
  # Testar cache de dados de appointments
  php artisan tinker
  >>> $appointment = App\Models\Appointments::first();
  >>> Cache::put("appointment:{$appointment->id}", $appointment, 60);
  >>> Cache::get("appointment:{$appointment->id}");
  
  # Testar cache de dados de médicos
  >>> $doctor = App\Models\Doctor::first();
  >>> Cache::put("doctor:{$doctor->id}", $doctor, 60);
  >>> Cache::get("doctor:{$doctor->id}");
  ```

- [ ] **Cache Tags (se disponível)**
  ```bash
  # Testar cache com tags
  php artisan tinker
  >>> Cache::tags(['appointments', 'doctors'])->put('test', 'value', 60);
  >>> Cache::tags(['appointments'])->get('test');
  >>> Cache::tags(['appointments'])->flush();
  ```

- [ ] **Verificar Redis**
  ```bash
  redis-cli
  > SELECT 1
  > KEYS *
  > GET laravel_cache:test
  > TTL laravel_cache:test
  # Verificar tempo de expiração do cache
  > DBSIZE
  # Verificar número de chaves no database de cache
  ```

### 📬 Queue e Jobs

- [ ] **Despachar Job**
  ```bash
  php artisan tinker
  >>> dispatch(new \App\Jobs\TestJob());
  # Ou criar um job simples:
  >>> \Illuminate\Support\Facades\Queue::push('test', ['data' => 'test']);
  ```

- [ ] **Processar Jobs**
  ```bash
  # Processar um job de cada vez
  php artisan queue:work redis --once
  
  # Processar todos os jobs pendentes
  php artisan queue:work redis --stop-when-empty
  
  # Processar jobs em background
  php artisan queue:work redis --tries=3 --timeout=90 --daemon
  ```

- [ ] **Verificar Queue**
  ```bash
  redis-cli
  > SELECT 0
  > LLEN queues:default
  > LRANGE queues:default 0 -1
  > KEYS queues:*
  ```

- [ ] **Jobs Falhos**
  ```bash
  # Listar jobs falhos
  php artisan queue:failed
  
  # Retry todos os jobs falhos
  php artisan queue:retry all
  
  # Retry um job específico
  php artisan queue:retry {job-id}
  
  # Deletar um job falho
  php artisan queue:forget {job-id}
  
  # Limpar todos os jobs falhos
  php artisan queue:flush
  ```

- [ ] **Queue Worker**
  ```bash
  # Iniciar worker
  php artisan queue:work redis --tries=3 --timeout=90
  
  # Verificar se está processando jobs
  # Parar com Ctrl+C
  
  # Iniciar worker em background (usando Supervisor em produção)
  php artisan queue:work redis --tries=3 --timeout=90 --daemon
  ```

- [ ] **Testar com Eventos do Projeto**
  ```bash
  # Testar eventos que podem usar queue
  php artisan tinker
  >>> $appointment = App\Models\Appointments::first();
  >>> event(new App\Events\AppointmentStatusChanged($appointment));
  # Verificar se o evento foi processado via queue
  ```

### 🔄 Broadcasting e Reverb

- [ ] **Conexão WebSocket**
  - Verificar se o Reverb está rodando
  - Conectar via WebSocket
  - Verificar se as mensagens são recebidas

- [ ] **Scaling com Redis**
  - Verificar se `REVERB_SCALING_ENABLED=true`
  - Testar com múltiplas instâncias
  - Verificar se as mensagens são sincronizadas

- [ ] **Evento AppointmentStatusChanged**
  ```bash
  # Testar evento de mudança de status de appointment
  php artisan tinker
  >>> $appointment = App\Models\Appointments::first();
  >>> $appointment->update(['status' => 'in_progress']);
  >>> event(new App\Events\AppointmentStatusChanged($appointment));
  # Verificar se o evento foi broadcastado
  ```

- [ ] **Evento RequestVideoCall**
  ```bash
  # Testar evento de requisição de vide chamada
  php artisan tinker
  >>> $user = App\Models\User::first();
  >>> event(new App\Events\RequestVideoCall($user));
  # Verificar se o evento foi broadcastado
  ```

- [ ] **Verificar Redis**
  ```bash
  redis-cli
  > SELECT 0
  > KEYS *reverb*
  > PUBSUB CHANNELS
  > PUBSUB NUMSUB appointment.*
  > PUBSUB NUMSUB video-call.*
  ```

### 📊 Performance

- [ ] **Performance do Cache**
  - Comparar tempo de resposta com e sem cache
  - Verificar se o cache está funcionando
  - Monitorar uso de memória do Redis
  ```bash
  # Testar performance do cache
  php artisan tinker
  >>> $start = microtime(true);
  >>> Cache::put('test', 'value', 60);
  >>> $time = microtime(true) - $start;
  >>> echo "Cache write: {$time}s\n";
  >>> $start = microtime(true);
  >>> Cache::get('test');
  >>> $time = microtime(true) - $start;
  >>> echo "Cache read: {$time}s\n";
  ```

- [ ] **Performance da Queue**
  - Comparar tempo de processamento
  - Verificar se os jobs são processados rapidamente
  - Monitorar número de jobs na fila
  ```bash
  # Testar performance da queue
  php artisan tinker
  >>> $start = microtime(true);
  >>> dispatch(new App\Jobs\TestJob());
  >>> $time = microtime(true) - $start;
  >>> echo "Job dispatch: {$time}s\n";
  ```

- [ ] **Performance das Sessions**
  - Verificar tempo de resposta do login
  - Verificar tempo de resposta das requisições autenticadas
  - Monitorar uso de memória do Redis
  ```bash
  # Testar performance das sessions
  # Fazer login na aplicação e medir tempo
  # Verificar tempo de resposta das requisições autenticadas
  ```

- [ ] **Performance do Broadcasting**
  - Verificar tempo de broadcast de eventos
  - Verificar tempo de recebimento de mensagens
  - Monitorar uso de memória do Redis
  ```bash
  # Testar performance do broadcasting
  php artisan tinker
  >>> $start = microtime(true);
  >>> event(new App\Events\AppointmentStatusChanged($appointment));
  >>> $time = microtime(true) - $start;
  >>> echo "Event broadcast: {$time}s\n";
  ```

### 🔍 Monitoramento

- [ ] **Monitorar Redis**
  ```bash
  redis-cli
  > INFO
  > INFO memory
  > INFO stats
  > DBSIZE
  ```

- [ ] **Monitorar Logs**
  ```bash
  tail -f storage/logs/laravel.log
  ```

- [ ] **Verificar Erros**
  ```bash
  php artisan queue:failed
  php artisan log:clear
  ```

---

## 🎯 Estratégia de Migração

### Migração em Desenvolvimento

1. **Fase 1: Preparação**
   - Instalar Redis
   - Instalar extensão PHP
   - Verificar configurações

2. **Fase 2: Migração Gradual**
   - Migrar Cache (sem impacto)
   - Migrar Queue (processar jobs antes)
   - Migrar Sessions (usuários precisam fazer login)

3. **Fase 3: Testes**
   - Executar checklist de testes
   - Verificar performance
   - Monitorar logs

### Migração em Produção

1. **Fase 1: Preparação**
   - Configurar ElastiCache Redis na AWS
   - Configurar segurança (VPC, Security Groups)
   - Testar conexão

2. **Fase 2: Migração Gradual**
   - Migrar Cache em horário de baixo tráfego
   - Migrar Queue (processar jobs antes)
   - Migrar Sessions (avisar usuários)

3. **Fase 3: Monitoramento**
   - Monitorar performance
   - Monitorar erros
   - Monitorar uso de recursos

### Rollback (Se Necessário)

Se algo der errado, você pode fazer rollback:

1. **Reverter `.env`**
   ```env
   CACHE_STORE=database
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   ```

2. **Limpar Cache**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Reiniciar Serviços**
   ```bash
   # Reiniciar queue workers
   # Reiniciar servidor web
   ```

---

## 🧪 Scripts de Teste

### Script de Teste Completo

Crie um arquivo `test-redis.php` na raiz do projeto para testar todas as funcionalidades:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testando Redis...\n\n";

// Teste 1: Conexão Redis
echo "1. Testando conexão Redis...\n";
try {
    $redis = Illuminate\Support\Facades\Redis::connection();
    $result = $redis->ping();
    echo "   ✅ Conexão Redis: OK ($result)\n";
} catch (Exception $e) {
    echo "   ❌ Erro na conexão Redis: " . $e->getMessage() . "\n";
    exit(1);
}

// Teste 2: Cache
echo "2. Testando Cache...\n";
try {
    Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
    $value = Illuminate\Support\Facades\Cache::get('test_key');
    if ($value === 'test_value') {
        echo "   ✅ Cache: OK\n";
    } else {
        echo "   ❌ Cache: Valor não corresponde\n";
    }
    Illuminate\Support\Facades\Cache::forget('test_key');
} catch (Exception $e) {
    echo "   ❌ Erro no Cache: " . $e->getMessage() . "\n";
}

// Teste 3: Queue
echo "3. Testando Queue...\n";
try {
    Illuminate\Support\Facades\Queue::push('test', ['data' => 'test']);
    echo "   ✅ Queue: Job despachado\n";
} catch (Exception $e) {
    echo "   ❌ Erro na Queue: " . $e->getMessage() . "\n";
}

// Teste 4: Sessions
echo "4. Testando Sessions...\n";
try {
    // Nota: Sessions requerem contexto HTTP, então este teste pode não funcionar completamente
    // Em um ambiente CLI, você pode testar a configuração diretamente
    $sessionDriver = config('session.driver');
    if ($sessionDriver === 'redis') {
        echo "   ✅ Sessions: Driver configurado como Redis\n";
    } else {
        echo "   ⚠️  Sessions: Driver não está configurado como Redis (atual: $sessionDriver)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro nas Sessions: " . $e->getMessage() . "\n";
}

echo "\n✅ Todos os testes concluídos!\n";
```

Execute o script:

```bash
php test-redis.php
```

### Comandos Úteis

#### Verificar Status do Redis

```bash
# Verificar se Redis está rodando
redis-cli ping

# Verificar informações do Redis
redis-cli INFO

# Verificar uso de memória
redis-cli INFO memory

# Verificar estatísticas
redis-cli INFO stats

# Verificar número de chaves
redis-cli DBSIZE

# Verificar databases
redis-cli
> SELECT 0
> DBSIZE
> SELECT 1
> DBSIZE
```

#### Limpar Redis (Cuidado!)

```bash
# Limpar database atual (cuidado!)
redis-cli
> FLUSHDB

# Limpar todos os databases (muito cuidado!)
redis-cli
> FLUSHALL

# Limpar apenas cache (database 1)
redis-cli
> SELECT 1
> FLUSHDB

# Limpar apenas queue e sessions (database 0)
redis-cli
> SELECT 0
> FLUSHDB
```

#### Monitorar Redis em Tempo Real

```bash
# Monitorar comandos em tempo real
redis-cli MONITOR

# Monitorar apenas comandos específicos
redis-cli MONITOR | grep "SET\|GET"

# Verificar pub/sub channels
redis-cli
> PUBSUB CHANNELS
> PUBSUB NUMSUB appointment.*
> PUBSUB NUMSUB video-call.*
```

#### Verificar Chaves do Redis

```bash
# Listar todas as chaves (cuidado em produção!)
redis-cli
> KEYS *

# Listar chaves com padrão
redis-cli
> KEYS laravel_cache:*
> KEYS laravel_database_session:*
> KEYS queues:*

# Contar chaves
redis-cli
> EVAL "return #redis.call('keys', 'laravel_cache:*')" 0

# Verificar TTL de uma chave
redis-cli
> TTL laravel_cache:test_key
```

#### Verificar Queue

```bash
# Verificar tamanho da queue
redis-cli
> SELECT 0
> LLEN queues:default

# Verificar jobs na queue
redis-cli
> SELECT 0
> LRANGE queues:default 0 -1

# Verificar todas as queues
redis-cli
> SELECT 0
> KEYS queues:*
```

#### Verificar Sessions

```bash
# Verificar sessions ativas
redis-cli
> SELECT 0
> KEYS laravel_database_session:*

# Verificar uma sessão específica
redis-cli
> SELECT 0
> GET laravel_database_session:xxxxx

# Verificar TTL de uma sessão
redis-cli
> SELECT 0
> TTL laravel_database_session:xxxxx
```

#### Verificar Cache

```bash
# Verificar cache
redis-cli
> SELECT 1
> KEYS laravel_cache:*

# Verificar um cache específico
redis-cli
> SELECT 1
> GET laravel_cache:test_key

# Verificar TTL de um cache
redis-cli
> SELECT 1
> TTL laravel_cache:test_key
```

## 🐛 Troubleshooting

### Erro: "Connection refused"

**Causa:** Redis não está rodando ou porta incorreta.

**Solução:**
```bash
# Verificar se Redis está rodando
redis-cli ping
# Deve retornar: PONG

# Verificar porta
netstat -an | grep 6379
# Ou no Windows:
netstat -an | findstr 6379

# Iniciar Redis
# Linux:
sudo systemctl start redis-server
# macOS:
brew services start redis
# Docker:
docker start redis
```

### Erro: "Class 'Redis' not found"

**Causa:** Extensão PHP Redis não está instalada.

**Solução:**
```bash
# Verificar se a extensão está instalada
php -m | grep redis

# Instalar extensão (veja seção Configuração do PHP)
# Reiniciar servidor web após instalação
```

### Erro: "No connection could be made because the target machine actively refused it"

**Causa:** Redis não está acessível na porta configurada.

**Solução:**
```bash
# Verificar configuração do Redis
redis-cli
> CONFIG GET port
> CONFIG GET bind

# Verificar firewall
# Linux:
sudo ufw allow 6379
# Windows: Verificar Windows Firewall
```

### Erro: "WRONGPASS invalid username-password pair"

**Causa:** Senha do Redis incorreta.

**Solução:**
```bash
# Verificar senha no .env
REDIS_PASSWORD=sua_senha_aqui

# Ou remover senha do Redis (apenas desenvolvimento)
# Editar redis.conf e remover requirepass
```

### Jobs Não Estão Sendo Processados

**Causa:** Queue worker não está rodando ou configurado incorretamente.

**Solução:**
```bash
# Verificar configuração
php artisan config:show queue

# Processar jobs manualmente
php artisan queue:work redis --once

# Verificar jobs na fila
redis-cli
> SELECT 0
> LLEN queues:default
> LRANGE queues:default 0 -1
```

### Sessions Não Estão Persistindo

**Causa:** Configuração incorreta ou cache não limpo.

**Solução:**
```bash
# Limpar cache de configuração
php artisan config:clear
php artisan cache:clear

# Verificar configuração
php artisan config:show session

# Verificar Redis
redis-cli
> SELECT 0
> KEYS *session*
```

### Cache Não Está Funcionando

**Causa:** Configuração incorreta ou cache não limpo.

**Solução:**
```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear

# Verificar configuração
php artisan config:show cache

# Testar cache
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')

# Verificar Redis
redis-cli
> SELECT 1
> KEYS *
> GET laravel_cache:test
```

### Performance Lenta

**Causa:** Redis não está otimizado ou há muitos dados.

**Solução:**
```bash
# Verificar uso de memória
redis-cli
> INFO memory
> DBSIZE

# Limpar dados antigos
redis-cli
> FLUSHDB  # CUIDADO: Remove todos os dados do database atual
> FLUSHALL # CUIDADO: Remove todos os dados de todos os databases

# Otimizar Redis
redis-cli
> CONFIG SET maxmemory-policy allkeys-lru
```

### Reverb Não Está Funcionando com Redis

**Causa:** Configuração incorreta do Reverb scaling.

**Solução:**
```bash
# Verificar configuração
php artisan config:show reverb

# Verificar .env
REVERB_SCALING_ENABLED=true
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Verificar Redis
redis-cli
> SELECT 0
> PUBSUB CHANNELS
```

---

## 🚀 Próximos Passos

### 1. Otimização

- **Configurar TTL apropriado para cache**
- **Configurar política de eviction do Redis**
- **Monitorar uso de memória**
- **Configurar backups do Redis**

### 2. Produção AWS

- **Configurar ElastiCache Redis**
- **Configurar segurança (VPC, Security Groups)**
- **Configurar backups automáticos**
- **Configurar monitoramento (CloudWatch)**

### 3. Escalabilidade

- **Configurar Redis Cluster (se necessário)**
- **Configurar múltiplos workers de queue**
- **Configurar load balancing**
- **Configurar failover**

### 4. Monitoramento

- **Configurar logs do Redis**
- **Configurar alertas (CloudWatch)**
- **Configurar dashboards de monitoramento**
- **Configurar métricas de performance**

### 5. Migração Futura para SQS (Opcional)

Se no futuro você precisar migrar para SQS:

1. **Configurar SQS na AWS**
2. **Atualizar `.env`**
   ```env
   QUEUE_CONNECTION=sqs
   ```
3. **Migrar jobs gradualmente**
4. **Manter Redis para Cache e Sessions**

---

## 📚 Recursos Adicionais

- [Documentação Redis](https://redis.io/docs/)
- [Laravel Cache](https://laravel.com/docs/cache)
- [Laravel Queue](https://laravel.com/docs/queues)
- [Laravel Sessions](https://laravel.com/docs/session)
- [Laravel Reverb](https://laravel.com/docs/reverb)
- [AWS ElastiCache](https://aws.amazon.com/elasticache/)

---

## ✅ Checklist Final de Migração

Antes de considerar a migração completa:

- [ ] Redis instalado e funcionando
- [ ] Extensão PHP Redis habilitada
- [ ] Configuração do Laravel atualizada
- [ ] Cache migrado e testado
- [ ] Queue migrada e testada
- [ ] Sessions migradas e testadas
- [ ] Todos os testes do checklist executados
- [ ] Performance verificada
- [ ] Logs verificados (sem erros)
- [ ] Monitoramento configurado
- [ ] Documentação atualizada

---

## 🎯 Resumo

### Benefícios da Migração para Redis

1. **Performance**: Redis é muito mais rápido que database para cache, sessions e queue
2. **Escalabilidade**: Redis suporta clustering e scaling horizontal
3. **Funcionalidades**: Redis oferece recursos avançados (pub/sub, streams, etc.)
4. **Compatibilidade**: Redis é compatível com AWS ElastiCache
5. **Desenvolvimento**: Ambiente de desenvolvimento mais próximo da produção

### Quando Usar Redis

- **Cache**: Sempre (melhor performance)
- **Sessions**: Sempre (melhor performance e escalabilidade)
- **Queue**: Sempre (melhor performance, mas pode migrar para SQS no futuro)
- **Broadcasting**: Sempre (necessário para Reverb scaling)

### Quando NÃO Usar Redis

- **Dados críticos**: Use database para dados que precisam ser persistidos
- **Dados transacionais**: Use database para transações ACID
- **Dados complexos**: Use database para queries complexas

---

**Última atualização:** 2025-01-XX  
**Versão do Laravel:** 12.x  
**Versão do Redis recomendada:** 7+

