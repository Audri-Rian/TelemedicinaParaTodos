# Feature Spec — Padronização visual: settings/Profile.vue (médico e paciente)

> Status: `approved`
> Autor: Tech Lead Agent · Data: 2026-05-22 · Aprovada: 2026-05-22

---

## Objetivo

Modernizar `resources/js/pages/settings/Profile.vue` para compartilhar a identidade visual, linguagem de design e padrões de componentes de `resources/js/pages/Patient/DoctorPerfil.vue`, mantendo toda a lógica de edição e regras de negócio intactas — para **médico e paciente**.

## Motivação

O perfil de configurações usa tokens genéricos do tema (`primary`, `gray-*`, `rounded-2xl`, `border-gray-100`) enquanto o `DoctorPerfil.vue` consolidou um design system coeso com palette `slate-*` + acento `teal-*`, seções com hierarquia clara e padrão de header de card em duas linhas (eyebrow uppercase + título bold). A divergência visual afeta médicos (que alternam entre perfil público e settings) e pacientes (mesma área de configurações com hierarquia inferior).

---

## Critérios de aceite

1. **Visual:** todos os cards e badges seguem tokens `slate-*` / `teal-*` / `amber-*` conforme tabelas desta spec; nenhum acento `primary` ou `green-*` permanece em `Profile.vue` (exceto erros `red-*`).
2. **Funcional:** `form.patch`, upload/remoção de avatar via `axios`, timeline (CRUD via modal), dropdown de especializações e `DeleteUser` funcionam como antes.
3. **Médico:** hero com avatar shadcn, badges derivados dos dados existentes (sem novo endpoint), seções em cards separados, timeline em card próprio fora do `<form>`.
4. **Paciente:** seções de saúde em cards separados; wrapper “Segunda Etapa de Autenticação” removido; badge de completude apenas no card de emergência; aviso pós-salvar com tokens `teal-*`.
5. **Campos:** `<Input>`, `<Textarea>` e `<Select>` sem classes `rounded-xl`, `border-primary/*` ou `focus-visible:ring-primary/*`.
6. **Headers:** nenhum uso de `HeadingSmall` em `Profile.vue` — padrão eyebrow + `h2` inline em todos os cards.
7. **Responsivo:** layout utilizável em 375px e 768px.

---

## Fora de escopo

| Item                                                                         | Motivo                                                                  |
| ---------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| Alteração de backend, rotas, `ProfileController`, migrations                 | Escopo estritamente visual em um arquivo Vue                            |
| Reordenar seções (ex.: campos médicos antes de conta)                        | Mantém ordem atual: hero → conta → doctor → patient → footer → timeline |
| `Dialog` shadcn no lugar de `window.confirm()`                               | Feature de UX separada                                                  |
| `Select` shadcn completo (`SelectTrigger` + `SelectContent`)                 | Manter `<Select>` nativo; binding `v-model` intocado                    |
| Migrar `uploadSuccess` para `useToast`                                       | Manter `ref` + mensagem inline; apenas restyle                          |
| Alterar `HeadingSmall.vue` ou outros consumidores                            | Substituir só em `Profile.vue`                                          |
| Barra de progresso de completude do perfil                                   | Apenas badges no hero/card, como em `DoctorPerfil`                      |
| `DeleteUser`, `TimelineModal`, lógica interna do dropdown de especializações | Apenas estilo do container/trigger/tags                                 |

---

## Regras de negócio

1. Toda lógica de formulário (`useForm`, `form.patch`, validações) deve ser preservada sem alteração.
2. Upload, preview e remoção de avatar via `axios` devem continuar funcionando identicamente.
3. Lógica de timeline (criar, editar, deletar eventos via `TimelineModal`) deve ser preservada.
4. Segmentação por role (`auth.isDoctor` / `auth.isPatient`) deve continuar controlando quais seções aparecem.
5. O campo `Select` nativo do shadcn é mantido para `blood_type` e `status` — não substituir por implementação custom.
6. O dropdown custom de especializações (`.profile-specializations-dropdown`) deve ser mantido — apenas estilizado, não refatorado em lógica.
7. `DeleteUser` e `TimelineModal` são componentes filhos — não alterar sua lógica interna.
8. **`HeadingSmall`:** remover de `Profile.vue` e substituir pelo padrão inline eyebrow + `h2` (o componente não aceita override de classes).
9. **Inputs:** remover de todos os `<Input>` / `<Textarea>` as classes `rounded-xl`, `border-primary/30`, `focus-visible:ring-primary/30` (e equivalentes); usar estilo padrão do shadcn sem override de radius/borda.

