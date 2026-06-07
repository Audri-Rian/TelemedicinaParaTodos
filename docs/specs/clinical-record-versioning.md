# Feature Spec — Versionamento de Registros Clínicos

> Status: `draft`
> Autor: Tech Lead Agent · Data: 2026-05-17

---

## Objetivo

Definir arquitetura, regras e critérios de aceite para versionamento auditável de **notas clínicas**, **prescrições** e **atestados**, alinhado a LGPD e CFM Res. 1.821/2007, resolvendo tensões entre o WIP local (`HasClinicalVersioning`) e o modelo append-only existente em `ClinicalNote`.

## Motivação

- WIP local entrega audit trail polimórfico + UI de histórico, mas **sem rotas de edição**, **dois modelos de versão em notas**, **campo `change_reason` morto** e **versão criada em `updating` antes do commit**.
- Pendência regulatória ([feature-interoperability-pendencias.md §639](feature-interoperability-pendencias.md)): prontuário não pode ser alterado, só anexado.
- Produto precisa decidir: edição in-place com auditoria vs imutabilidade pós-assinatura vs híbrido.

---

## Contexto encontrado (código)

| Artefato                                                 | Padrão identificado                                                                                                                     |
| -------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| `HasClinicalVersioning`                                  | Eventos `created`/`updating`; grava em `clinical_record_versions`; **não preenche `change_reason`**; versão em `updating` (risco órfão) |
| `ClinicalNote`                                           | **Duplo modelo**: colunas `version` + `parent_id` (append-only via `createClinicalNote`) **e** trait (audit in-place)                   |
| `MedicalRecordClinicalActionService::createClinicalNote` | Incrementa `version`/`parent_id`; só POST create hoje                                                                                   |
| Rotas doctor                                             | POST create; GET versions; **sem PATCH/PUT**                                                                                            |
| `MedicalRecordPolicy::viewVersionHistory`                | Médico com acesso **ou** paciente dono                                                                                                  |
| UI                                                       | `VersionHistoryModal.vue` — rota `/doctor/...`; botão só em `context.mode === 'doctor'`                                                 |
| Observers                                                | `PrescriptionObserver`, `MedicalCertificateObserver` bloqueiam edição de conteúdo pós-assinatura                                        |
| Jobs assinatura                                          | `SignPrescriptionJob` etc. alteram campos fora de `versionedFields`                                                                     |
| Escopo atual                                             | Notas, prescrições, atestados — **não** uploads, exames, diagnósticos                                                                   |

---

## Decisões pendentes

Cada item: tensão → opções → **recomendação Tech Lead (default: opção C híbrida)**.

