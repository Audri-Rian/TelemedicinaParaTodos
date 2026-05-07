# Feature Spec — [Nome da Feature]

> Status: `draft` | `review` | `approved`
> Autor: Tech Lead Agent · Data: [YYYY-MM-DD]

---

## Objetivo

[Uma frase clara. O que essa feature faz e para quem.]

## Motivação

[Problema sendo resolvido. Por que agora.]

---

## Regras de negócio

1. ...
2. ...

---

## Arquitetura proposta

[Fluxo textual da solução. Exemplo:]

```
[Trigger] → [Controller] → [Service] → [Job?] → [Storage/DB]
                                ↓
                         [Notification?]
```

Padrões reutilizados:

- `XService` — estendido com método `Y`
- `ZJob` — disparado quando [condição]

---

## Frontend

### Componentes

| Componente        | Novo/Reutilizado | Props principais |
| ----------------- | ---------------- | ---------------- |
| `ComponenteX.vue` | Novo             | `propA`, `propB` |

### Composable

- `useX.ts` — responsabilidade

### Estados de UI

- **Loading:** [comportamento]
- **Erro:** [mensagem + recovery]
- **Vazio:** [estado empty]
- **Sucesso:** [feedback]

### Rota Inertia

| Método | Rota    | Componente          |
| ------ | ------- | ------------------- |
| GET    | `/rota` | `pages/X/Index.vue` |

---

## Backend

### Endpoints

| Método | Rota     | Controller@action   | FormRequest     |
| ------ | -------- | ------------------- | --------------- |
| POST   | `/api/x` | `XController@store` | `StoreXRequest` |

### Service

- `XService::metodo(args)` — [responsabilidade]

### Jobs / Filas

| Job           | Fila      | Quando disparar | Timeout |
| ------------- | --------- | --------------- | ------- |
| `ProcessXJob` | `default` | após [evento]   | 60s     |

### Validações (FormRequest)

```php
// StoreXRequest rules
'campo' => 'required|string|max:255',
```

### Autorização

- Middleware: `auth`, `[outros]`
- Policy: `XPolicy@action` — regra

---

## Banco de dados

### Migrations

```php
// Tabela nova ou coluna adicionada
'coluna' => tipo, nullable?, index?
```

### Índices necessários

| Tabela   | Coluna(s) | Motivo                      |
| -------- | --------- | --------------------------- |
| `tabela` | `coluna`  | filtro frequente em query Y |

### Relacionamentos

- `ModelX hasMany ModelY` via `foreign_key`

---

## Infraestrutura

- **Storage:** `disk('local')` · path: `[caminho]` · permissões: [regra]
- **Fila:** RabbitMQ · fila: `[nome]` · workers: [quantidade estimada]
- **Cache:** [driver] · TTL: [Xs] · invalidado quando: [evento]

_(Omitir seções não aplicáveis)_

---

## Observabilidade

| O que logar  | Nível   | Contexto incluído      |
| ------------ | ------- | ---------------------- |
| Criação de X | `info`  | `user_id`, `x_id`      |
| Falha em Y   | `error` | `exception`, `payload` |

---

## Segurança

- Validação de entrada: [campos e regras]
- Sanitização de output: [onde/como]
- Dados sensíveis: [como proteger]
- Riscos identificados: [lista]

---

## Edge Cases

1. [Cenário] → [Comportamento esperado]
2. Upload parcial → rollback do arquivo e retorno de erro 422
3. Race condition em [operação] → [mecanismo de lock/idempotência]
4. ...

---

## Riscos técnicos

| Risco     | Probabilidade    | Impacto          | Mitigação |
| --------- | ---------------- | ---------------- | --------- |
| [Risco X] | Alta/Média/Baixa | Alto/Médio/Baixo | [ação]    |

---

## Plano de implementação

Ordenado por dependência técnica:

1. `[Backend]` Migration `create_x_table`
2. `[Backend]` Model `X` + relacionamentos
3. `[Backend]` `XService` com lógica isolada
4. `[Backend]` `ProcessXJob` (se async)
5. `[Backend]` `StoreXRequest` + `XController`
6. `[Backend]` Rotas em `routes/web/[domínio].php`
7. `[Backend]` `XPolicy` + registro em `AuthServiceProvider`
8. `[Frontend]` Composable `useX.ts`
9. `[Frontend]` Componentes + estados UI
10. `[Frontend]` Página Inertia + tipagem TypeScript
11. `[Testes]` Unit: `XService`
12. `[Testes]` Feature: `XController` endpoints
13. `[Testes]` E2E: fluxo principal

---

## Checklist

### Backend

- [ ] Migration criada e revisada
- [ ] Model com relacionamentos e casts corretos
- [ ] Service com lógica isolada (sem HTTP no service)
- [ ] FormRequest com todas as validações
- [ ] Controller delegando ao Service (sem lógica de negócio)
- [ ] Rotas no arquivo de domínio correto com middleware
- [ ] Policy implementada e registrada
- [ ] Job + fila configurados (se async)
- [ ] Logs nos pontos críticos

### Frontend

- [ ] Composable com tipagem TypeScript
- [ ] Loading / erro / vazio / sucesso implementados
- [ ] Responsividade verificada (mobile first)
- [ ] `useToast` para feedback ao usuário
- [ ] Props tipadas com interface/type

### Qualidade

- [ ] Testes unitários do Service
- [ ] Testes de integração do Controller
- [ ] Edge cases cobertos em testes
- [ ] Nenhuma query N+1 introduzida
- [ ] `php artisan test` passando
