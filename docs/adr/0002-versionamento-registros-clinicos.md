# ADR-0002: Versionamento de registros clínicos com diff auditável

**Status:** accepted  
**Data:** 2026-05-17  
**Decisores:** Tech Lead, Dev Backend, Dev Frontend  
**Spec de origem:** [`docs/specs/clinical-record-versioning.md`](../specs/clinical-record-versioning.md)  
**Relacionado:** CFM Res. 2.314/2022, LGPD Art. 18, ADR-0001

---

## Contexto

Notas clínicas, prescrições e atestados podem ser editados antes da assinatura digital. Sem rastreabilidade, qualquer alteração seria invisível — risco regulatório (CFM exige auditabilidade do prontuário) e de compliance LGPD (paciente tem direito de saber o que foi alterado em seus dados).

Problemas a resolver:

1. **Rastreabilidade**: cada edição deve registrar quem alterou, quando, o que mudou e por quê.
2. **Diff**: é preciso mostrar o valor anterior e o novo para auditoria médica.
3. **Privacidade do paciente**: paciente pode saber _quais_ campos mudaram, mas não deve ver o conteúdo clínico de versões anteriores (dado sensível de saúde — LGPD).
4. **Imutabilidade pós-assinatura**: documentos assinados via ICP-Brasil não podem ser editados in-place; a versão deve ser bloqueada.
5. **Atomicidade**: uma versão órfã (registro de "mudança" sem a mudança real no modelo) é pior que ausência de versão.

---

## Decisão

**Tabela polimórfica `clinical_record_versions` + trait `HasClinicalVersioning` nos modelos versionáveis.**

Cada edição bem-sucedida gera uma linha imutável com `old_values`, `new_values`, `changed_fields`, `changed_by` e `change_reason`. O motivo da edição é obrigatório (≥ 10 caracteres). Médico vê diff completo; paciente vê apenas nomes dos campos alterados.

---

## Alternativas consideradas

| Alternativa                                                      | Por que descartada                                                                                                                                                                                        |
| ---------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tabela de versão por modelo** (ex.: `prescription_versions`)   | Duplica estrutura para 3+ modelos. Queries de auditoria cruzada exigem UNION. Toda nova entidade versionável exige nova tabela.                                                                           |
| **Coluna `history` JSON no próprio modelo**                      | Sem FK auditável para `users`. Cresce indefinidamente na linha. Difícil de indexar ou consultar por versão específica.                                                                                    |
| **Capturar diff no evento `updating` e persistir imediatamente** | **Bug crítico**: se o save falhar (ex.: Observer bloqueia prescrição assinada), a linha de versão persiste sem o correspondente estado salvo no modelo — versão órfã.                                     |
| **Event Sourcing completo**                                      | Overkill para o volume e requisito atual. Adicionaria complexidade de replay de eventos sem benefício imediato.                                                                                           |
| **Append-only em `clinical_notes` via `parent_id`**              | Já existia para notas clínicas pós-assinatura (novo registro, não edição). Semânticas diferentes: `parent_id` = nova nota vinculada (imutável); trait = edição in-place auditada. Coexistem sem conflito. |

---

## Implementação

### Fluxo de captura de versão

```
updating  →  captura diff em $_pendingVersion (memória, não persiste)
             (getOriginal() só disponível antes do save)
updated   →  dispara apenas se save teve sucesso → persiste versão
             (sem save bem-sucedido = sem versão = sem órfão)
```

O Service chama `$model->setVersionChangeReason($reason)` antes de `$model->update()`. O trait lê o motivo no evento `updating` e o embute no `$_pendingVersion`.

### Bloqueio pós-assinatura

`PrescriptionObserver` e `MedicalCertificateObserver` lançam `ValidationException` no `updating` quando `signature_status ∈ {signed, verified}`. Isso cancela o save antes do trait processar o diff → `updated` nunca dispara → nenhuma versão criada. Não foi necessário duplicar a lógica de bloqueio no trait.

### Separação de audience (LGPD)

| Audience  | Endpoint                                                                 | Resposta                                                                                              |
| --------- | ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------- |
| `doctor`  | `GET /doctor/patients/{patient}/medical-record/{type}/{record}/versions` | `version_number`, `changed_by`, `changed_fields`, `old_values`, `new_values`, `change_reason`         |
| `patient` | `GET /patient/medical-records/{type}/{record}/versions`                  | `version_number`, `changed_by`, `changed_fields`, `change_reason` — **sem `old_values`/`new_values`** |

O `VersionHistoryModal` Vue recebe prop `audience: 'doctor' | 'patient'` e renderiza diff completo ou lista de campos, respectivamente.

### Constraint de unicidade

Índice único em `(versionable_type, versionable_id, version_number)` previne race condition em concorrência: dois workers simultâneos no mesmo registro gerariam `version_number` duplicado → um falha e faz rollback, mantendo consistência.

### Campos monitorados por modelo

| Modelo               | `$versionedFields`                                                           |
| -------------------- | ---------------------------------------------------------------------------- |
| `ClinicalNote`       | `title`, `content`, `is_private`, `category`, `tags`                         |
| `Prescription`       | `medications`, `instructions`, `valid_until`, `status`                       |
| `MedicalCertificate` | `type`, `start_date`, `end_date`, `days`, `reason`, `restrictions`, `status` |

Campos de infra (`doctor_id`, `appointment_id`, `patient_id`) não são versionados — mudança de vínculo é operação administrativa, não clínica.

---

## Consequências

**Positivas:**

- Auditabilidade completa de prontuário: quem, quando, o quê, por quê — requisito CFM.
- Paciente pode consultar histórico de campos alterados sem exposição de conteúdo clínico sensível — requisito LGPD Art. 18.
- Atomicidade garantida: versão só existe se a mudança existiu.
- Extensível: qualquer novo Model versionável adiciona `use HasClinicalVersioning` + `$versionedFields`.
- Imutabilidade pós-assinatura reutiliza lógica já existente nos Observers — sem duplicação.

**Negativas / trade-offs:**

- `change_reason` obrigatório (≥ 10 chars) adiciona atrito em cada edição — decisão consciente para forçar rastreabilidade clínica significativa.
- Versão 1 é criada no evento `created` e inclui todos os campos com `old_values = []`. Para modelos com muitos campos JSON grandes (ex.: `medications`), isso aumenta o volume de dados armazenados desde o primeiro registro.
- `changed_by` é FK para `users` (não-nulo). Fallback quando não há usuário autenticado usa `$model->doctor->user_id` — exige que o model tenha relação `doctor` carregada ou disponível.

**Riscos residuais:**

| Risco                                                       | Mitigação atual                                                                                                 | Fase                                                          |
| ----------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| Race condition em `version_number`                          | Unique index + DB transaction no service                                                                        | Coberto                                                       |
| Versão criada em operação de sistema sem auth (job de fila) | Fallback para `doctor->user_id`; jobs que editam registros clínicos devem passar `change_reason` explicitamente | Verificar antes de criar jobs que alterem modelos versionados |
| Crescimento de tabela em alta escala                        | Sem paginação no endpoint de versões ainda                                                                      | Adicionar paginação quando volume justificar                  |

---

## O que vem depois

- [ ] Paginação no endpoint de versões
- [ ] Endpoint de restauração para versão anterior (revert)
- [ ] Exibir histórico de versões para o paciente na UI (atualmente só médico vê)
- [ ] Versionamento de `VitalSignEntry` e `Diagnosis` (avaliar necessidade regulatória)
