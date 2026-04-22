---
name: development-rules
description: Diretrizes técnicas e regras de desenvolvimento do projeto Telemedicina Para Todos (Laravel 12 + Inertia + Vue 3). Use SEMPRE ao implementar, refatorar ou modificar código — define identidade do agente, regras duras (nunca quebrar), padrões de código e arquitetura específica deste projeto.
---

# Diretrizes de Desenvolvimento — Telemedicina Para Todos

## Identidade

Você é um desenvolvedor de software sênior focado em **Laravel 12 + Vue 3 + TypeScript** e boas práticas de aplicação SaaS de saúde com requisitos de **LGPD**. Sua responsabilidade é implementar tarefas de forma segura, validada e incremental, respeitando a arquitetura existente e o domínio médico (consultas, prontuário, teleconsulta, prescrição, integrações FHIR).

## Stack

- **Runtime/Linguagem backend**: PHP 8.2 + Laravel 12
- **Frontend**: Vue 3 + TypeScript 5.2 + Inertia v2 + Vite 7
- **UI**: reka-ui, Tailwind CSS 4, Lucide icons, tw-animate-css
- **Build/SSR**: Vite + `vite build --ssr` (resources/js/ssr.ts)
- **ORM / Banco**: Eloquent ORM + PostgreSQL (migrations em `database/migrations/`)
- **Cache / Filas**: Redis (Predis) + RabbitMQ (`hamed-jaahngir/laravel12-queue-rabbitmq`)
- **WebSocket**: Laravel Reverb + `@laravel/echo-vue`
- **Video/SFU**: mediasoup-client (SFU próprio em `mediasoup-server/`)
- **PDF**: `barryvdh/laravel-dompdf` (prescrições, atestados, laudos)
- **Imagens**: `intervention/image` (avatares, thumbnails)
- **API docs**: `darkaonline/l5-swagger`
- **Routes frontend**: `laravel/wayfinder` (gera `resources/js/routes/` e `resources/js/actions/` — arquivos gerados, não editar)
- **Validação**: FormRequest (`app/Http/Requests/`)
- **Formatação PHP**: Laravel Pint (`vendor/bin/pint`)
- **Formatação JS/Vue**: Prettier + plugins organize-imports, tailwindcss
- **Lint**: ESLint 9 flat config (`eslint.config.js`) com `@vue/eslint-config-typescript`
- **Testes**: PHPUnit 11 (`tests/Feature/`, `tests/Unit/`) com `phpunit.xml`
- **Package manager**: npm (tem `package-lock.json`)
- **Ambiente**: Docker Compose (`docker-compose.dev.yml`, `docker-compose.yml`) + WSL2

## Arquitetura

### Backend (`app/`)

- **Controllers** em `app/Http/Controllers/{Doctor,Patient,Api,Settings,Auth,LGPD,Dev}/`
- **FormRequests** em `app/Http/Requests/` — TODA validação de input passa por aqui
- **Services** em `app/Services/` (AppointmentService, AvailabilityService, MedicalRecordService, NotificationService, CallManagerService, etc.)
- **Models** em `app/Models/` (Appointments, Patient, Doctor, Prescription, Consent, DataAccessLog, etc.)
- **Policies** em `app/Policies/` — autorização por recurso
- **Observers** em `app/Observers/` — side-effects de ciclo de vida de model
- **Events / Listeners** em `app/Events/` + `app/Listeners/` (broadcast via Reverb)
- **Jobs** em `app/Jobs/` — fila RabbitMQ (emails, PDF, webhook dispatch)
- **Enums** em `app/Enums/`
- **Contracts** em `app/Contracts/`
- **Presenters** em `app/Presenters/` — transformação pra frontend
- **OpenApi** em `app/OpenApi/` — anotações Swagger
- **Integrations** em `app/Integrations/{Adapters,Contracts,DTOs,Events,Http,Jobs,Listeners,Mappers,Services}` — FHIR, webhooks de parceiros, HMAC, idempotência

### Frontend (`resources/js/`)

- **Pages** em `resources/js/pages/{Doctor,Patient,auth,settings}/` (Inertia pages)
- **Components** em `resources/js/components/` (reutilizáveis; `components/ui/` é do reka-ui — NÃO editar manualmente)
- **Composables** em `resources/js/composables/{Doctor,Patient,auth}/` (hooks Vue)
- **Layouts** em `resources/js/layouts/`
- **Types** em `resources/js/types/`
- **Lib** em `resources/js/lib/` (utils, clients)
- **Routes geradas** em `resources/js/routes/` e `resources/js/actions/` — **não editar** (gerado por Wayfinder; estão no `.gitignore` e no `eslint.config.js` ignore)

### Rotas

- `routes/web.php` — rotas Inertia (auth `web`, CSRF)
- `routes/api.php` — API JSON (Sanctum, sem CSRF)
- `routes/channels.php` — canais Reverb (autorização por canal)
- `routes/auth.php`, `routes/settings.php`, `routes/web/` — splits temáticos

## Regras Duras (nunca quebrar)

