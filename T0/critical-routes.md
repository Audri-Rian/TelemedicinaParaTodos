# Escopo de Rotas Críticas — v1

> **Esta lista é fechada para a versão v1. Alterações exigem revisão.**

## O que é

Uma lista fechada (curta e explícita) dos fluxos mais sensíveis do sistema, onde:
- **Existe dado médico**
- **Existe impacto legal**
- **Existe risco de abuso** (IDOR, vazamento, fraude)

Nada fora dessa lista entra por enquanto.

---

## Por que é importante

Sem isso:
- Tudo vira "crítico" → ninguém revisa nada direito
- Ou nada é crítico → falha grave passa despercebida

Isso define **prioridade absoluta** para auditoria, testes e revisão de código.

---

## Rotas críticas v1

### 1. Prontuários médicos

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `doctor/patients/{patient}/medical-record` | GET | Visualizar prontuário | IDOR (acessar paciente de outro médico) |
| `doctor/patients/{patient}/medical-record/export` | POST | Exportar PDF completo | Vazamento em massa |
| `patient/medical-records` | GET | Visualizar próprio prontuário | IDOR (paciente ver outro) |
| `patient/medical-records/export` | POST | Exportar próprio prontuário | Fraude, vazamento |
| `doctor/consultations/{appointment}` | GET | Detalhe da consulta (dados clínicos) | IDOR |
| `doctor/consultations/{appointment}/save-draft` | POST | Salvar rascunho clínico | Injeção, adulteração |
| `doctor/consultations/{appointment}/finalize` | POST | Finalizar consulta | Fraude (encerrar sem conclusão) |
| `doctor/consultations/{appointment}/complement` | POST | Complementar consulta finalizada | Edição indevida |
| `doctor/consultations/{appointment}/pdf` | GET | Gerar PDF da consulta | Download não autorizado |
| `patient/consultation-details/{appointment}` | GET | Paciente ver detalhes da consulta | IDOR |

**Entrada clínica (via DoctorConsultationDetailController):**
- `save-draft`: queixa principal, exame físico, diagnóstico, CID-10, instruções
- `finalize`: consolida o registro e dispara notificações
- `complement`: notas complementares (após finalização)

---

### 2. Consultas / Appointments

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `appointments` | POST | Criar agendamento | Criação fraudulenta |
| `appointments/{id}` | GET | Visualizar consulta | IDOR |
| `appointments/{id}` | PUT/PATCH | Atualizar consulta | Alteração indevida |
| `appointments/{id}` | DELETE | Excluir consulta | Perda de evidência |
| `appointments/{id}/start` | POST | Iniciar consulta | Fraude (iniciar sem paciente) |
| `appointments/{id}/end` | POST | Encerrar consulta | Fraude |
| `appointments/{id}/cancel` | POST | Cancelar consulta | Abuso, impacto legal |
| `appointments/{id}/reschedule` | POST | Reagendar | Confusão de horários, IDOR |

**Políticas:** `AppointmentPolicy` (view, create, update, delete, start, end, cancel, reschedule).

---

### 3. Prescrições médicas

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `doctor/patients/{patient}/medical-record/prescriptions` | POST | Criar prescrição | Emissão indevida, fraude |
| Prescrições visíveis em `medical-record` e `consultations/{appointment}` | GET | Visualizar | IDOR |

**Nota:** Prescrições são criadas via prontuário; não há rota dedicada de listagem separada. Download/visualização ocorre no contexto do prontuário e do PDF da consulta.

---

### 4. Uploads clínicos / Documentos do prontuário

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `doctor/patients/{patient}/medical-record/documents` | POST | Upload (médico) | IDOR, upload malicioso |
| `patient/medical-records/documents` | POST | Upload (paciente) | Envio indevido, malware |
| **Download/visualização** | GET | `/storage/medical-records/uploads/{patient_id}/{file}` | **IDOR crítico** — path exposto no frontend |

**Categorias:** exame, prescrição, laudo, outros.  
**Atenção:** O frontend usa `getDocumentUrl(path)` → `/storage/${path}`. O Laravel serve `public/storage` via symlink. Se não houver rota protegida, documentos clínicos podem ser acessados por path guessing. **Recomendação:** criar rota autenticada para download (ex.: `GET /api/medical-documents/{id}/download`) com checagem de Policy.

---

### 5. Componentes adicionais do prontuário (médico)

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `doctor/patients/{patient}/medical-record/diagnoses` | POST | Registrar diagnóstico | CID-10 indevido |
| `doctor/patients/{patient}/medical-record/examinations` | POST | Solicitar exame | Fraude |
| `doctor/patients/{patient}/medical-record/notes` | POST | Anotação clínica | Dado sensível |
| `doctor/patients/{patient}/medical-record/certificates` | POST | Emitir atestado | Fraude, impacto legal |
| `doctor/patients/{patient}/medical-record/vital-signs` | POST | Registrar sinais vitais | Dado sensível |
| `doctor/patients/{patient}/medical-record/consultations/pdf` | POST | PDF de consulta específica | Download não autorizado |

