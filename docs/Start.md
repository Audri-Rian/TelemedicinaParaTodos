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

Edite o arquivo `.env` com suas configurações:

```env
APP_NAME="Telemedicina para Todos"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=database/database.sqlite

BROADCAST_DRIVER=reverb
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

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

Em outro terminal, inicie o servidor de broadcasting (Reverb):

```bash
php artisan reverb:start
```

## 🌐 Acessando a Aplicação

Após seguir todos os passos, você poderá acessar:

- **Aplicação Principal**: http://localhost:8000
- **Servidor Reverb**: http://localhost:8080

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