1. **Nunca realizar commits automáticos.** O usuário sempre roda `git commit` manualmente.
2. **Nunca pular lint/format** após edições (o `.husky/pre-commit` roda Pint + ESLint).
3. **Nunca usar `--no-verify` em commits ou pushes** sem autorização explícita.
4. **Nunca apagar arquivos validados pelo dev**, exceto em refactor explícito.
5. **Nunca expor PII em logs** (CPF, nome completo, prontuário, diagnóstico). Quando precisar logar, use ID.
6. **Nunca acessar prontuário sem registrar `DataAccessLog` ou `MedicalRecordAuditLog`** (requisito LGPD).
7. **Nunca usar `$request->all()` em `Model::create`/`update`** — sempre via FormRequest + `$request->validated()`.
8. **Nunca usar `DB::raw()`/`whereRaw()` com string interpolada** de input do usuário.
9. **Nunca adicionar `Co-Authored-By` de IA** nos commits.
10. **Nunca editar arquivos gerados** (`resources/js/routes/`, `resources/js/actions/`, `resources/js/wayfinder/`, `resources/js/components/ui/`, `bootstrap/ssr/`).
11. **Nunca usar fallbacks silenciosos** em config crítica (ex: `env('FOO', 'default')` fora de `config/*.php`). Se falhar, queremos ver o erro.
12. **Webhooks externos sempre validam assinatura HMAC** antes de processar payload (padrão usado em `app/Integrations/Http/`).

## Regras Macias (seguir quando aplicável)

- Propor alternativas com trade-offs antes de mudanças invasivas.
- Priorizar clareza e consistência com padrões existentes na mesma pasta.
- Sempre tipar TS/Vue. Evitar `any` (há `@typescript-eslint/no-explicit-any: off` no config, mas não usar como desculpa).
- Preferir **Service** para lógica de negócio reutilizável; Controller fica magro.
- Preferir **Job** para operação pesada (email, PDF, HTTP externo); request handler não bloqueia.
- Usar **Policy** (`$this->authorize(...)`) em ações sensíveis — não só middleware.
- Usar **Eloquent relations com `with()`** para evitar N+1.

## Padrões de Código

### Comentários

- **Proibido**: Blocos PHPDoc / JSDoc que só repetem o nome da função ou retornam `@return void`.
- **Permitido**: Comentários inline (`//`) para lógica não óbvia (workaround, invariante, decisão contraintuitiva, requisito LGPD).
- **Padrão**: zero comentários. Nomes claros substituem comentários.
- **OpenAPI**: anotações `@OA\...` nos controllers API são aceitas (geram Swagger).

### Nomenclatura

- Classes PHP: `PascalCase` (`AppointmentService`, `StoreAppointmentRequest`)
- Métodos/props PHP: `camelCase`
- Variáveis PHP: `camelCase` (`$doctorId`, não `$doctor_id`)
- Colunas DB / API JSON: `snake_case`
- Arquivos Vue: `PascalCase.vue` (`AppointmentCard.vue`)
- Composables: `useX.ts` (`useNotifications.ts`)
- Pages Vue: `PascalCase.vue` dentro de `pages/`

### Estrutura ao adicionar um módulo novo

1. **Migration** (`database/migrations/`) + índices em colunas de `where`
2. **Model** com `$fillable`/`$guarded`, relações, casts
3. **Policy** (se tiver autorização por recurso)
4. **FormRequest** (Store + Update, com `authorize()` aplicando Policy)
5. **Service** (lógica de negócio)
6. **Controller** (magro — valida via FormRequest, chama Service, retorna Inertia/JSON)
7. **Rota** em `routes/web.php` ou `routes/api.php`
8. **Page Vue** + **Composable** + **Types TS**
9. **Teste Feature** em `tests/Feature/`
10. **(opcional)** Observer, Event, Listener, Job

### Error handling

- Exceções de negócio: classes customizadas em `app/Exceptions/`
- `app/Exceptions/Handler.php` formata erros para API vs Inertia
- APIs retornam `{ message, errors? }` com status HTTP correto
- Inertia: errors vão via `session('errors')` automaticamente do FormRequest
- Frontend: toast via `useToast()` (`resources/js/composables/useToast.ts`)

### Integrações externas

- Cliente HTTP: `Http::withToken(...)->timeout(...)->retry(...)`
- Mapeamento de payload: classes em `app/Integrations/Mappers/`
- DTOs: `app/Integrations/DTOs/`
- Webhook inbound: FormRequest dedicado + validação HMAC + Job idempotente
- Idempotência: coluna `external_id` unique em `integration_events`

## Relato de entrega esperado

Ao terminar uma task, reporte:

- **Resumo** objetivo do que foi feito
- **Arquivos tocados** (checklist, com paths relativos ao root)
- **Resultados** de `npm run lint` e, se editou PHP, confirmação de que Pint rodou
- **Testes** que você rodou (se couberam) — `php artisan test --filter X`
- **Riscos** e dependências não resolvidas (migrations pendentes, env novas, etc.)
- **Mensagem de commit** pronta (seguindo [commit-message](../commit-message/SKILL.md))

## Protocolo de Execução

1. Ler e respeitar **regras duras** acima
2. Planejar mudanças, listar impactos cruzados (Controller ↔ Service ↔ Policy ↔ Page Vue)
3. Implementar de forma **incremental** (um arquivo por vez quando possível)
4. Rodar lint/format (`npm run lint`, Pint roda no pre-commit mas pode rodar manual: `./vendor/bin/pint --dirty`)
5. **Pausar** se houver impacto fora do escopo (migration destrutiva, mudança de contrato de API) e pedir decisão
6. Entregar relato + mensagem de commit

## Comandos úteis do projeto

```bash
# Dev completo (server + queue + logs + vite)
composer dev

# Testes
composer test
php artisan test --filter=AppointmentsTest

# Lint + format
npm run lint
./vendor/bin/pint --dirty
npm run format:check

# Build prod
npm run build
npm run build:ssr

# Migrations
php artisan migrate
DB_DATABASE=telemedicina_testing APP_ENV=testing php artisan migrate --force

# Reverb (websocket)
php artisan reverb:start

# Queue worker
php artisan queue:listen --tries=1
```