| #   | Tensão                                                          | Opções                                                                                                                                         | Recomendação                                                                                                                                                                                                                                                                                                                               |
| --- | --------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Sem rotas PATCH/PUT — histórico só v1                           | **A** Implementar update + versionamento · **B** Manter só create + append-only · **C** Update pré-assinatura; pós-assinatura só novo registro | **C** — rotas `update` para rascunho; após `signed_at`/status assinado, bloquear update e usar correção via novo registro (`parent_id` em notas; equivalente em prescrição/atestado se necessário)                                                                                                                                         |
| 2   | Dois modelos em `ClinicalNote` (`version`/`parent_id` vs trait) | **A** Só audit trail in-place · **B** Só append-only · **C** Híbrido unificado                                                                 | **C** — **Correções pós-assinatura / CFM**: novo `ClinicalNote` com `parent_id` (cadeia append-only). **Edições pré-assinatura**: update in-place + linhas em `clinical_record_versions`. Descontinuar incremento de `clinical_notes.version` em updates in-place; `version`/`parent_id` = linhagem de “emenda”, não número do audit trail |
| 3   | CFM Res. 1.821/2007 — alterar vs anexar                         | **A** Audit trail permite editar · **B** Imutável total · **C** Híbrido regulatório                                                            | **C** — Conteúdo clínico **assinado** tratado como imutável (observer + policy). Ajustes = novo registro vinculado + motivo. Audit trail documenta edições **permitidas** (pré-assinatura) e metadados de emenda                                                                                                                           |
| 4   | `change_reason` nunca preenchido                                | **A** Obrigatório em todo update · **B** Opcional · **C** Obrigatório só em update e emenda                                                    | **C** — `change_reason` required (min 10 chars) em `UpdateClinical*Request` e em POST de emenda (`parent_id`); v1 em `created` pode ser `null` ou auto `"Criação inicial"`                                                                                                                                                                 |
| 5   | Paciente na policy sem rota/UI                                  | **A** Expor histórico ao paciente · **B** Só médico · **C** Paciente read-only, sem diff sensível                                              | **C** — Rota patient GET versions; UI read-only; ocultar `is_private` e campos internos; sem botão de edição                                                                                                                                                                                                                               |
| 6   | Sem testes                                                      | **A** Feature + unit mínimos · **B** Só feature · **C** Suite completa versionamento                                                           | **C** — cobrir trait, policy, observers, controllers, regressão órfã                                                                                                                                                                                                                                                                       |
| 7   | Versão em `updating` antes do commit                            | **A** Manter · **B** `saved` + transação · **C** Service explícito                                                                             | **B** — Gravar versão em `updated`/`saved` após persistência bem-sucedida, ou dentro de `DB::transaction` no Service; nunca criar linha se save falhar                                                                                                                                                                                     |
| 8   | Escopo (uploads, exames, diagnósticos)                          | **A** Expandir agora · **B** Fora de escopo · **C** Fase 2                                                                                     | **B/C** — Esta spec: **notas, prescrições, atestados** apenas. Fase 2: documentos uploadados (imutabilidade delete), exames, diagnósticos                                                                                                                                                                                                  |

**Decisão arquitetural consolidada (default produto): Opção C — Híbrido regulatório**

---

## Regras de negócio

1. Todo registro versionável nasce com versão audit `version_number = 1` em `clinical_record_versions`.
2. **Pré-assinatura** (status rascunho / sem `signed_at`): médico autorizado pode **atualizar** campos em `$versionedFields`; cada update exige `change_reason`.
3. **Pós-assinatura**: update de campos clínicos em `$versionedFields` **proibido** (observer + policy + FormRequest). Correção = **novo registro** (nota: `parent_id`; prescrição/atestado: definir `supersedes_id` ou padrão equivalente na Fase 1 se ainda não existir coluna).
4. Alterações de assinatura/PDF (`SignPrescriptionJob`, etc.) **não** geram versão clínica de conteúdo; opcional: versão tipo `metadata` ou audit log separado (`auditService` já existente).
5. Paciente pode **visualizar** histórico do próprio prontuário (registros não privados); não edita nem vê notas `is_private`.
6. Médico só vê histórico de pacientes com vínculo de acesso (`doctorCanAccessPatient`).
7. Retenção: versões seguem política LGPD do prontuário — sem delete físico de `clinical_record_versions` (soft policy; anonimização em fluxo LGPD existente).

---

## Requisitos funcionais

| ID    | Requisito                                                                                      |
| ----- | ---------------------------------------------------------------------------------------------- |
| RF-01 | Médico atualiza nota/prescrição/atestado em rascunho via PATCH com `change_reason`             |
| RF-02 | Sistema registra diff (`changed_fields`, `old_values`, `new_values`) por versão                |
| RF-03 | Médico consulta timeline de versões (já existe GET doctor)                                     |
| RF-04 | Paciente consulta timeline read-only (nova rota patient)                                       |
| RF-05 | Pós-assinatura: tentativa de update retorna 403/422 com mensagem clara                         |
| RF-06 | Correção pós-assinatura em nota: POST com `parent_id` + `change_reason` (append-only)          |
| RF-07 | UI médico: botão Histórico + fluxo de edição com modal de motivo                               |
| RF-08 | UI paciente: visualização de histórico sem ações de edição                                     |
| RF-09 | Emenda de nota aparece na timeline do registro raiz (agregação por `parent_id` ou link na API) |

