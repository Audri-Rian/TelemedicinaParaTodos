# Telemedicina Para Todos - Checklist Completo de Pendencias
## Branch: development (+ feature/interoperability)

Gerado em: 2026-04-14
Fonte: analise completa do codigo, docs/TrueIssues.md, execute/, docs/Tasks/

---

# SEGURANCA (CRITICO)

### bootstrap/app.php
- [ ] Reativar middleware SecurityHeaders (linha 24 - comentado como "TEMPORARIAMENTE DESATIVADO")
  - Sem isso: faltam CSP, HSTS, X-Frame-Options (vulneravel a XSS, clickjacking)

### routes/api.php
- [ ] Adicionar middleware ValidateWebhookSignature na rota POST /webhooks/lab/{partnerSlug} (linha 27)
  - Sem isso: qualquer pessoa pode enviar webhooks falsos

### app/Http/Requests/Doctor/MedicalRecords/StorePrescriptionRequest.php
- [ ] Implementar authorize() corretamente (atualmente retorna true sempre)
  - Sem isso: qualquer usuario autenticado pode criar prescricoes para qualquer paciente

### app/Http/Controllers/AppointmentsController.php
- [ ] Validar query parameters (status, etc.) antes de usar como filtro (linhas 44-62)

### routes/web.php
- [ ] Validar formato de userId e filename na rota de avatars (linhas 187-201) - risco de path traversal

---

# CONFORMIDADE CFM (CRITICO - BLOQUEANTE)

### 1. Assinatura Digital ICP-Brasil (Art. 8, Res. 2.314/2022)
- [ ] Contratar provedor de certificacao digital (Soluti, Certisign, Safeweb)
- [ ] Implementar DigitalSignatureService.php
- [ ] Adicionar campos signature_hash e verification_code no model Prescription
- [ ] Criar migration para atualizar tabela prescriptions
- [ ] Integrar fluxo de assinatura no frontend
- [ ] Validar certificado antes de emissao de documentos
  - IMPACTO: Sem isso, prescricoes e atestados NAO TEM VALIDADE LEGAL

### 2. Documentacao Legal CFM
- [ ] Adicionar secao "Consentimento para Telemedicina" na politica de privacidade
- [ ] Adicionar secao "Prontuario Eletronico" (armazenamento, retencao 20 anos)
- [ ] Adicionar secao "Gravacao de Consultas" (consentimento especifico)
- [ ] Adicionar secao "Documentos Medicos Digitais" (validade legal, ICP-Brasil)
- [ ] Expandir secao "Protocolo de Emergencias" (SAMU 192, Bombeiros 193)
- [ ] Adicionar secao "Responsabilidades do Medico" (CRM + UF)
- [ ] Implementar Termo de Consentimento Livre e Esclarecido (Art. 4 e 5)

---

# VIDEOCONFERENCIA (70% implementado)

- [ ] Tornar appointment_id obrigatorio em VideoCallRoom + migration
- [ ] Implementar locks de concorrencia com Redis (evitar chamadas simultaneas)
- [ ] Configurar TURN server (Coturn ou Twilio) para NAT traversal
- [ ] Implementar cancelamento e timeout de chamadas
- [ ] Testes end-to-end de videoconferencia
- [ ] Rate limiting e anti-spam em video call request
- [ ] Regras de janela de horario e timezone
- [ ] Jobs/Cron para marcar no_show

---

# BACKEND - IMPLEMENTACOES PENDENTES

### Controllers Stub (apenas renderizam pagina vazia, sem logica)
- [ ] DoctorDocumentsController.php - implementar logica de dados reais
- [ ] DoctorHistoryController.php - implementar logica de dados reais
- [ ] DoctorPatientsController.php - implementar logica de dados reais
- [ ] DoctorLaboratoriesController.php - implementar logica de dados reais
- [ ] PatientDetailsController.php - implementar logica de dados reais

### Policies incompletas
- [ ] MessagePolicy.php - faltam metodos view(), create(), update(), delete()
- [ ] AppointmentPolicy - faltam metodos start(), end(), cancel() (doc: TrueIssues.md sec 5)

### Migrations pendentes (docs/TrueIssues.md sec 7.1)
- [ ] Criar tabela appointment_availabilities
- [ ] Criar tabela doctor_availability_exceptions
- [ ] Criar tabela patient_emergency_contacts
- [ ] Adicionar indices em status e scheduled_at
- [ ] Adicionar colunas metadata JSON e consent flags

