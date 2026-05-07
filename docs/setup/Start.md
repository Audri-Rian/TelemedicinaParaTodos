# 🚀 Guia de Instalação - Telemedicina para Todos

Este guia irá te ajudar a configurar e executar o projeto Telemedicina para Todos em seu ambiente de desenvolvimento local.

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- **PHP 8.2+** com as seguintes extensões:
    - BCMath PHP Extension
    - Ctype PHP Extension
    - cURL PHP Extension
    - DOM PHP Extension
    - Fileinfo PHP Extension
    - JSON PHP Extension
    - Mbstring PHP Extension
    - OpenSSL PHP Extension
    - PCRE PHP Extension
    - PDO PHP Extension
    - Tokenizer PHP Extension
    - XML PHP Extension

- **Composer 2.0+** - Gerenciador de dependências PHP
- **Node.js 18+** e **npm** - Para dependências JavaScript
- **Git** - Para clonar o repositório

## 🛠️ Instalação Passo a Passo

### 1. Clone o Repositório

```bash
git clone https://github.com/Audri-Rian/TelemedicinaParaTodos.git
cd TelemedicinaParaTodos
```

### 2. Instale as Dependências PHP

```bash
composer install
```

### 3. Configure o Ambiente

Copie o arquivo de configuração de exemplo:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações. O `.env.example` já traz comentários por seção. Mínimo para rodar local:

```env
APP_NAME="Telemedicina para Todos"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
# DB_DATABASE=... (ou database/database.sqlite para SQLite)

BROADCAST_CONNECTION=log
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

Use `BROADCAST_CONNECTION=reverb` e preencha as variáveis `REVERB_*` no `.env` se quiser notificações/WebSocket em tempo real. Use `CACHE_STORE=redis` e `QUEUE_CONNECTION=redis` se tiver Redis instalado.

Para organização por domínio, use também os templates:

- `.env.telemedicine.example` (núcleo da plataforma)
- `.env.integrations.example` (FHIR/RNDS/filas/circuit breaker)

Esses arquivos são **referência**; em runtime mantenha um único `.env` por ambiente.

### 4. Gere a Chave da Aplicação

```bash
php artisan key:generate
```

### 5. Configure o Banco de Dados

Crie o arquivo do banco SQLite:

```bash
touch database/database.sqlite
```

Execute as migrações:

```bash
php artisan migrate
```

### 6. Instale as Dependências JavaScript

```bash
npm install
```

### 7. Compile os Assets

```bash
npm run build
```

### 8. Inicie o Servidor de Desenvolvimento

Em um terminal, inicie o servidor Laravel:

```bash
php artisan serve
```

(Opcional) Em terminais separados, para funcionalidades completas:

- **Reverb** (WebSocket / tempo real): `php artisan reverb:start` — só necessário se `BROADCAST_CONNECTION=reverb`.
- **Queue worker** (jobs em background): `php artisan queue:work` — necessário se usar filas (lembretes, notificações, etc.).
- **Scheduler** (tarefas agendadas): `php artisan schedule:work` — para lembretes de consulta e rotinas do sistema.

## 🌐 Acessando a Aplicação

Após seguir todos os passos, você poderá acessar:

- **Aplicação Principal**: http://localhost:8000
- **Servidor Reverb**: http://localhost:8080
- **Servidor Peerjs**: Para projetos grandes, é comum rodar um servidor PeerJS (com npx peerjs --port 9000) ou usar STUN/TURN para conexões externas.

## 🧪 Executando Testes

Para executar os testes automatizados:

```bash
# Testes unitários
php artisan test --testsuite=Unit

# Testes de funcionalidade
php artisan test --testsuite=Feature

# Todos os testes
php artisan test
```

## 🐳 Usando Laravel Sail (Docker)

Se preferir usar Docker, você pode utilizar o Laravel Sail:

```bash
# Instalar dependências do Sail
./vendor/bin/sail up -d

# Executar comandos via Sail
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run build
```

## 🔧 Comandos Úteis

```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recarregar configurações
php artisan config:cache

# Ver rotas disponíveis
php artisan route:list

# Acessar Tinker (console interativo)
php artisan tinker
```

## 🚨 Solução de Problemas

### Erro de Permissões

Se encontrar problemas de permissão:

```bash
chmod -R 755 storage bootstrap/cache
```

### Erro de Extensões PHP

Verifique se todas as extensões necessárias estão instaladas:

```bash
php -m | grep -E "(bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pcre|pdo|tokenizer|xml)"
```

### Erro de Composer

Se o Composer falhar, tente:

```bash
composer install --ignore-platform-reqs
```

## ✅ Checklist — Subiu local e está ok

- [ ] `cp .env.example .env` e `php artisan key:generate`
- [ ] `composer install` e `npm install` sem erro
- [ ] `php artisan migrate` executou sem falha
- [ ] (Opcional) `php artisan db:seed`
- [ ] `php artisan serve` sobe e a aplicação abre no navegador
- [ ] (Se usar Reverb) `php artisan reverb:start` rodando
- [ ] (Se usar filas) `php artisan queue:work` rodando
- [ ] (Opcional) Login e uma consulta de teste funcionando

## 📚 Próximos Passos

Após a instalação bem-sucedida:

1. **Configure o usuário administrador** através do seeder
2. **Explore a documentação** em `docs/`
3. **Verifique os diagramas** em `diagrams/`
4. **Comece a desenvolver** suas funcionalidades

## 🤝 Suporte

Se encontrar problemas durante a instalação:

1. Verifique se todos os pré-requisitos estão atendidos
2. Consulte a documentação em `docs/`
3. Abra uma issue no repositório do GitHub

---

**🎉 Parabéns!** Seu ambiente de desenvolvimento está configurado e pronto para uso!
