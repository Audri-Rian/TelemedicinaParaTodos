# Guia para Desenvolvedores — Telemedicina para Todos

Este documento é o ponto de entrada para quem vai desenvolver no projeto: **básico do ambiente**, **onde achar cada coisa** e **como usar a documentação da API (Swagger e ReDoc)**.

---

## Para quem é este guia

- Novos devs que acabaram de clonar o repositório
- Quem precisa rodar o projeto localmente e consultar a API
- Quem quer saber como a documentação OpenAPI (Swagger / ReDoc) funciona neste projeto

---

## Básico: primeiro acesso e rodando o projeto

### 1. Clone e dependências

```bash
git clone <url-do-repo>
cd TelemedicinaParaTodos
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Banco de dados

- **SQLite:** `touch database/database.sqlite` e depois `php artisan migrate`
- **MySQL/PostgreSQL:** configure no `.env` e rode `php artisan migrate`

### 3. Frontend

```bash
npm install
npm run dev
```

### 4. Subir a aplicação

```bash
php artisan serve
```

Ou use o script integrado (servidor + fila + Vite + Reverb):

```bash
composer run dev
```

A aplicação fica disponível em `http://localhost:8000` (ou na porta indicada no terminal).

### 5. Documentos essenciais

| Objetivo | Documento |
|----------|-----------|
| Instalação passo a passo | [Guia de Instalação](../setup/Start.md) |
| Arquitetura e camadas | [Arquitetura](../layers/architecture-governance/Architecture/Arquitetura.md) |
| Padrões de código (Controllers, Services, DTOs, Models) | [Guia de Desenvolvimento (DevGuide)](../layers/architecture-governance/Architecture/DevGuide.md) |
| Frontend (Vue, Inertia, convenções) | [Guia do Frontend](../layers/architecture-governance/Architecture/VueGuide.md) |
| Regras de negócio e compliance | [Regras do Sistema](../layers/architecture-governance/requirements/SystemRules.md) |
| Resumo rápido (stack, scripts, convenções) | [DevREADME](../../DevREADME.MD) (na raiz do projeto) |

Ao abrir uma task, use como contexto: **SystemRules** + **DevGuide** para manter padrões e responsabilidades das camadas.

---

## Documentação da API: Swagger e ReDoc

A API (endpoints que retornam JSON) é descrita em **OpenAPI 3.x**. A partir dessa mesma especificação, o projeto expõe duas interfaces:

- **Swagger UI** — para **consultar e testar** os endpoints (Try it out).
- **ReDoc** — para **ler** a documentação em formato de manual.

Ambos usam o **mesmo arquivo de spec** gerado pelo comando do L5-Swagger; não é necessário manter duas documentações.

### O que é a spec OpenAPI

- A **spec** (especificação) é um JSON (ou YAML) que descreve todos os endpoints, parâmetros, respostas e tags.
- Ela fica em `storage/api-docs/api-docs.json` e é gerada a partir das **anotações PHP (Attributes)** nos controllers.
- Toda vez que você altera um endpoint ou adiciona um novo, é preciso **regerar** a spec para Swagger e ReDoc refletirem a mudança.

### Rotas para acessar a documentação

As duas rotas só funcionam em ambiente **local** (ou dev/staging), por middleware de segurança.

| Interface | URL (local) |
|-----------|-------------|
| **Swagger UI** | `http://localhost:8000/api/documentation` |
| **ReDoc** | `http://localhost:8000/api/redoc` |

Em outro ambiente, troque o host (ex.: `https://seu-dominio.com/api/documentation` e `https://seu-dominio.com/api/redoc`).

### Quando usar cada um

- **Swagger UI** — quando você quer **testar** a API direto no navegador (enviar requisições, ver respostas). Útil para debug e integração.
- **ReDoc** — quando você quer **ler** a documentação como um manual (layout em coluna, foco em leitura). Útil para onboarding e para compartilhar com parceiros.

Não é obrigatório usar os dois; um só já atende. Ter as duas rotas é apenas conveniência.

### Como regerar a documentação

Sempre que alterar controllers ou Attributes OpenAPI:

```bash
php artisan l5-swagger:generate
```

Isso atualiza `storage/api-docs/api-docs.json`. Ao recarregar **Swagger UI** ou **ReDoc**, a documentação exibida será a nova.

### Autenticação nos endpoints

- Vários endpoints exigem **autenticação por sessão** (cookie). No **Try it out** do Swagger, o navegador pode não enviar o cookie automaticamente; para testar, use os endpoints **públicos** (ex.: listagem de especializações, disponibilidade por data) ou faça login na aplicação no mesmo domínio antes.
- A spec já está preparada para uma futura **API pública** com autenticação Bearer (interoperabilidade).

### Referência da feature

- Detalhes da implementação e checklist da feature Swagger/OpenAPI: [TASK_SWAGGER_FEATURE.md](../Tasks/TASK_SWAGGER_FEATURE.md).

---

## Resumo rápido

1. **Rodar o projeto:** `composer install`, configurar `.env`, migrations, `npm run dev`, `php artisan serve` (ou `composer run dev`).
2. **Padrões e arquitetura:** [DevGuide](../layers/architecture-governance/Architecture/DevGuide.md) e [Arquitetura](../layers/architecture-governance/Architecture/Arquitetura.md).
3. **Documentação da API:** Swagger em `/api/documentation`, ReDoc em `/api/redoc`; regerar com `php artisan l5-swagger:generate` após mudanças nos endpoints.