---

## Decisões fechadas

| Tópico                             | Decisão                                                                                                                                            |
| ---------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Escopo de roles                    | Médico **e** paciente na mesma entrega                                                                                                             |
| Ordem das seções                   | Inalterada: hero → informações básicas → blocos doctor → blocos patient → footer do form → timeline (doctor) → `DeleteUser`                        |
| `HeadingSmall`                     | Substituir globalmente em `Profile.vue` por eyebrow + `h2`                                                                                         |
| Feedback de upload                 | Manter `uploadSuccess` ref + `<div>` inline; só trocar classes para `teal-*`                                                                       |
| Paciente “Segunda Etapa”           | Remover título/badge global; badge completo/incompleto **somente** no header do card de emergência                                                 |
| Aviso pós-salvar (patient)         | Manter bloco; trocar `green-*` → `teal-*` / `slate-*`                                                                                              |
| Aviso timeline incompleta (doctor) | Trocar `border-blue-200 bg-blue-50` → `border-slate-200 bg-slate-50 text-slate-700`                                                                |
| Hero badges CRM                    | Espelhar `DoctorPerfil`: exibir se `form.crm` ou `doctor?.crm` preenchido; rótulo **“CRM verificado”** é cosmético (não implica validação backend) |
| Especialidade no hero              | `computed` no frontend — sem nova prop do controller                                                                                               |
| Ícones de badge                    | Padronizar em `CheckCircle2` e `AlertCircle` (importar `CheckCircle2`; remover `CheckCircle` se não usado)                                         |

---

## Análise de divergência visual

### Tokens que divergem

| Dimensão                | DoctorPerfil.vue (referência)       | Profile.vue (atual)                      | Ação                                                                    |
| ----------------------- | ----------------------------------- | ---------------------------------------- | ----------------------------------------------------------------------- |
| Background da página    | `bg-slate-50`                       | `bg-gray-50/80` (via Layout)             | Harmonizar — Layout usa `bg-gray-50/80`, manter; cards internos alinhar |
| Border dos cards        | `border-slate-200`                  | `border-gray-100`                        | Mudar para `border-slate-200`                                           |
| Border-radius dos cards | `rounded-lg`                        | `rounded-2xl`                            | Mudar para `rounded-lg`                                                 |
| Shadow dos cards        | `shadow-sm`                         | `shadow-sm`                              | Manter                                                                  |
| Header de seção         | eyebrow + `h2`                      | `HeadingSmall`                           | Substituir por padrão inline (decisão fechada)                          |
| Título de seção         | `text-xl font-bold text-slate-950`  | `text-base font-medium` via HeadingSmall | `text-xl font-bold text-slate-950`                                      |
| Subtítulo de seção      | `text-sm text-slate-500`            | `text-muted-foreground`                  | `text-sm text-slate-500`                                                |
| Labels de campo         | `text-slate-700`                    | `text-gray-800`                          | `text-slate-700`                                                        |
| Palette de acento       | `teal-*`                            | `primary` (azul)                         | Substituir por `teal-*` onde aplicável                                  |
| Avatar fallback         | `bg-teal-700 text-white` + initials | gradiente azul + `UserIcon`              | `AvatarFallback` + `useInitials`                                        |
| Avatar ring             | `ring-4 ring-teal-50`               | `border-2 border-primary/20`             | `ring-4 ring-teal-50`                                                   |
| Input                   | padrão shadcn                       | `rounded-xl border-primary/30`           | Remover overrides (decisão fechada)                                     |
| Botão primário          | `bg-teal-500 text-slate-950`        | `bg-primary text-white`                  | `bg-teal-500 text-slate-950 hover:bg-teal-400`                          |
| Badges de status        | `teal-*` / `amber-*`                | `green-*` / `yellow-*`                   | Padronizar                                                              |
| Separador               | `border-slate-200`                  | `border-t` sem cor                       | `border-t border-slate-200`                                             |