---

### 6. Salas de vídeo (videoconferência)

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `doctor/video-call/request/{user}` | POST | Iniciar chamada (médico) | Acesso sem consulta ativa |
| `doctor/video-call/request/status/{user}` | POST | Aceitar/rejeitar chamada | Interceptação |
| `patient/video-call/request/{user}` | POST | Iniciar chamada (paciente) | Mesmo |
| `patient/video-call/request/status/{user}` | POST | Aceitar/rejeitar chamada | Mesmo |

**Validação atual:** `ensureActiveAppointment()` — exige consulta ativa ou dentro da janela de lead time entre médico e paciente.  
**Canais:** `channels.php` — `video-call.{id}`, `appointment.{participantId}`. Entrada na sala exige autenticação via broadcast.

---

### 7. Notificações sensíveis

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `api/notifications` | GET | Listar notificações | Vazamento de conteúdo clínico |
| `api/notifications/{id}` | GET | Ver notificação | IDOR |
| `api/notifications/{id}/read` | POST | Marcar como lida | Baixo |

**Tipos de notificação clínica (templates em `resources/views/notifications/`):**
- `appointment_created`, `appointment_cancelled`, `appointment_reminder`, `appointment_rescheduled`
- `prescription_issued`, `medical_certificate_issued`, `examination_requested`

Conteúdo pode incluir dados médicos; API filtra por `user_id`, mas `{id}` deve ser validado contra ownership.

---

### 8. LGPD / Dados sensíveis

| Rota | Método | Ação | Risco |
|------|--------|------|-------|
| `lgpd/data-portability/export` | GET | Exportar todos os dados do usuário | Vazamento em massa |
| `lgpd/right-to-be-forgotten/request` | POST | Solicitar exclusão | Perda indevida, fraude |
| `lgpd/consents/grant`, `lgpd/consents/revoke` | POST | Consentimentos | Impacto legal |
| `lgpd/data-access-report/generate` | POST | Relatório de acessos | Exposição de auditoria |

---

## Rotas compartilhadas (auth genérica)

As rotas abaixo estão em `Route::middleware(['auth','verified'])` sem `doctor`/`patient`:

| Rota | Observação |
|------|------------|
| `appointments/*` | Policy `AppointmentPolicy` filtra por papel |
| `api/messages/*` | Mensagens entre médico/paciente — dado sensível |
| `api/timeline-events/*` | Timeline do usuário — verificar se inclui dados clínicos |

**Recomendação:** Garantir que Policies e middlewares restrinjam acesso por papel onde necessário.

---

## Resumo — 9 domínios críticos

1. **Prontuários** — visualização, export, rascunho, finalização, complemento, PDF
2. **Consultas/Appointments** — CRUD, start, end, cancel, reschedule
3. **Prescrições** — criação (via prontuário)
4. **Uploads clínicos** — upload médico/paciente, **download (risco IDOR)**
5. **Componentes do prontuário** — diagnósticos, exames, notas, atestados, sinais vitais, PDF de consulta
6. **Videoconferência** — request, status (com validação de appointment ativo)
7. **Notificações** — listagem e visualização (podem conter dados clínicos)
8. **LGPD** — portabilidade, direito ao esquecimento, consentimentos, relatório de acessos
9. **Mensagens** — API de mensagens (médico ↔ paciente)

---

## Checklist de revisão para rotas críticas

Para cada rota desta lista, validar:
- [ ] Policy aplicada e verificando ownership/vínculo médico-paciente
- [ ] Rate limiting onde houver export/download em massa
- [ ] Auditoria de acesso (MedicalRecordAuditLog, DataAccessLog)
- [ ] Validação de entrada (request validation)
- [ ] Nenhum path de arquivo exposto sem checagem de autorização

---

## Observações de segurança (análise do projeto)

1. **Documentos clínicos:** O frontend usa `/storage/${file_path}` para exibir PDFs/imagens. O Laravel serve `public/storage` via symlink. Se o path for previsível, há risco de IDOR. Avaliar rota protegida para download.
2. **Avatares:** Já existe rota custom `storage/avatars/{userId}/{filename}` com checagem de existência; não há checagem explícita de ownership.
3. **Broadcasting:** Canais `video-call.{id}`, `messages.{id}`, `appointment.{participantId}`, `notifications.{id}` validam que o usuário autenticado é o dono do canal.

---

*Documento gerado a partir da análise de `routes/web.php`, `routes/auth.php`, `routes/settings.php`, `routes/channels.php` e controllers associados.*