---

## Requisitos não-funcionais

| ID     | Requisito                                                                                |
| ------ | ---------------------------------------------------------------------------------------- |
| RNF-01 | Versionamento atômico com persistência do registro (sem versões órfãs)                   |
| RNF-02 | Histórico GET paginável se > 50 versões (futuro; v1 pode limitar 100)                    |
| RNF-03 | Latência GET versions < 200ms p95 em registro típico (< 20 versões)                      |
| RNF-04 | Logs de auditoria LGPD em acesso a histórico (reutilizar `AuditAccess` / `auditService`) |
| RNF-05 | Testes automatizados cobrindo fluxos críticos antes de merge                             |

---

## Arquitetura proposta

```
[UI Edit + motivo]
    → PATCH /doctor/.../notes|prescriptions|certificates/{id}
    → UpdateClinical*Request (change_reason, campos versionados)
    → MedicalRecordPolicy@updateClinicalRecord
    → MedicalRecordClinicalActionService::update* (transaction)
         → Model::update (campos permitidos)
         → HasClinicalVersioning::recordVersionOnSaved (após commit)
    → JSON / Inertia reload

[Pós-assinatura correção nota]
    → POST .../notes (parent_id, change_reason)
    → createClinicalNote (append-only, sem update in-place)

[Histórico]
    → GET doctor|patient .../versions
    → resolveModel + versions()->with('changedBy')
```

**Padrões reutilizados**

- `MedicalRecordClinicalActionService` — novos métodos `updateClinicalNote`, `updatePrescription`, `updateMedicalCertificate`
- `MedicalRecordPolicy` — `updateClinicalRecord`, manter `viewVersionHistory`
- `HasClinicalVersioning` — refatorar: extrair gravação para método chamado pós-save; preencher `change_reason` de request/context
- `PrescriptionObserver` / `MedicalCertificateObserver` — manter bloqueio; alinhar lista de campos com `$versionedFields`
- `VersionHistoryModal.vue` — reuso; parametrizar base URL doctor vs patient

**Não criar**

- Novo modelo de versão paralelo; unificar em `clinical_record_versions`

---

## Frontend

### Componentes

| Componente                              | Novo/Reutilizado | Alteração                                                |
| --------------------------------------- | ---------------- | -------------------------------------------------------- |
| `VersionHistoryModal.vue`               | Reutilizado      | Prop `apiBase` ou `audience: doctor \| patient`          |
| Tabs (Notes/Prescriptions/Certificates) | Reutilizado      | Botão Editar (pré-assinatura); modal `ChangeReasonModal` |
| `ChangeReasonModal.vue`                 | Novo             | Captura motivo antes de submit PATCH                     |

### Estados de UI

- **Loading:** skeleton na timeline e no form de edição
- **Erro:** toast via `useToast`; 403 pós-assinatura → mensagem “Registro assinado. Crie uma emenda.”
- **Vazio:** “Nenhuma alteração registrada” (só v1)
- **Sucesso:** toast + refresh lista + histórico atualizado

### Rotas Inertia / API

| Método | Rota (doctor)                                                    | Ação                       |
| ------ | ---------------------------------------------------------------- | -------------------------- |
| PATCH  | `patients/{patient}/medical-record/notes/{note}`                 | `updateClinicalNote`       |
| PATCH  | `patients/{patient}/medical-record/prescriptions/{prescription}` | `updatePrescription`       |
| PATCH  | `patients/{patient}/medical-record/certificates/{certificate}`   | `updateMedicalCertificate` |
| GET    | `patients/{patient}/medical-record/{type}/{record}/versions`     | existente                  |
| GET    | `patient/medical-record/{type}/{record}/versions`                | **novo** (patient.php)     |