---

## Arquitetura proposta

Feature puramente frontend. Nenhuma alteração de backend, rotas, controllers, jobs ou banco de dados.

```
Profile.vue (template refatorado)
  ├── SettingsLayout (sem alteração)
  ├── [Card] Hero — avatar + upload (todos os roles; badges extras só doctor)
  ├── <form>
  │     ├── [Card] Informações básicas (todos)
  │     ├── [Cards] Seções doctor (condicional)
  │     ├── [Cards] Seções patient (condicional)
  │     └── [Footer] Salvar + feedback inline
  ├── [Card] Timeline (doctor, fora do form)
  └── DeleteUser (sem alteração)
```

---

## Frontend

### Estrutura atual vs proposta

#### Problema atual

O `Profile.vue` mistura avatar, formulário monolítico e timeline com hierarquia visual fraca. A proposta mantém **um único `<form>`** (submit único) e divide o template em **múltiplos cards**.

#### Padrão de card (obrigatório)

```html
<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">[Categoria]</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">[Título]</h2>
            <p v-if="descricao" class="mt-1 text-sm text-slate-500">[Descrição]</p>
        </div>
        <!-- slot opcional: badge no canto do header -->
    </div>
    <!-- conteúdo -->
</div>
```

**`HeadingSmall`:** não usar em `Profile.vue`. Remover import após migração.

---

### Hero do perfil — contrato de dados (sem backend)

Card **fora do `<form>`**, no topo da página, para todos os roles com upload de avatar.

#### Variante médico (`auth.isDoctor`)

| Elemento                | Fonte                                             | Condição / regra                                                                                             |
| ----------------------- | ------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| Nome                    | `user.name`                                       | Sempre                                                                                                       |
| Avatar                  | `avatarThumbnailUrl` / `avatarUrl` / `previewUrl` | Lógica atual                                                                                                 |
| Iniciais fallback       | `getInitials(user.name)` ou `'?'` se vazio        | Ver edge case #3                                                                                             |
| Badge “Perfil completo” | `props.timelineCompleted`                         | `v-if` doctor + true                                                                                         |
| Badge “CRM verificado”  | `form.crm \|\| props.doctor?.crm`                 | Cosmético, igual `DoctorPerfil`                                                                              |
| Badge status            | `form.status`                                     | Labels: `active` → Ativo, `inactive` → Inativo, `suspended` → Suspenso; estilo `slate-50` + `ring-slate-200` |
| Especialidade exibida   | `primarySpecialtyLabel` (computed)                | Primeira especialização em `selectedSpecializationsList`; se vazio, omitir linha                             |

```ts
// Computed sugerido — sem alterar ProfileController
const primarySpecialtyLabel = computed(() => {
    const first = selectedSpecializationsList.value[0];
    return first?.name ?? null;
});
```

(`selectedSpecializationsList` já existe ou equivalente no componente.)

#### Variante paciente (`auth.isPatient`)

- Mesmo card de avatar + ações de upload + nome (`text-2xl font-bold text-slate-950`).
- **Sem** badges CRM, status profissional, especialidade ou “Perfil completo”.

#### Variante sem role doctor/patient

- Hero reduzido: avatar + upload + nome apenas.

---

### Wireframes por seção

Ordem de renderização fixa (não reordenar).

#### Seção 1 — Hero (todos; badges conforme role acima)

```
┌─────────────────────────────────────────────────────────────┐
│ [Avatar size-20 sm:size-24, ring-4 ring-teal-50]            │
│ [badges doctor: Perfil completo | CRM verificado | Status]  │
│ [Nome — text-2xl font-bold text-slate-950]                  │
│ [Especialidade — text-sm text-slate-600] (doctor, se houver)│
│ [Botões upload / remover] [erro/sucesso inline teal/red]    │
│ [hint formatos — text-xs text-slate-500]                    │
└─────────────────────────────────────────────────────────────┘
```

Elimina o card atual “Foto de Perfil”. Upload permanece com `axios`; mensagem de sucesso via `uploadSuccess` inline (classes `teal-50`).

---

#### Seção 2 — Informações básicas (dentro do `<form>`, todos)

```
CONTA / Informações básicas / nome + email + aviso verificação
```