### Tasks de manutencao (Kernel/Scheduler)
- [ ] Job para marcar no_show em appointments
- [ ] Job para finalizar chamadas de video zumbis
- [ ] Job para limpar locks expirados do Redis
- [ ] Job para enviar lembretes pre-consulta

### Servicos incompletos
- [ ] NotificationService.php:168 - sendPush() esta vazio (placeholder)
- [ ] BaseAdapter.php:84 - renovacao de token OAuth2 nao implementada (TODO)
- [ ] NotifyIntegrationFailure.php:41 - notificacao real para admins nao implementada (TODO)
- [ ] DataAccessReportController.php:75 - exportacao PDF retorna 501 (TODO)

### CRUD de perfis (docs/TrueIssues.md sec 7.2)
- [ ] CRUD completo de perfis de Doctors (biografia, CRM, especializacoes, agenda, fee)
- [ ] CRUD completo de perfis de Patients (dados clinicos, consentimento, contatos emergencia)
- [ ] API de busca de medicos (filtro por especializacao, preco, avaliacao, localizacao)
- [ ] Autenticacao de dois fatores (2FA) para pacientes

### Agenda e Consultas (docs/TrueIssues.md sec 7.3)
- [ ] AppointmentsController completo (listagens paginadas, POST/PUT/DELETE)
- [ ] AppointmentService ampliado (conflito horario, bloqueio por status, motivos)
- [ ] AppointmentsObserver (gerar access_code, preencher metadata, disparar eventos)
- [ ] Scheduling de disponibilidades (CRUD de blocos, materializar slots livres)

