# 🛠️ Configuração e Instalação

Esta pasta contém toda a documentação necessária para configurar e executar o projeto em ambiente de desenvolvimento.

## 📁 Arquivos

- **[🚀 Guia de Instalação](Start.md)** - Configuração do ambiente de desenvolvimento
- **[⚙️ Regras do Cursor](CursorRulesGuide.md)** - Configurações do ambiente de desenvolvimento
- **[🔐 Estratégia de Ambientes](EnvironmentStrategy.md)** - Padrão de `.env` e checklist de secrets

## 🎯 Propósito

Estes documentos guiam através de:

- **Instalação** do ambiente de desenvolvimento
- **Configuração** de dependências
- **Execução** local do projeto
- **Configurações** específicas do editor

## 🔗 Navegação

- **Novos Desenvolvedores**: Comece com o [Guia de Instalação](Start.md)
- **Configuração de Editor**: Use as [Regras do Cursor](CursorRulesGuide.md)

## 🚀 Requisitos do Sistema

### Backend

- **PHP 8.2+**
- **Composer**
- **MySQL 8.0+** ou **SQLite**
- **Laravel 11**

### Frontend

- **Node.js 18+**
- **NPM** ou **Yarn**
- **Vue.js 3**

### Ferramentas

- **Git**
- **Editor** (VS Code/Cursor recomendado)
- **Docker** (opcional, para Laravel Sail)

## 📊 Ambiente de Desenvolvimento

### Opção 1: Instalação Local

1. Clone o repositório
2. Instale dependências PHP: `composer install`
3. Instale dependências JS: `npm install`
4. Configure variáveis de ambiente
5. Execute migrações e seeders

### Opção 2: Docker (Laravel Sail)

1. Clone o repositório
2. Execute `./vendor/bin/sail up`
3. Configure banco de dados
4. Execute setup inicial

## 🔧 Configurações

### Variáveis de Ambiente

- **APP_ENV** - Ambiente (local, staging, production)
- **DB_CONNECTION** - Tipo de banco (mysql, sqlite)
- **BROADCAST_CONNECTION** - Broadcasting (log ou reverb)
- **CACHE_STORE** - Cache (database, file ou redis)
- **QUEUE_CONNECTION** - Filas (database ou redis)
- **VITE_APP_NAME** - Nome da aplicação

### Editor

- **Cursor Rules** - Configurações específicas
- **ESLint** - Linting JavaScript/TypeScript
- **Prettier** - Formatação de código
- **PHP CS Fixer** - Formatação PHP

## 📊 Comandos Úteis

```bash
# Instalação
composer install
npm install

# Desenvolvimento
npm run dev
php artisan serve

# Testes
php artisan test
npm run test

# Build
npm run build
```

---

_Última atualização: Dezembro 2024_
