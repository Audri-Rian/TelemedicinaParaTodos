# DevREADME — Guia do Desenvolvedor

Documento de entrada para quem desenvolve no **Telemedicina para Todos**. Aqui está o resumo do projeto, stack, onde achar cada doc e como rodar o ambiente.

---

## Resumo do Projeto

Plataforma de telemedicina (Laravel + Vue/Inertia) com:

- **Agendamento** de consultas
- **Videochamadas** em tempo real (PeerJS + Laravel Reverb)
- **Prontuários digitais** e prescrições
- **Dois perfis**: médico e paciente

A versão do projeto segue o **package.json** (`version`). Histórico de mudanças em [CHANGELOG.md](CHANGELOG.md).

---

## Stack

| Camada   | Tecnologia |
|----------|------------|
| Backend  | Laravel 12, PHP 8.2+, Inertia.js, MySQL/SQLite, Reverb, Wayfinder |
| Frontend | Vue 3, TypeScript, Inertia (Vue 3), Tailwind CSS 4, Reka UI, Lucide Vue |
| Build    | Vite 7 |
| Testes   | PHPUnit (backend), testes em `tests/` |

---

## Primeiros Passos

1. **Clone e dependências**
   - `git clone ...` → `composer install` → `cp .env.example .env` → `php artisan key:generate`
2. **Banco**
   - SQLite: `touch database/database.sqlite` e `php artisan migrate`
   - Ou configure MySQL no `.env` e rode as migrations
3. **Frontend**
   - `npm install` (ou `pnpm install`) → `npm run dev`
4. **App**
   - `php artisan serve` (ou Sail/Docker conforme [Guia de Instalação](docs/setup/Start.md))

Guia completo: [docs/setup/Start.md](docs/setup/Start.md).

---

## Onde Está Cada Coisa

| Objetivo              | Documento |
|-----------------------|-----------|
| Instalação e ambiente | [docs/setup/Start.md](docs/setup/Start.md) |
| Arquitetura geral     | [docs/Architecture/Arquitetura.md](docs/Architecture/Arquitetura.md) |
| Padrões de código     | [docs/Architecture/DevGuide.md](docs/Architecture/DevGuide.md) |
| Frontend (Vue)        | [docs/Architecture/VueGuide.md](docs/Architecture/VueGuide.md) |
| Regras de negócio     | [docs/requirements/SystemRules.md](docs/requirements/SystemRules.md) |
| Funcionalidades       | [docs/requirements/FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md) |
| Modelo de dados       | [docs/database/diagrama_banco_dados.md](docs/database/diagrama_banco_dados.md) |
| Agendamentos          | [docs/modules/appointments/AppointmentsLogica.md](docs/modules/appointments/AppointmentsLogica.md), [AppointmentsImplementationStudy.md](docs/modules/appointments/AppointmentsImplementationStudy.md) |
| Videochamadas         | [docs/modules/videocall/VideoCallImplementation.md](docs/modules/videocall/VideoCallImplementation.md) |
| Glossário             | [docs/index/Glossario.md](docs/index/Glossario.md) |
| Matriz de requisitos  | [docs/index/MatrizRequisitos.md](docs/index/MatrizRequisitos.md) |

---

## Convenções de Desenvolvimento

- **Arquitetura**: Migrations → Models → DTOs ↔ Controllers ↔ Services (repositórios opcionais). Controllers recebem requisições, montam DTOs e chamam Services; lógica de negócio fica em Services.
- **Código**: PSR-12 (PHP), nomeação em inglês, migrations com timestamps.
- **Testes**: Métodos críticos com teste unitário/feature em `tests/`.

Ao abrir uma task, considere as regras em `docs/requirements/SystemRules.md` e o guia em `docs/Architecture/DevGuide.md`.

---

## Scripts Úteis

```bash
npm run dev          # frontend em modo desenvolvimento
npm run build        # build de produção
npm run lint         # ESLint
npm run format       # Prettier em resources/
npm run format:check # checagem Prettier
```

Backend: `php artisan test` para rodar os testes.

---

## Versionamento

A versão do projeto é a do **package.json** (`version`). Alterações relevantes são registradas no [CHANGELOG.md](CHANGELOG.md), organizadas por versão e por tipo (Added, Changed, Fixed, etc.).