### Mensageria (docs/TrueIssues.md sec 7.5)
- [ ] Verificar se endpoints /api/messages/* e /api/notifications/* existem no backend
  - Frontend (useMessages.ts, useNotifications.ts) referencia endpoints que podem nao existir

### Prontuario e Prescricoes (docs/TrueIssues.md sec 7.6)
- [ ] Versionamento explicito de alteracoes clinicas
- [ ] Historico de edicoes com diff
- [ ] Interface para visualizar historico de alteracoes

---

# FRONTEND - PENDENCIAS

### Dados hardcoded (mock data em paginas de producao)
- [ ] Doctor/History.vue:23-78 - dados de consultas mockados com URLs do Unsplash
- [ ] Doctor/Documents.vue:26-83 - lista de pacientes e medicamentos mockados
- [ ] Patient/NextConsultation.vue:25-37 - dados estaticos do medico (Dr. Ricardo Almeida)
- [ ] settings/BugReport.vue:47-84 - bug reports mockados (comentario: "substituir por dados reais")
- [ ] Doctor/PatientDetails.vue:30 - URL de avatar hardcoded do Unsplash
- [ ] components/modals/ChatModal.vue:44-69 - URLs de avatar hardcoded

### TODOs no frontend
- [ ] BugReport.vue:160 - implementar recarregamento real do backend
- [ ] BugReportModal.vue:74 - implementar envio real para backend (simula com setTimeout)

### console.log para remover
- [ ] Doctor/Consultations.vue:73 - console.log('Dados salvos na sidebar')
- [ ] Patient/NextConsultation.vue:57 - console.log('Consulta cancelada')
- [ ] components/LottieAnimation.vue:100,105 - console.log de animacao

### Erros com alert() (deveria usar toast/componente)
- [ ] Patient/ConsultationDetails.vue:173,206 - alert() para exibir erro
- [ ] Patient/VideoCall.vue:76 - alert() para mensagem de erro

### Paginas de Laboratorio vazias (nao confundir com Integrations)
- [ ] Doctor/Laboratories/Hub.vue - completamente vazio
- [ ] Doctor/Laboratories/Partners.vue - completamente vazio

### Video chamada incompleta
- [ ] Patient/VideoCall.vue - marcado como "P2P removida; SFU em desenvolvimento"
- [ ] Dev/VideoTest.vue - pagina de teste, P2P removida

---

# CONFIGURACAO E AMBIENTE

### .env.example - variaveis faltando
- [x] RNDS_ENABLED, RNDS_ENVIRONMENT, RNDS_BASE_URL, RNDS_CERTIFICATE_PATH
- [x] FHIR_SYSTEM_URL
- [x] INTEGRATION_* (timeouts, circuit breaker settings)
- [x] RETRY_*_MAX variaveis

---

# TESTES (cobertura atual: 37 metodos, maioria auth/settings)

### Testes existentes
- Auth (login, logout, registro, email verification, password reset) - OK
- Settings (profile update, password update) - OK
- AppointmentsTest (14 unit tests) - OK
- DoctorMedicalRecordActionsTest (1 test) - OK

### Testes faltando (sem nenhum arquivo)
- [ ] Testes de Doctor appointments management
- [ ] Testes de Doctor availability/scheduling
- [ ] Testes de Patient dashboard
- [ ] Testes de Patient booking
- [ ] Testes de Patient consultations
- [ ] Testes do sistema de mensagens
- [ ] Testes de LGPD (Consent, Data Access, Portability)
- [ ] Testes de Policies (AppointmentPolicy, ConversationPolicy)
- [ ] Testes de VideoCall

### Factories faltando
- [ ] AppointmentsFactory (usado em testes mas criado manualmente)

### Testes placeholder para remover ou implementar
- [ ] tests/Unit/ExampleTest.php - trivial
- [ ] tests/Feature/ExampleTest.php - trivial
- [ ] tests/Unit/VideoCallPolicyTest.php - trivial/vazio

---

# BRANCH feature/interoperability - PENDENCIAS ESPECIFICAS

### Testes unitarios faltando (9)
- [x] PatientFhirMapperTest
- [x] ExamOrderFhirMapperTest
- [x] ExamResultFhirMapperTest
- [x] DiagnosisFhirMapperTest
- [x] PrescriptionFhirMapperTest
- [x] ExamResultDtoTest
- [x] IntegrationCredentialTest (encryption, isTokenExpired)
- [x] IntegrationEventTest (scopes)
- [x] IntegrationQueueItemTest (shouldRetry, markAsProcessing)

### Testes feature faltando (2)
- [x] PartnerHealthControllerTest
- [x] ResilienceTest (circuit breaker E2E, retry, idempotencia)

### Seeders faltando (2)
- [x] ExaminationIntegrationSeeder
- [x] IntegrationQueueSeeder

### Resiliencia (1)
- [x] Testar circuit breaker com Redis real (CircuitBreakerTest expandido: threshold, half-open->closed, limite de tentativas)

### Regulatorio (2)
- [x] Implementar job SendToRnds (autenticacao e-CNPJ + envio Bundle FHIR) -- codigo pronto, SendToRndsTest (9 testes). Ativacao requer RNDS_ENABLED=true + certificado e-CNPJ real (fora do escopo do MVP atual)
- [ ] Registrar aplicacao no Portal de Servicos DATASUS -- FORA DO ESCOPO DO MVP (validacao externa, fase futura)

### MVP 1 criterios finais (2)
- [ ] Dados enviados a RNDS apos resultado -- codigo pronto (listener SendExamResultToRnds registrado); aguarda registro DATASUS + certificado
- [ ] 1 laboratorio piloto conectado (validacao real) -- aguarda definicao do laboratorio (banner no frontend Hub indica pendencia ao usuario)

---

# GRAVACAO DE SESSAO (50% implementado - opcional pelo CFM)

- [ ] MediaRecorder API (gravacao no frontend)
- [ ] Upload para storage seguro (S3/MinIO)
- [ ] Controle de acesso as gravacoes
- [ ] Interface de consentimento especifico para gravacao
- [ ] Politica de retencao automatizada (job para excluir apos prazo)
- [ ] Player de video com controle de acesso

---

# OBSERVABILIDADE E INFRAESTRUTURA

- [ ] Logs estruturados completos (Monolog channels dedicados)
- [ ] Metricas de qualidade de chamada (latencia, perda de pacotes)
- [ ] Dashboard de KPIs (total chamadas, taxa sucesso, tempo medio)
- [ ] Backups automatizados (mysqldump + storage)
- [ ] Instrumentar metricas (Prometheus ou Laravel Horizon)

---

# RESUMO

| Categoria                        | Pendente |
|----------------------------------|----------|
| Seguranca (critico)              | 5        |
| Conformidade CFM (bloqueante)    | 13       |
| Videoconferencia                 | 8        |
| Backend - controllers/policies   | 7        |
| Backend - migrations             | 5        |
| Backend - tasks/scheduler        | 4        |
| Backend - servicos incompletos   | 4        |
| Backend - CRUDs e features       | 10       |
| Frontend - dados hardcoded       | 6        |
| Frontend - TODOs e bugs          | 8        |
| Config e ambiente                | 0        |
| Testes gerais                    | 12       |
| Interoperabilidade (branch)      | 3        |
| Gravacao de sessao               | 6        |
| Observabilidade                  | 5        |
| **TOTAL**                        | **~96**  |