---

## Backend

### Endpoints

| Método | Rota                        | Controller                                                      | FormRequest                       |
| ------ | --------------------------- | --------------------------------------------------------------- | --------------------------------- |
| PATCH  | doctor …/notes/{note}       | `DoctorPatientMedicalRecordController@updateClinicalNote`       | `UpdateClinicalNoteRequest`       |
| PATCH  | doctor …/prescriptions/{id} | `@updatePrescription`                                           | `UpdatePrescriptionRequest`       |
| PATCH  | doctor …/certificates/{id}  | `@updateMedicalCertificate`                                     | `UpdateMedicalCertificateRequest` |
| GET    | patient …/versions          | `PatientMedicalRecordController@showVersionHistory` (ou shared) | —                                 |

### Service

- `MedicalRecordClinicalActionService::updateClinicalNote` — valida status, transação, set `change_reason` no contexto do trait
- Idem prescrição/atestado com `$versionedFields` respectivos

### Validações (FormRequest)

- `change_reason`: `required|string|min:10|max:500` em updates e emendas
- Campos: subset de `$versionedFields`; proibir `status`/`signed_*` via usuário se controlado por job
- `parent_id`: exists, mesmo `patient_id`, registro pai assinado ou imutável

### Autorização

- `MedicalRecordPolicy::updateClinicalRecord` — só médico com acesso; negar se assinado
- `viewVersionHistory` — manter; paciente só próprio prontuário
- Middleware: `auth`, escopo doctor/patient nas rotas respectivas

---

## Banco de dados

### Estado atual (manter)

- `clinical_record_versions` — polimórfico, índice `(versionable_type, versionable_id, version_number)`

### Migrations possíveis (Fase implementação)

| Alteração                                                                         | Motivo                                        |
| --------------------------------------------------------------------------------- | --------------------------------------------- |
| `prescriptions.supersedes_id`, `medical_certificates.supersedes_id` (nullable FK) | Emenda pós-assinatura simétrica a `parent_id` |
| Unique `(versionable_type, versionable_id, version_number)`                       | Evitar corrida em concorrência                |
| CHECK ou app-level: `change_reason` NOT NULL quando `version_number > 1`          | Integridade regulatória (opcional DB)         |

### Relacionamentos

- `ClinicalNote` morphMany `versions`; self-ref `parent_id` para cadeia de emendas
- `ClinicalRecordVersion` belongsTo `changedBy` (User)

---

## Segurança e conformidade

| Tema            | Tratamento                                                                                         |
| --------------- | -------------------------------------------------------------------------------------------------- |
| LGPD            | Histórico = dado sensível; log de acesso; paciente não vê `is_private`; export LGPD inclui versões |
| CFM 1.821/2007  | Imutabilidade pós-assinatura; emenda auditada; sem delete de versões                               |
| Autorização     | Policy em todo GET/PATCH; paciente sem PATCH                                                       |
| Mass assignment | `$fillable` restrito; FormRequest whitelist                                                        |
| XSS             | Diff renderizado escapado no Vue (`{{ }}` / sanitize JSON)                                         |

**Riscos regulatórios**

- Audit trail in-place **sem** bloqueio pós-assinatura → não conformidade CFM
- Permitir paciente ver `old_values` de campos removidos → vazamento de dado suprimido na UI atual

---

## Edge Cases

1. Update sem campos versionados dirty → 422 ou 204 sem nova versão
2. Save falha após validação → zero linhas novas em `clinical_record_versions`
3. Concorrência: dois PATCH simultâneos → unique version_number ou lock pessimista no Service
4. Job de assinatura altera `status` → não dispara versão de conteúdo (excluir de `$versionedFields` ou `isDirty` filtrado)
5. Nota emenda (`parent_id`) → timeline agregada mostra cadeia; versão 1 do filho não substitui pai
6. Médico perde acesso ao paciente → GET versions 403
7. Registro órfão pré-refactor → script de limpeza one-off (fora escopo feature)