---

#### Seções 3–7 — Médico (dentro do `<form>`, condicional)

Registro profissional → Especialidades → Biografia → Consultas (fee + status) — conforme wireframes anteriores da spec (tokens atualizados).

Tags de especialização: `bg-teal-50 text-teal-800 ring-1 ring-teal-100`.

---

#### Seção 8 — Timeline (doctor, **fora** do `<form>`)

Card próprio com header:

- Eyebrow `TRAJETÓRIA` + título `Formação e certificações`
- Badge completo/incompleto no header (`isSecondStageComplete` / `timelineCompleted`)
- Aviso informativo: `rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700` (substitui azul)
- Botão “Adicionar Evento” + `<Timeline>` inalterado em lógica

Remover título solto “Segunda Etapa de Autenticação - Timeline Profissional”.

---

#### Seções 9–14 — Paciente (dentro do `<form>`, condicional)

**Mudança estrutural (decisão fechada):**

- Remover o bloco pai `<div class="border-t pt-6">` com `HeadingSmall` “Segunda Etapa de Autenticação” e badges globais.
- Cada área vira card independente: Emergência → Saúde → Dados físicos → Interoperabilidade → Cobertura → Consentimento.

**Card Emergência:**

- Header com badge incompleto/completo (`isSecondStageComplete` para patient).
- Card padrão `bg-white border-slate-200` (sem `bg-yellow-50`).

**Card Consentimento:**

- `border-slate-200 bg-slate-50` + ícone `ShieldCheck`.

**Footer do form (patient):**

- Bloco `recentlySuccessful` existente: trocar `green-*` → `teal-50` / `teal-800` / `teal-700`.

---

#### Footer do form

Botão `Salvar Alterações` com `bg-teal-500 text-slate-950 hover:bg-teal-400`; texto “Salvo.” em `text-slate-600`.

---

### Mapeamento de componentes

| Elemento atual        | Classe proposta                                                    | Notas                                 |
| --------------------- | ------------------------------------------------------------------ | ------------------------------------- |
| Card                  | `rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6` | Todos os cards                        |
| Header                | Eyebrow + `h2` inline                                              | Sem `HeadingSmall`                    |
| Avatar                | `Avatar` + `AvatarImage` + `AvatarFallback`                        | `useInitials`; fallback `?` se vazio  |
| Input/Textarea        | Sem classes custom de borda/radius                                 | Remover `rounded-xl border-primary/*` |
| Botão primário        | `bg-teal-500 text-slate-950 hover:bg-teal-400`                     | Submit e ações principais             |
| Badge completo        | `bg-teal-50 text-teal-800 ring-teal-100` + `CheckCircle2`          |                                       |
| Badge incompleto      | `bg-amber-50 text-amber-700 ring-amber-200` + `AlertCircle`        |                                       |
| Sucesso upload inline | `bg-teal-50 text-teal-800 ring-teal-100`                           | Manter `uploadSuccess` ref            |

---

### Padrões de tokens visuais

(inalterados em essência — palette `slate` + `teal` + `amber` + `red` para erros)

#### Padrão de campo

```
label:  text-slate-700 (Label shadcn, sem text-gray-800)
input:  componente shadcn SEM class override de radius/borda/ring primary
hint:   text-xs text-slate-500
erro:   InputError (sem alteração)
```

---

### Comportamento por componente

#### Avatar / Upload

- Fallback: `getInitials(user.name) || '?'` em `text-2xl font-semibold`
- Preview: `ring-2 ring-amber-300` no `Avatar`
- Uploading: overlay `bg-black/50` + `Loader2`
- **Sucesso:** `uploadSuccess = true` + div inline `teal-50` (não usar `useToast` nesta entrega)
- Erro: inline `red-50` com `ring-red-100`

#### Badges de completude

- Doctor timeline card + patient emergência card apenas
- Ícones: `CheckCircle2` / `AlertCircle` tamanho `size-3.5`

#### Dropdown especializações

- Bordas/focus `slate` / `teal`; lógica e listener `handleSpecializationsClickOutside` intactos

---

### Observações técnicas de implementação