---

## Testes (critérios)

| Suite                        | Casos mínimos                                                                               |
| ---------------------------- | ------------------------------------------------------------------------------------------- |
| Unit `HasClinicalVersioning` | created v1; update gera v2 com diff; falha save não cria versão; `change_reason` persistido |
| Feature PATCH                | 200 pré-assinatura; 403 pós-assinatura; 422 sem motivo                                      |
| Feature GET versions         | doctor ok; patient ok próprio; patient 403 outro                                            |
| Feature POST emenda          | `parent_id` incrementa cadeia; motivo obrigatório                                           |
| Observer                     | update campo clínico pós-assinatura bloqueado                                               |
| Policy                       | `viewVersionHistory`, `updateClinicalRecord` matrices                                       |

---

## Critérios de aceite

- [ ] Médico edita rascunho com motivo; timeline mostra v2+ com diff correto
- [ ] PATCH pós-assinatura rejeitado; mensagem orienta emenda
- [ ] Emenda de nota via `parent_id` visível na UI sem apagar registro assinado
- [ ] Paciente acessa histórico read-only na rota patient
- [ ] Nenhuma versão órfã em teste que força falha de save
- [ ] `change_reason` presente em todas versões > 1
- [ ] Documentação interna atualizada: pendência §639 parcialmente endereçada (escopo notas/prescrições/atestados)
- [ ] `php artisan test` com testes novos passando

---

## Riscos técnicos

| Risco                                                  | Prob. | Impacto | Mitigação                                          |
| ------------------------------------------------------ | ----- | ------- | -------------------------------------------------- |
| Duplicidade `ClinicalNote.version` vs `version_number` | Alta  | Alto    | Spec de linhagem vs audit; documentar no Service   |
| Versões órfãs (bug atual)                              | Média | Alto    | Mover para evento pós-save + testes                |
| CFM interpretado como proibir qualquer update          | Média | Alto    | Híbrido + revisão jurídica                         |
| Jobs assinatura geram versões espúrias                 | Média | Médio   | Excluir campos de assinatura de `$versionedFields` |
| Timeline paciente expõe dados sensíveis                | Baixa | Alto    | Filtrar props na API patient                       |

---

## Plano de implementação

1. **[Decisão]** Product/jurídico valida Opção C (esta spec)
2. **[Backend]** Refatorar `HasClinicalVersioning` (pós-save, `change_reason`)
3. **[Backend]** `Update*Request` + PATCH routes + Service updates
4. **[Backend]** Policy `updateClinicalRecord`; rota patient GET versions
5. **[Backend]** Unificar semântica `parent_id` / emenda; migration `supersedes_id` se necessário
6. **[Frontend]** `ChangeReasonModal` + fluxo edit; `VersionHistoryModal` patient
7. **[Testes]** Unit trait + Feature PATCH/GET + Observer
8. **[Docs]** Atualizar `feature-interoperability-pendencias.md` §7.6 e §639 com escopo real

---

## Checklist (implementação futura)

### Backend

- [ ] Trait grava versão apenas após save bem-sucedido
- [ ] `change_reason` obrigatório em updates/emendas
- [ ] PATCH endpoints + FormRequests
- [ ] Policy update + patient GET versions
- [ ] Alinhar observers e `$versionedFields`

### Frontend

- [ ] Edição pré-assinatura com motivo
- [ ] Histórico paciente read-only
- [ ] Mensagens CFM-friendly pós-assinatura

### Qualidade

- [ ] Testes listados na seção Testes
- [ ] Sem versões órfãs
- [ ] Revisão LGPD/CFM registrada

---

## Fora de escopo (esta entrega)

- Versionamento de documentos uploadados, exames, diagnósticos
- Assinatura ICP-Brasil / validade legal de PDF
- Delete de versões ou compactação de histórico
- Filas/Jobs novos (assinatura continua via jobs existentes)