1. Importar `useInitials`, `Avatar`, `AvatarImage`, `AvatarFallback`, `CheckCircle2`, `ShieldCheck` conforme necessário; remover `HeadingSmall`, `UserIcon` se obsoletos.
2. `<input type="file" ref="fileInputRef" class="hidden">` permanece **fora** do componente `Avatar`.
3. `<form>` envolve apenas cards de campos (seções 2 + doctor + patient) + footer; hero e timeline ficam fora.
4. Remover todos os `border-t pt-6` que separavam sub-seções do card monolítico antigo.
5. Manter `v-if` de role sem alterar expressões.
6. `confirm()` nativo permanece (fora de escopo).

---

## Plano de implementação

| #   | Escopo                 | Ação                                                                                                           |
| --- | ---------------------- | -------------------------------------------------------------------------------------------------------------- |
| 1   | Hero                   | Card com `Avatar` shadcn; variantes doctor/patient; computed `primarySpecialtyLabel`; badges conforme contrato |
| 2   | HeadingSmall           | Remover import/uso; eyebrow + `h2` em todos os cards                                                           |
| 3   | Cards                  | `rounded-lg border-slate-200` em toda a página                                                                 |
| 4   | Form structure         | Hero fora; cards dentro do form; timeline em card fora                                                         |
| 5   | Patient                | Desmontar wrapper “Segunda Etapa”; cards separados; badge só em emergência                                     |
| 6   | Inputs                 | Remover overrides `primary` / `rounded-xl`                                                                     |
| 7   | Botões / tags / badges | Tokens `teal` / `amber` / `slate`                                                                              |
| 8   | Timeline doctor        | Card + aviso `slate-50`; header com badge                                                                      |
| 9   | Feedback               | Upload inline teal; pós-salvar patient teal                                                                    |
| 10  | Imports                | Limpar não utilizados (`HeadingSmall`, `CheckCircle`, etc.)                                                    |

---

## Riscos técnicos

| Risco                                     | Prob. | Impacto | Mitigação                                                |
| ----------------------------------------- | ----- | ------- | -------------------------------------------------------- |
| Campo fora do `<form>` após refactor      | Média | Alto    | Checklist: todos os `v-model` do form dentro do `<form>` |
| `Select` nativo quebrado                  | Baixa | Alto    | Testar `blood_type` e `status` após remover wrappers     |
| Dropdown click-outside                    | Baixa | Médio   | Testar após mover container                              |
| `getInitials('')` vazio                   | Baixa | Baixo   | Fallback literal `'?'` no template                       |
| Remover wrapper patient e perder contexto | Baixa | Baixo   | Badge no card emergência + aviso pós-salvar mantidos     |

---

## Edge Cases

1. Médico sem especialidades → omitir linha de especialidade no hero; placeholder no dropdown mantido.
2. Preview cancelado → `cancelPreview()` remove `ring-amber-300`.
3. Nome vazio → `AvatarFallback` exibe `?` (não depender só de `getInitials('')` que retorna string vazia).
4. Role indefinida → hero simples + card conta apenas.
5. Doctor: `timelineCompleted` e badge “Perfil completo” alinhados à prop `timelineCompleted` do controller.

---

## Checklist

### Frontend

- [ ] Hero (doctor/patient/all) com `Avatar` + `useInitials` + ring `teal-50`
- [ ] `primarySpecialtyLabel` computed sem mudança no controller
- [ ] Badges hero doctor conforme contrato (CRM cosmético, status, perfil completo)
- [ ] Zero uso de `HeadingSmall` em `Profile.vue`
- [ ] Todos os cards `rounded-lg border-slate-200`
- [ ] Patient: wrapper “Segunda Etapa” removido; cards separados
- [ ] Badge completude patient só no card emergência
- [ ] Inputs sem `rounded-xl` / `border-primary/*`
- [ ] Botão salvar e tags `teal-*`
- [ ] Timeline em card fora do form; aviso `slate-50`
- [ ] Upload: `uploadSuccess` inline com classes `teal-*`
- [ ] Aviso pós-salvar patient com `teal-*`
- [ ] Submit, upload, dropdown, checkbox, timeline testados manualmente
- [ ] 375px e 768px verificados

### Qualidade

- [ ] Nenhuma lógica de negócio alterada
- [ ] Nenhum `v-model` / handler removido
- [ ] Imports mortos removidos
