# Telemedicina Para Todos - Checklist Completo de Pendencias

## Rebaseline

- Rebaseline parcial realizado em: 2026-05-04
- Criterio: apenas itens validados diretamente no codigo foram atualizados
- Proximo passo: revisar blocos de QA manual em ondas para evitar falso positivo/negativo
- Rebaseline parcial realizado em: 2026-05-08
- Escopo desta onda: validacao local por codigo, sem dependencias externas (DATASUS/RNDS, ICP-Brasil, laboratorio piloto, TURN, gateways de pagamento)
- Achados desta onda: bloco de `/patient/medical-records` tinha itens stale sobre download direto por `/storage`; bloco de `/doctor/history` tinha itens stale de layout antigo (tabela/search/paginacao) apos redesign parcial para timeline

## Branch: development (+ feature/interoperability)

Gerado em: 2026-04-14
Fonte: analise completa do codigo, docs/TrueIssues.md, execute/, docs/Tasks/

---

# SEGURANCA (CRITICO)

### bootstrap/app.php

[x] Reativar middleware SecurityHeaders (bootstrap/app.php:24 - reativado; testar em dev se Vite/Reverb/LottieFiles ainda funcionam, ajustar CSP se necessario)

### routes/api.php

[x] Adicionar middleware ValidateWebhookSignature na rota POST /webhooks/lab/{partnerSlug} — resolvido em a409230 (middleware `partner.hmac` aplicado em routes/api.php:40)

### app/Http/Requests/Doctor/MedicalRecords/StorePrescriptionRequest.php

[x] Implementar authorize() corretamente - exige perfil doctor; vinculo medico-paciente ja e validado no controller via PatientPolicy::issuePrescription e AppointmentPolicy::createPrescription

### app/Http/Controllers/AppointmentsController.php

[ ] Validar query parameters (status, etc.) antes de usar como filtro (linhas 44-62)

### routes/web/public.php (movido de routes/web.php)

[x] Reforcar validacao de userId/filename na rota de avatars (routes/web/public.php:26-30) - constraints agora exigem `[0-9]+` para userId e `(thumb_)?[a-f0-9-]{36}\.jpg` para filename; AvatarService::resolveAvatarFile ja chamava basename() como defesa em profundidade

---

# CONFORMIDADE CFM (CRITICO - BLOQUEANTE)

### 1. Assinatura Digital ICP-Brasil (Art. 8, Res. 2.314/2022)

ESQUELETO PRONTO (2026-04-27): driver pattern (Null/IcpBrasil), service, hash canonico, verification code, rota publica /verify/{code}, paginas Verify/Show + Verify/NotFound. Driver atual: `null` (sem validade legal). Configurar SIGNATURE*DRIVER=icp_brasil + ICP* vars apos contrato.

[ ] Contratar provedor de certificacao digital (Soluti, Certisign, Safeweb) - DECISAO DE NEGOCIO
[x] Implementar DigitalSignatureService.php - app/Services/Signatures/DigitalSignatureService.php
[x] Contract DigitalSignatureDriver - app/Contracts/DigitalSignatureDriver.php
[x] Driver NullSignatureDriver (dev/staging) - app/Services/Signatures/NullSignatureDriver.php
[x] Driver IcpBrasilSignatureDriver (stub - throw RuntimeException ate provedor) - app/Services/Signatures/IcpBrasilSignatureDriver.php
[x] CanonicalPayloadBuilder (hash determinístico) - app/Support/Signatures/CanonicalPayloadBuilder.php
[x] SignatureResult value object - app/Support/Signatures/SignatureResult.php
[x] Adicionar campos signature_hash e verification_code no model Prescription - ja existiam (migration 2026_03_28_000010)
[x] Adicionar signature_status + signed_at em medical_certificates - migration 2026_04_27_120000 + atualizacao do model
[x] Bind no AppServiceProvider via config('telemedicine.signature.driver')
[x] Config telemedicine.signature (driver, icp_brasil credentials, verification_url_template)
[x] Wiring em MedicalRecordService::issuePrescription e issueCertificate (assinam apos create se service injetado)
[x] Rota publica de verificacao GET /verify/{code} (throttle 30/min, regex [A-Z0-9]{6,32})
[x] DocumentVerificationController + paginas Verify/Show.vue + Verify/NotFound.vue
[ ] Implementar IcpBrasilSignatureDriver::sign/verify com SDK do provedor (apos contrato)
[ ] Integrar fluxo de assinatura no frontend Documents.vue (badge de status + link de verificacao)
[ ] Embutir verification_code + QR code no PDF do atestado/prescricao
[ ] Validar certificado antes de emissao de documentos - IMPACTO: documentos emitidos com driver `null` NAO TEM VALIDADE LEGAL ate trocar para `icp_brasil`

### 2. Documentacao Legal CFM

[x] Adicionar secao "Consentimento para Telemedicina" na politica de privacidade
[x] Adicionar secao "Prontuario Eletronico" (armazenamento, retencao 20 anos)
[x] Adicionar secao "Gravacao de Consultas" (consentimento especifico)
[x] Adicionar secao "Documentos Medicos Digitais" (validade legal, ICP-Brasil)
[x] Expandir secao "Protocolo de Emergencias" (SAMU 192, Bombeiros 193)
[x] Adicionar secao "Responsabilidades do Medico" (CRM + UF)
[x] Implementar Termo de Consentimento Livre e Esclarecido (Art. 4 e 5)

---

# VIDEOCONFERENCIA (70% implementado)

[ ] Tornar appointment_id obrigatorio em VideoCallRoom + migration
[ ] Implementar locks de concorrencia com Redis (evitar chamadas simultaneas)
[ ] Configurar TURN server (Coturn ou Twilio) para NAT traversal
[ ] Implementar cancelamento e timeout de chamadas
[ ] Testes end-to-end de videoconferencia
[ ] Rate limiting e anti-spam em video call request
[ ] Regras de janela de horario e timezone
[ ] Jobs/Cron para marcar no_show

---

# BACKEND - IMPLEMENTACOES PENDENTES

### Controllers Stub (apenas renderizam pagina vazia, sem logica)

[x] DoctorDocumentsController.php - lista real de pacientes do medico (Documents.vue atualizado para receber prop patients).
PENDENCIAS desta pagina (nao bloqueantes para uso interno, mas obrigatorias antes de validade legal): - Catalogo de medicamentos (drugCatalog) ainda mockado - sem tabela/API de drogas no projeto - Catalogo de exames TUSS (examCatalog) ainda mockado - sem tabela TUSS no projeto - Assinatura digital ICP-Brasil nao implementada - documentos emitidos por aqui NAO TEM VALIDADE LEGAL ate parte 3 (CFM) ser concluida - Persistencia do formulario reusa endpoints existentes em DoctorPatientMedicalRecordController
[x] DoctorHistoryController.php - dados reais (ultimos 30 dias agrupados por dia, com summary, status e detalhes do paciente). History.vue atualizado para receber prop dayGroups; mock antigo removido
[x] DoctorPatientsController.php - dados reais (stats agregadas, upcoming patients, patient history) com queries otimizadas (sem N+1)
[ ] DoctorLaboratoriesController.php - **arquivo nao existe** (verificar se ainda faz sentido apos modulo Integrations; talvez remover do checklist)
[x] PatientDetailsController.php - dados reais (perfil resumido do paciente + ultimas 10 consultas), autorizado via MedicalRecordPolicy::view. PatientDetails.vue atualizado para receber props (patient, consultations)

### Policies incompletas

[x] MessagePolicy.php - implementados view, create, update, delete, markAsRead (alem do markAsDelivered ja existente)
[x] AppointmentPolicy - metodos start(), end(), cancel() implementados (app/Policies/AppointmentPolicy.php:114,139,153)

### Migrations pendentes (docs/TrueIssues.md sec 7.1) - TODOS OBSOLETOS

[x] Criar tabela appointment_availabilities - existe como `doctor_availability_slots` (2025_11_11_000002)
[x] Criar tabela doctor_availability_exceptions - existe como `doctor_blocked_dates` (2025_11_11_000003)
[x] Criar tabela patient_emergency_contacts - campos `emergency_contact` e `emergency_phone` em `patients`
[x] Adicionar indices em status e scheduled_at - ja existem em appointments (doctor_id+scheduled_at, patient_id+scheduled_at, status+scheduled_at, access_code)
[x] Adicionar colunas metadata JSON e consent flags - `appointments.metadata` jsonb existe; tabela `consents` dedicada (2025_11_30_145555); `patients.consent_telemedicine` boolean

### Tasks de manutencao (Kernel/Scheduler)

[x] Job para marcar no_show em appointments - implementado em `MarkNoShowAppointments`, agendado em `routes/console.php` e coberto por `MaintenanceJobsTest`
[x] Job para finalizar chamadas de video zumbis - implementado em `EndZombieVideoCalls`, agendado em `routes/console.php` e coberto por `MaintenanceJobsTest`
[x] Job para limpar locks expirados do Redis - implementado em `CleanExpiredRedisLocks`, com padroes configuraveis em `telemedicine.maintenance.lock_key_patterns` e agendamento em `routes/console.php`
[x] Job para enviar lembretes pre-consulta - `SendAppointmentReminders` ja existia; agora ficou idempotente por janela (`reminders_sent`) e segue agendado em `routes/console.php`

### Servicos incompletos

[ ] NotificationService.php:168 - sendPush() esta vazio (placeholder)
[ ] BaseAdapter.php:74,109 - renovacao de token OAuth2 nao implementada (TODO - aguarda integracao com parceiro real)
[ ] NotifyIntegrationFailure.php:41 - notificacao real para admins nao implementada (TODO)
[ ] DataAccessReportController.php:75 - exportacao PDF retorna 501 (TODO)

### CRUD de perfis (docs/TrueIssues.md sec 7.2)

[ ] CRUD completo de perfis de Doctors (biografia, CRM, especializacoes, agenda, fee)
[ ] CRUD completo de perfis de Patients (dados clinicos, consentimento, contatos emergencia)
[ ] API de busca de medicos (filtro por especializacao, preco, avaliacao, localizacao)
[ ] Autenticacao de dois fatores (2FA) para pacientes

### Agenda e Consultas (docs/TrueIssues.md sec 7.3)

[ ] AppointmentsController completo (listagens paginadas, POST/PUT/DELETE)
[ ] AppointmentService ampliado (conflito horario, bloqueio por status, motivos)
[x] AppointmentsObserver implementado (app/Observers/AppointmentsObserver.php) - access_code unico, default status, dispara AppointmentCreated/Cancelled/Rescheduled/StatusChanged, logs em AppointmentLog
[ ] Scheduling de disponibilidades (CRUD de blocos, materializar slots livres)

### Mensageria (docs/TrueIssues.md sec 7.5)

[x] Endpoints de mensagens/notificacoes existem no backend - confirmado em 2026-05-04: rotas em `routes/web/shared.php` (`/api/messages/`_ e `/api/notifications/_`)

### Prontuario e Prescricoes (docs/TrueIssues.md sec 7.6)

[ ] Versionamento explicito de alteracoes clinicas
[ ] Historico de edicoes com diff
[ ] Interface para visualizar historico de alteracoes

---

# FRONTEND - PENDENCIAS

### Dados hardcoded (mock data em paginas de producao)

[ ] Doctor/History.vue:23-78 - dados de consultas mockados com URLs do Unsplash
[ ] Doctor/Documents.vue:26-83 - lista de pacientes e medicamentos mockados
[ ] Patient/NextConsultation.vue:25-37 - dados estaticos do medico (Dr. Ricardo Almeida)
[ ] settings/BugReport.vue:47-84 - bug reports mockados (comentario: "substituir por dados reais")
[ ] Doctor/PatientDetails.vue:30 - URL de avatar hardcoded do Unsplash
[ ] components/modals/ChatModal.vue:44-69 - URLs de avatar hardcoded

### TODOs no frontend

[ ] BugReport.vue:160 - implementar recarregamento real do backend
[ ] BugReportModal.vue:74 - implementar envio real para backend (simula com setTimeout)

### console.log para remover

[ ] Doctor/Consultations.vue:73 - console.log('Dados salvos na sidebar')
[ ] Patient/NextConsultation.vue:57 - console.log('Consulta cancelada')
[ ] components/LottieAnimation.vue:100,105 - console.log de animacao

### Erros com alert() (deveria usar toast/componente)

[ ] Patient/ConsultationDetails.vue:173,206 - alert() para exibir erro
[ ] Patient/VideoCall.vue:76 - alert() para mensagem de erro

### Paginas de Laboratorio (substituidas pelo modulo Integrations)

[x] Doctor/Laboratories/Hub.vue e Partners.vue - **arquivos nao existem mais**, modulo Integrations cobre o caso de uso (remover do checklist)

### Video chamada incompleta

[ ] Patient/VideoCall.vue - marcado como "P2P removida; SFU em desenvolvimento"
[ ] Dev/VideoTest.vue - pagina de teste, P2P removida

---

# CONFIGURACAO E AMBIENTE

### .env.example - variaveis faltando

[x] RNDS_ENABLED, RNDS_ENVIRONMENT, RNDS_BASE_URL, RNDS_CERTIFICATE_PATH
[x] FHIR_SYSTEM_URL
[x] INTEGRATION (timeouts, circuit breaker settings)
[x] RETRYMAX variaveis

---

# TESTES (cobertura atual: 37 metodos, maioria auth/settings)

### Testes existentes

- Auth (login, logout, registro, email verification, password reset) - OK
- Settings (profile update, password update) - OK
- AppointmentsTest (14 unit tests) - OK
- DoctorMedicalRecordActionsTest (1 test) - OK

### Testes faltando (sem nenhum arquivo)

[ ] Testes de Doctor appointments management
[ ] Testes de Doctor availability/scheduling
[ ] Testes de Patient dashboard
[ ] Testes de Patient booking
[ ] Testes de Patient consultations
[ ] Testes do sistema de mensagens
[ ] Testes de LGPD (Consent, Data Access, Portability)
[ ] Testes de Policies (AppointmentPolicy, ConversationPolicy)
[ ] Testes de VideoCall

### Factories faltando

[ ] AppointmentsFactory (usado em testes mas criado manualmente)

### Testes placeholder para remover ou implementar

[ ] tests/Unit/ExampleTest.php - trivial
[ ] tests/Feature/ExampleTest.php - trivial
[ ] tests/Unit/VideoCallPolicyTest.php - trivial/vazio

---

# BRANCH feature/interoperability - PENDENCIAS ESPECIFICAS

### Testes unitarios faltando (9)

[x] PatientFhirMapperTest
[x] ExamOrderFhirMapperTest
[x] ExamResultFhirMapperTest
[x] DiagnosisFhirMapperTest
[x] PrescriptionFhirMapperTest
[x] ExamResultDtoTest
[x] IntegrationCredentialTest (encryption, isTokenExpired)
[x] IntegrationEventTest (scopes)
[x] IntegrationQueueItemTest (shouldRetry, markAsProcessing)

### Testes feature faltando (2)

[x] PartnerHealthControllerTest
[x] ResilienceTest (circuit breaker E2E, retry, idempotencia)

### Seeders faltando (2)

[x] ExaminationIntegrationSeeder
[x] IntegrationQueueSeeder

### Resiliencia (1)

[x] Testar circuit breaker com Redis real (CircuitBreakerTest expandido: threshold, half-open->closed, limite de tentativas)

### Regulatorio (2)

[x] Implementar job SendToRnds (autenticacao e-CNPJ + envio Bundle FHIR) -- codigo pronto, SendToRndsTest (9 testes). Ativacao requer RNDS_ENABLED=true + certificado e-CNPJ real (fora do escopo do MVP atual)
[ ] Registrar aplicacao no Portal de Servicos DATASUS -- FORA DO ESCOPO DO MVP (validacao externa, fase futura)

### MVP 1 criterios finais (2)

[ ] Dados enviados a RNDS apos resultado -- codigo pronto (listener SendExamResultToRnds registrado); aguarda registro DATASUS + certificado
[ ] 1 laboratorio piloto conectado (validacao real) -- aguarda definicao do laboratorio (banner no frontend Hub indica pendencia ao usuario)

---

# GRAVACAO DE SESSAO (50% implementado - opcional pelo CFM)

[ ] MediaRecorder API (gravacao no frontend)
[ ] Upload para storage seguro (S3/MinIO)
[ ] Controle de acesso as gravacoes
[ ] Interface de consentimento especifico para gravacao
[ ] Politica de retencao automatizada (job para excluir apos prazo)
[ ] Player de video com controle de acesso

---

# OBSERVABILIDADE E INFRAESTRUTURA

[ ] Logs estruturados completos (Monolog channels dedicados)
[ ] Metricas de qualidade de chamada (latencia, perda de pacotes)
[ ] Dashboard de KPIs (total chamadas, taxa sucesso, tempo medio)
[ ] Backups automatizados (mysqldump + storage)
[ ] Instrumentar metricas (Prometheus ou Laravel Horizon)

---

# QA MANUAL - TESTES DE PAGINA (em andamento)

Realizado em: 2026-04-16

### Landing Page (`/`)

[ ] Ajustar navbar: botoes "Registrar-se para Pacientes" e "Faca parte da equipe" estao muito colados, quebrando nomes como "A quem servimos"
[ ] Corrigir links do dropdown da navbar que nao redirecionam para lugar nenhum. Devem redirecionar para login (se nao autenticado) e depois para a pagina relacionada (ex: Documentacao API -> login -> /api/documentation; Interoperabilidade -> login -> secao de integracao)
[ ] Corrigir height do botao "Conheca nossa visao" que esta desalinhado em relacao ao botao "Agendar agora" - devem ter o mesmo tamanho
[ ] Corrigir botao "Conheca agora" que nao redireciona para login -> dashboard
[ ] Corrigir links da section 2 ("Descubra por que a Telemedicina para todos...") para direcionar ao login (se nao autenticado) ou ao dashboard (se ja logado)
[ ] Corrigir botao "Agendar consulta agora" na penultima section para redirecionar ao login (se nao autenticado) ou a pagina de agendamentos (se ja logado)
[ ] Corrigir links do footer para redirecionarem corretamente (passando pelo login se nao autenticado): - Especialidades -> pagina de agendamentos - Como funciona -> pagina de Dashboard - Sobre telemedicina -> landing page - Entrar no sistema -> pagina de Dashboard
[ ] Redesenhar botao "Entrar" na navbar que esta escondido/pouco visivel

### Login (`/login`)

[ ] Botoes de conexao com Google, Apple e Meta nao tem feature implementada (placeholder)
[ ] Botao "Cadastre-se" redireciona apenas para /register/patient. Adicionar link/opcao para redirecionar tambem para /register/doctor
[ ] Pagina e funcionalidade de "Esqueceu senha" nao esta implementada (design + backend)

### Registro Paciente (`/register/patient`)

[ ] Imagem pendente/faltando na pagina
[ ] Campo de data de nascimento nao tem seletor por calendario, apenas input de texto
[ ] Botao "Criar conta" em telas grandes esta no canto inferior esquerdo - ajustar design (centralizar)
[ ] Funcionalidade de registro com Google e outros provedores sociais nao implementada
[ ] Container da esquerda ("Comece sua jornada") deve ter a mesma altura do container do formulario
[ ] Criar componente padrao de select para o campo Genero

### Registro Medico (`/register/doctor`)

[ ] Ajustar tamanho do input de especializacoes que esta fora de ordem visual comparado aos campos nome, CRM etc.
[ ] Avaliar melhoria de arquitetura: carregar catalogo de especializacoes por endpoint dedicado/cache no frontend, reduzindo payload em respostas com erro de validacao (atualmente traz lista completa no retorno da tela)
[ ] Separar conceitualmente dados de especializacoes disponiveis (renderizacao) das especializacoes selecionadas (formulario), para evitar confusao

### Dashboard Medico (`/doctor/dashboard`)

[ ] Corrigir funcionalidade do tour que esta totalmente quebrada
[ ] Corrigir: clicar em "Explorar por conta propria" ainda faz o tour aparecer - confirmado em 2026-05-04: `skipWelcome` marca apenas `has_seen_doctor_welcome_screen=true`; como `has_seen_doctor_dashboard_tour` permanece `false`, o backend volta com `showTour=true` no proximo load
[ ] Corrigir posicionamento das instrucoes do tour (aparecem no canto superior esquerdo em vez de junto ao elemento alvo)
[ ] Implementar responsividade do tour
[ ] Corrigir persistencia do estado do tour: fechar no X antes do fim e dar F5 nao deve reabrir o tour - confirmado em 2026-05-04: fechar tour so altera estado local no frontend (`showTour=false`), sem persistir conclusao no backend
[ ] Investigar e corrigir lentidao/performance do tour
[ ] Melhorar performance geral da pagina (Lighthouse)
[ ] Corrigir KPI "Taxa de cumprimento" que mostra valor semanticamente incorreto - confirmado em 2026-05-04: formula atual usa `weeklyStats.total / monthlyStats.total`, sem considerar desfecho (completed/cancelled/no_show)
[ ] Ajustar calculo de "Taxa de cumprimento" para considerar estados reais (concluidas, canceladas, no_show)
[ ] Corrigir card "Pacientes agendados": UI informa "Proximas 24h" mas o numero exibido nao representa esse recorte - confirmado em 2026-05-04: valor usa `upcomingAppointments.length` (lista limitada) e nao filtro temporal de 24h
[ ] Ajustar acoes da "Proxima consulta" e da tabela para levarem a consulta especifica, nao para paginas genericas - confirmado em 2026-05-04: links ainda apontam para rotas genericas (`doctorRoutes.consultations()` / `doctorRoutes.appointments()`)
[ ] Corrigir graficos semanal e mensal que subcontam consultas - confirmado em 2026-05-04: semanal considera apenas Seg-Sex e mensal fixa S1-S4
[ ] Fazer grafico semanal considerar toda a janela da estatistica semanal (nao apenas Seg a Sex)
[ ] Fazer grafico mensal considerar corretamente meses com 5 semanas
[ ] Implementar ou remover acoes visuais que hoje nao executam o que prometem
[ ] Implementar handler real para o botao "Cancelar" - confirmado em 2026-05-04: botao segue sem acao de cancelamento vinculada
[ ] Fazer botoes de "entrar em chamada" e "detalhes" levarem para a consulta/agendamento especifico

### Dashboard Paciente (`/patient/dashboard`)

[ ] Problema de performance detectado no Lighthouse
[ ] Tour esta quebrado
[ ] Corrigir secao "Historico de Consultas": confirmado em 2026-05-04, card no dashboard ainda aponta para `search-consultations` em vez de `history-consultations`
[ ] Corrigir acoes da "Proxima Consulta" que nao levam para a consulta especifica: confirmado em 2026-05-04 - "Entrar na videochamada" leva para tela generica, "Reagendar" leva para search-consultations, "Cancelar" segue sem acao implementada
[ ] Corrigir filtro de "Convenio" na secao "Encontrar Medico": confirmado em 2026-05-04, `insuranceFilter` existe em `Dashboard.vue` mas nao participa de `filteredDoctors`
[ ] Corrigir tour/welcome: comportamento incompativel com documentacao do projeto. "Explorar por conta" nao deveria iniciar tour; persistencia da decisao e fragil; fechar no X nao marca nada no backend
[ ] Remover payload desnecessario do controller: confirmado em 2026-05-04, `recentAppointments` e `stats` sao enviados pelo `PatientDashboardController` mas nao aparecem na tela; `reminders` e `healthTips` seguem arrays vazios

### Agenda e Disponibilidade Medico (`/doctor/schedule` + `/doctor/availability`)

[ ] **Merge das duas paginas em uma unica rota (Opcao A - decidido)**: as duas telas mexem com a mesma entidade (`AvailabilitySlot`) e chamam os mesmos endpoints CRUD, gerando duplicidade de UX e carga cognitiva para o medico - Consolidar em `/doctor/schedule` com duas abas no topo: - Aba **"Configurar"** - calendario + editor de slots por data + slots recorrentes + datas bloqueadas + locais de atendimento (base: [ScheduleManagement.vue](resources/js/pages/Doctor/ScheduleManagement.vue)) - Aba **"Visao geral"** - timeline/lista cronologica com cards de resumo (proxima sessao, futuros, passados), filtros, edicao individual (base: [AvailabilityOverview.vue](resources/js/pages/Doctor/AvailabilityOverview.vue)) - Unificar sidebar de locais de servico - Remover rota `/doctor/availability` ou manter como redirect para `/doctor/schedule?tab=overview` - Atualizar menu lateral do medico removendo duplicidade - Arquivos impactados: [DoctorScheduleController.php](app/Http/Controllers/Doctor/DoctorScheduleController.php), [DoctorAvailabilityController.php](app/Http/Controllers/Doctor/DoctorAvailabilityController.php), [routes/web/doctor.php](routes/web/doctor.php)

### Pacientes Medico (`/doctor/patients`)

[x] **Controller carrega dados reais** - confirmado em 2026-05-04: [DoctorPatientsController.php](app/Http/Controllers/Doctor/DoctorPatientsController.php) envia `stats`, `upcomingPatients` e `patientHistory` com queries reais
[ ] **Falta autorizacao (Policy)** - diferente de [DoctorPatientMedicalRecordController.php:37](app/Http/Controllers/Doctor/DoctorPatientMedicalRecordController.php#L37) que usa `authorize('view', $patient)`, aqui nao ha Policy validando se o medico pode ver aqueles pacientes. Criar `PatientPolicy` e aplicar na listagem
[x] **Props agora recebem backend** - confirmado em 2026-05-04: [Patients.vue](resources/js/pages/Doctor/Patients.vue) mantem fallback defensivo, mas backend ja popula `stats`, `upcomingPatients` e `patientHistory`
[ ] **Busca e filtro fake** - [Patients.vue:76-123](resources/js/pages/Doctor/Patients.vue#L76-L123) opera client-side sobre arrays vazios. Sem paginacao, sem backend search, sem ordenacao real
[ ] **Link "Iniciar video" quebrado** - [Patients.vue:295](resources/js/pages/Doctor/Patients.vue#L295) usa `doctorRoutes.videoCall?.()` que nao existe em [routes/doctor/index.ts](resources/js/routes/doctor/index.ts). Fallback redireciona para agenda generica, nao para a consulta especifica do paciente
[ ] **Empty state generico** - [Patients.vue:374-382](resources/js/pages/Doctor/Patients.vue#L374-L382) mostra "Nenhum paciente encontrado" sempre, sem diferenciar: (a) sem dados carregados, (b) filtro sem resultados, (c) realmente sem pacientes
[ ] **Responsividade** - grid `xl:grid-cols-3` em [Patients.vue:243](resources/js/pages/Doctor/Patients.vue#L243) pode comprimir cards em tablets; revisar breakpoints

### Detalhes do Paciente (`/doctor/patients/{id}/details`)

[x] **Controller com dados reais e autorizacao** - confirmado em 2026-05-04: [PatientDetailsController.php](app/Http/Controllers/Doctor/PatientDetailsController.php) usa route model binding (`Patient $patient`), `authorize('view', $patient)` e retorna props reais (paciente + consultas)
[x] **Pagina sem dados hardcoded de paciente** - confirmado em 2026-05-04: [PatientDetails.vue](resources/js/pages/Doctor/PatientDetails.vue) usa props (`patient`, `consultations`) para renderizar conteudo
[ ] **Breadcrumb e botao "Voltar" com URL fixa** - [PatientDetails.vue:86](resources/js/pages/Doctor/PatientDetails.vue#L86) volta para `/doctor/history` hardcoded. Trocar por `doctorRoutes.patients().url` (ou history se fizer sentido)
[ ] **Impacto no fluxo** - sem `/doctor/patients` funcional, o medico so chega no prontuario via `/doctor/appointments`, `/doctor/consultations/{id}` ou URL direta `/doctor/patients/{id}/medical-record`. Fluxo principal quebrado pela raiz

### Historico de Consultas Medico (`/doctor/history`)

[x] **Refatorar design da pagina** - rebaseline 2026-05-08: layout antigo (busca + filtros + tabela + paginacao) nao existe mais. [History.vue](resources/js/pages/Doctor/History.vue) usa timeline agrupada por dia, sidebar de resumo, skeleton, erro e empty state.
[x] **Controller com dados reais** - confirmado em 2026-05-04: [DoctorHistoryController.php](app/Http/Controllers/Doctor/DoctorHistoryController.php) ja monta `dayGroups` e `documentsSummary` com queries reais
[x] **Pagina nao esta mais 100% mockada** - confirmado em 2026-05-04: [History.vue](resources/js/pages/Doctor/History.vue) consome props do backend (`dayGroups`, `documentsSummary`)
[x] **Search bar sem efeito** - rebaseline 2026-05-08: item obsoleto; a search bar foi removida do layout atual.
[ ] **Botoes de filtro sem funcionalidade** - rebaseline 2026-05-08: chips de periodo/status e botao "Mais filtros" existem no layout atual, mas seguem estaticos/sem `@click` aplicando filtros reais.
[ ] **Botao "Nova consulta" sem destino** - rebaseline 2026-05-08: botao existe no header e no empty state, mas continua sem Link/handler.
[x] **Link do paciente aponta para rota inexistente** - rebaseline 2026-05-08: item obsoleto; o link textual antigo foi removido. Nova pendencia relacionada: acao de visualizar consulta usa botao com icone `Eye`, mas ainda nao navega para detalhe/prontuario.
[ ] **Acao de visualizar consulta sem implementacao** - [History.vue](resources/js/pages/Doctor/History.vue) renderiza botao `Eye` por consulta sem `@click`/Link. Definir destino: detalhe da consulta, prontuario do paciente ou modal.
[x] **Botao "MoreHorizontal" (acoes) sem implementacao** - rebaseline 2026-05-08: item obsoleto; o botao `MoreHorizontal` nao existe mais no layout atual.
[x] **Paginacao fake** - rebaseline 2026-05-08: item obsoleto; a paginacao fake foi removida junto com a tabela antiga.
[x] **Sem empty state, loading ou error state** - rebaseline 2026-05-08: resolvido em [History.vue](resources/js/pages/Doctor/History.vue) com `DataGridSkeleton`, estado de erro e empty state.
[ ] **Resumo do periodo e pendencias com numeros hardcoded** - rebaseline 2026-05-08: sidebar mostra "142 Atendimentos", "98% Confirmacao", "7 Faltas", "12 min", "3 prontuarios a finalizar", "2 prescricoes em rascunho" e "1 reagendamento aguardando" fixos; conectar ao backend ou remover.
[ ] **Avaliar sobreposicao com `/doctor/consultations`** - verificar se `/doctor/history` e `/doctor/consultations` mostram informacoes concorrentes e se cabe unificar (semelhante ao merge schedule/availability)

### Emissao de Documentos Medico (`/doctor/documents`)

[ ] **Refatorar design da pagina** - layout atual mistura catalogo, lista de selecionados e preview no mesmo fluxo vertical, ocupando espaco excessivo. Propostas: (a) split layout (formulario esquerda, preview fixo direita sticky), (b) wizard/stepper por aba, (c) mover catalogo de medicamentos para modal/autocomplete inline na tabela de selecionados. Padronizar Buttons com design system (nao usar classes soltas de cor)
[x] **Controller envia pacientes reais** - confirmado em 2026-05-04: [DoctorDocumentsController.php](app/Http/Controllers/Doctor/DoctorDocumentsController.php) envia `patients` em `Inertia::render('Doctor/Documents', [...])`
[ ] **Falta autorizacao** - nao ha policy validando se o medico pode emitir documento ao paciente selecionado (ex: verificar vinculo via `Appointment`). Criar/aplicar policy antes do submit
[x] **Pacientes vindos por props** - confirmado em 2026-05-04: [Documents.vue](resources/js/pages/Doctor/Documents.vue) usa `props.patients` via `patientsCatalog`
[ ] **Medicamentos mockados** - [Documents.vue:36-58](resources/js/pages/Doctor/Documents.vue#L36-L58) catalogo hardcoded (Ibuprofeno/Paracetamol/Amoxicilina). Implementar endpoint `GET /api/medications` (busca com debounce, paginado) ou integrar com base oficial (Anvisa, DATASUS/SIGTAP). Busca atual filtra client-side
[ ] **BUG: prescricao inicia preenchida (`rxItems`)** - confirmado em 2026-05-04: [Documents.vue](resources/js/pages/Doctor/Documents.vue) ainda inicializa `rxItems` com itens do catalogo; deveria iniciar vazia
[ ] **Abas "Atestado" e "Pedido de Exames" sao visuais** - [Documents.vue:128-149](resources/js/pages/Doctor/Documents.vue#L128-L149) trocam `selectedTab` mas o conteudo abaixo nao reage (tabela de medicamentos fica visivel nas 3 abas). Implementar formulario proprio de Atestado (CID-10, dias de afastamento, texto livre, tipo) e Pedido de Exames (catalogo TUSS/SIGTAP, urgencia, indicacao clinica, jejum)
[ ] **Sem edicao dos campos do medicamento** - o medico nao consegue alterar dosagem, via nem instrucoes — apenas adiciona item do catalogo e fica preso ao texto fixo. Tornar campos editaveis na tabela de selecionados (inputs inline)
[ ] **Campos clinicos faltando** - nao ha campo para: indicacao clinica, validade da prescricao (dias), controle especial (antibiotico/tarja preta/vermelha), posologia personalizada, orientacoes gerais ao paciente
[ ] **Preview com dados hardcoded** - [Documents.vue:274](resources/js/pages/Doctor/Documents.vue#L274) fallback "Ana Beatriz Silva" quando paciente nao selecionado e [Documents.vue:299](resources/js/pages/Doctor/Documents.vue#L299) "Dr. Ricardo Almeida" fixo. Trocar por medico autenticado (nome + CRM + UF + especialidade) e nao renderizar preview sem paciente
[ ] **Botao "Assinar Digitalmente" sem handler** - [Documents.vue:306](resources/js/pages/Doctor/Documents.vue#L306) sem `@click`. Depende da implementacao ICP-Brasil ja listada em "Conformidade CFM" (bloqueante). Deixar desabilitado enquanto nao houver servico de assinatura
[ ] **Botao "Gerar e Enviar para o Paciente" sem handler** - [Documents.vue:309](resources/js/pages/Doctor/Documents.vue#L309) sem `@click`. Integrar com `POST /doctor/patients/{patient}/medical-record/prescriptions` (ou novo endpoint standalone), gerar PDF, disparar notificacao/email
[ ] **Sem validacao** - permite gerar com paciente vazio, sem medicamentos, sem campos obrigatorios. Implementar validacao no frontend e backend (FormRequest)
[ ] **Sem estados de loading/erro/sucesso** - submit nao mostra feedback (spinner, toast, validacao por campo)
[ ] **Breadcrumb/rota** - confirmar nomenclatura: `Documentos` (menu) vs `Emissao de Documentos` (titulo da pagina). Alinhar labels

### Interoperabilidade - Hub/Parceiros/Connect (`/doctor/integrations/*`)

[ ] **Melhorar identidade visual da API Docs (logo/componente)** - criar uma logo mais profissional para a area de documentacao e refatorar o componente atual de logo (muito fraco visualmente), incluindo variantes para header e sidebar com boa legibilidade
[x] **BUG CRITICO: PartnerIntegration global sem escopo multi-tenant** - resolvido em 2026-05-02 com adocao da Opcao A: criacao da pivot `doctor_partner_integrations`, escopo por medico em `DoctorIntegrationsController` (`index`, `partners`, `show`, `sync`) e conexao por vinculo doctor-partner (sem duplicar parceiro global por slug)
[x] **Estudo tecnico (arquitetura multi-tenant) antes da correcao definitiva** - decisao implementada em 2026-05-02: **Opcao A** (catalogo global de parceiros + vinculo N:N por medico)
[x] **Definir estrategia de migracao de dados para o modelo escolhido** - aplicado backfill em migration da pivot para conexoes legadas (`partner_integrations.connected_by -> doctors.user_id`) com `insertOrIgnore`; seeders globais continuam catalogo e o vinculo passa a ser explicito por medico
[x] **BUG: sincronizacao retorna JSON cru quebrando Inertia** - resolvido em 2026-05-02: `DoctorIntegrationsController::sync` agora retorna `RedirectResponse` com flash (`success`/`error`) via `back()`, mantendo compatibilidade com `router.post()` do Inertia em `Partners.vue`
[x] **BUG: eager load com `limit(5)` em `events` nao limita por parceiro** - ja corrigido: `recentEventsByPartner()` usa `ROW_NUMBER() OVER (PARTITION BY partner_integration_id ORDER BY created_at DESC)` com subquery `DB::query()->fromSub()`, limitando corretamente por parceiro
[x] **Implementar sincronizacao automatica com cron escalonado (sem pico de carga)** - resolvido: `SyncExamResults::handle()` agora dispatcha cada parceiro com delay `index * 10s + random_int(0, 5)s`; `SyncPartnerExamResultsJob` adicionou `Cache::lock("sync_partner_{id}", 600)` com try/finally para evitar execucoes concorrentes por parceiro. Cron ja estava em `routes/console.php` com `*/15 * * * *`
[x] **Conectar card "Proxima Manutencao" ao cron real de sincronizacao** - resolvido: `index()` passa `nextSyncAt` via `CronExpression::getNextRunDate()`; Hub.vue recebe a prop, renomeou o card para "Proxima Sincronizacao" e formata a data real em pt-BR
[x] **Titulo "Hub de Integracoes" duplicado** - resolvido: Partners.vue agora exibe "Gerenciar Parceiros" no `<h1>`
[x] **Lista de parceiros no Connect e hardcoded no frontend** - resolvido: catalogo movido para `config/integrations.partner_catalog`; `connect()` passa `availablePartners` e `connectedPartners` como props; Connect.vue usa `props.availablePartners` no `v-for` e em `selectPartner()`
[x] **"Outro" bloqueado sem explicacao clara** - [Connect.vue:109](resources/js/pages/Doctor/Integrations/Connect.vue#L109) marca `available: false`. Item marcado como resolvido conforme validacao de QA atual
[x] **Parceiro ja conectado nao tem marcacao visual no Connect** - resolvido em 2026-05-02: `connect()` agora envia `connectedSlugs` e a UI marca parceiros ja conectados com badge "Ja conectado", bloqueando selecao duplicada no wizard
[x] **Checkbox "Enviar pedidos de exame" visivel em modo receive_only** - resolvido: label do checkbox tem `v-if="!isReceiveOnly"`, ocultando-o completamente no modo receive_only
[x] **Eventos criticos 24h nao tem filtro por parceiro** - resolvido: adicionado `criticalEventPartnerFilter` ref + `filteredCriticalEvents` computed + dropdown `<select>` no banner com opcoes derivadas dos nomes unicos em `criticalEvents`; banner mostra contador filtrado/total e empty state quando filtro nao tem resultados
[x] **Webhook em `/api/v1/public/webhooks/lab/{slug}` com assinatura** - confirmado em 2026-05-04: rota protegida por middleware `partner.hmac` em `routes/api.php` (rota de webhook do parceiro)
[x] **Refatorar UX do Connect para parceiro ja existente** - resolvido: `connect()` agora passa `connectedPartners` com `{id, slug}`; Connect.vue tem `handlePartnerCardClick()` que, ao clicar em parceiro ja conectado, navega diretamente para `integrationRoutes.show({partner: id})`; badge "Ja conectado" + "Ver configuracao ->" como hint visual
[x] **Versao FHIR como input livre - deveria ser fixada em R4** - resolvido em 2026-05-02: campo no `Connect.vue` passou para somente leitura (`R4`), request restringe com `Rule::in(['R4'])` e controller persiste `fhir_version` fixo como `R4`
[x] **Wizard nao testa conexao antes de marcar parceiro como ACTIVE** - resolvido: `store()` reestruturado — transaction salva parceiro+credenciais em `STATUS_PENDING`; apos transaction faz `Http::timeout(5)->get("{base_url}/metadata")`; se 200 -> `STATUS_ACTIVE` + flash success; se falhar -> `STATUS_PENDING` + flash warning explicativo; receive_only ativa diretamente sem teste de conexao
[x] **Auth method "Certificado Digital" sem campo de upload** - resolvido: opcao `certificate` removida de `authMethods` no wizard; `canProceed` default alterado de `true` para `false`; imports `Server` e `Wifi` removidos. Reimplementar quando tiver suporte a upload `.pfx`/`.p12`
[x] **Sem orientacao na UI sobre como obter credenciais do parceiro** - resolvido: bloco informativo adicionado no Step 3 do wizard com instrucoes passo-a-passo: contato tecnico -> CNPJ da clinica -> receber client_id/client_secret -> usar e-mail de contato tecnico do Step 2
[x] **Tela de sucesso com dados 100% hardcoded** - resolvido: bento grid (Latencia/Certificado TLS/Ultima Sincronizacao) removido da tela de sucesso. Reintroduzir quando wizard tiver teste real de conexao
[x] **selectMode nao reseta estado de forma simetrica** - resolvido: ambos os branches do `selectMode` agora limpam `auth_method`, `base_url`, `client_id`, `client_secret` e `bearer_token`; modo `full` tambem restaura `perm_receive_results` e `perm_webhook` para `true`
[x] **Backend aceita salvar OAuth2 sem client_id/client_secret via API direta** - resolvido em 2026-05-02: `StorePartnerIntegrationRequest` agora exige credenciais condicionais por `auth_method` (`required_if` para `oauth2`, `api_key` e `bearer`)
[x] **Card "Outro" duplicado no Step 1** - resolvido: card hardcoded removido; `v-for` ja filtrava `available: false` (custom nao aparece no loop), entao o card nao e mais renderizado
[x] **Sidebar usa label "Sincronizacao" para etapa que e Autenticacao** - resolvido: step 3 renomeado para `{ label: 'Autenticacao', icon: Lock }` e `stepTitles[3].label` atualizado para `'AUTENTICACAO'`
[x] **Sem rate limit explicito em POST /doctor/integrations/connect** - validado em 2026-05-02: rota ja protegida com `throttle:10,1` em `routes/web/doctor.php`

### Paciente - Pesquisar Medicos (`/patient/search-consultations`)

#### Redesign (solicitado pelo usuario)

[ ] **Redesign visual completo da pagina** - layout atual ([SearchConsultations.vue](resources/js/pages/Patient/SearchConsultations.vue)) tem hierarquia plana: header centralizado + barra de busca + filtros + grade de especializacoes + cards de medicos, todos empilhados sem container/card delimitando secoes. Excesso de `bg-primary/10` (barra de busca, especializacoes) cria uniformidade tom-sobre-tom que prejudica leitura. Definir wireframe novo cobrindo: (a) sidebar de filtros sticky a esquerda + lista de medicos a direita (padrao marketplace), OU (b) hero compacto com busca + chips de filtros aplicados acima da grade, (c) ordenacao visivel ("Mais avaliados / Menor preco / Disponivel hoje"), (d) contador de resultados, (e) skeleton/loading ao trocar filtro, (f) empty state com CTA "Limpar filtros"

#### Bugs e melhorias backend

[ ] **N+1 ao listar medicos com data selecionada** - [PatientSearchConsultationsController.php:59-83](app/Http/Controllers/Patient/PatientSearchConsultationsController.php#L59-L83) executa uma query `Appointments::where('doctor_id', $doctor->id)` dentro do `through()` para cada medico da pagina. Com paginacao de 6 medicos sao 6 queries extras so para descobrir slots ocupados. Substituir por single query agrupando por `doctor_id` com `whereIn`, ou eager-load via subselect/relation
[ ] **Filtro "Telemedicina" e placeholder no-op** - [SearchConsultations.vue:120-123](resources/js/pages/Patient/SearchConsultations.vue#L120-L123) tem comentario explicito "Placeholder para futuras implementacoes. Atualmente, todos atendem online." Checkbox aparece para o paciente como se filtrasse algo. Remover do UI ate ter modalidade presencial vs online no model `Doctor`
[ ] **"Especializacoes Recomendadas para Voce" nao e personalizado** - [SearchConsultations.vue:298](resources/js/pages/Patient/SearchConsultations.vue#L298) usa `specializations.slice(0, 6)` (ordem alfabetica do banco). Titulo engana o usuario. Ou implementar recomendacao real (baseada em consultas anteriores, idade, condicoes do prontuario) ou renomear para "Especialidades populares"
[ ] **Icones de especializacao hardcoded por nome em portugues** - [SearchConsultations.vue:95-107](resources/js/pages/Patient/SearchConsultations.vue#L95-L107) faz match por string (`'Cardiologia' => Heart`). Adicionar nova especializacao no banco mostra icone `Heart` (fallback) sempre. Mover para coluna `icon` ou `slug` na tabela `specializations` e mapear via slug
[ ] **Prop `appointments` carregada e nao usada** - controller envia [appointments:108-125](app/Http/Controllers/Patient/PatientSearchConsultationsController.php#L108-L125) (ate 10 consultas do paciente com eager load `doctor.user.specializations`) mas o template Vue nao consome em nenhum lugar. Remover prop e queries do controller
[ ] `**displayedDoctors.slice(0, 6)` no frontend duplica paginacao do backend** - [SearchConsultations.vue:138](resources/js/pages/Patient/SearchConsultations.vue#L138) corta novamente em 6, mascarando bugs futuros se backend retornar quantidade diferente. Remover slice e confiar na paginacao
[ ] **Filtro "Disponivel na data" so faz efeito se houver data selecionada** - [SearchConsultations.vue:125-127](resources/js/pages/Patient/SearchConsultations.vue#L125-L127) e local-only e silenciosamente ignorado quando `selectedDate` e null. Desabilitar o checkbox quando nao houver data + tooltip explicativo
[ ] `**replace: true`em todos os`applyFilters`quebra historico do navegador** - [SearchConsultations.vue:163](resources/js/pages/Patient/SearchConsultations.vue#L163) sobrescreve a entry atual. Usuario nao consegue voltar ao estado anterior de filtros com botao "Voltar". Remover`replace: true`para filtros nao-trivial (especialidade, data) e manter so para input de busca debounced
[ ] **Datepicker nativo sem`min`permite buscar em data passada** - [SearchConsultations.vue:262-266](resources/js/pages/Patient/SearchConsultations.vue#L262-L266)`<Input type="date">`aceita qualquer dia. Adicionar`:min="today"`(formato`YYYY-MM-DD`) e idealmente trocar por componente date picker UI consistente
[ ] `**Carbon::parse($filters['date'])` aceita strings arbitrarias do query** - [PatientSearchConsultationsController.php:45](app/Http/Controllers/Patient/PatientSearchConsultationsController.php#L45) faz parse generoso. Se passar `?date=now+1year` ou `?date=lixo`, cai no catch silencioso e ignora. Validar o formato em FormRequest com `date_format:Y-m-d` + `after_or_equal:today`
[ ] `**availability_schedule` (JSON) enviado integralmente para o frontend** - [PatientSearchConsultationsController.php:90](app/Http/Controllers/Patient/PatientSearchConsultationsController.php#L90) inclui o agenda completo da semana de cada medico. Aumenta peso do payload e expoe horarios de outros dias sem necessidade. Enviar so `available_slots_for_day` quando ha data, ou um indicador "tem agenda na data"
[ ] **Confirmar fonte de verdade da disponibilidade** - controller usa `availability_schedule` JSON em `Doctor`, mas existe tambem tabela `availability_slots` (vista no contexto da feature de Agenda). Validar qual e a oficial e remover o caminho redundante para evitar dados divergentes
[ ] `**bySpecialization`scope sem checagem multi-tenant nao se aplica aqui (intencional)** - busca de medicos e cross-tenant por design (paciente pode ver qualquer medico ativo da plataforma). Apenas registrar como decisao de produto para nao ser confundido com bug futuro
[ ] **Sem indicador de loading entre filtros** - troca de especialidade/data nao mostra skeleton enquanto request esta em voo (Inertia partial). Adicionar`processing`state e skeleton nos cards
[ ] **Sem total de resultados visivel** - paginacao traz`total`em [PaginatedDoctors](resources/js/pages/Patient/SearchConsultations.vue#L65) mas template nao exibe. Mostrar "X medicos encontrados" acima da grade
[ ] **Sem chips dos filtros aplicados** - depois de aplicar filtros, usuario nao tem feedback visual do que esta filtrando alem dos selects. Exibir chips dismissable ("Cardiologia x", "25/04 x") acima da lista
[ ] **Sem ordenacao** - lista vem`orderByDesc('created_at')`fixo. Adicionar select "Ordenar por: Mais recentes / Menor preco / Mais avaliados / Disponibilidade"
[x]`**consultation_fee` ja exibido no card** - confirmado em 2026-05-04: [DoctorCard.vue](resources/js/components/DoctorCard.vue) renderiza `R$ {{ formattedConsultationFee }}`; manter payload do backend
[ ] **Paginacao usa `v-html` para `link.label`** - [SearchConsultations.vue:356](resources/js/pages/Patient/SearchConsultations.vue#L356) renderiza HTML cru do label da paginacao Laravel (`&laquo;` etc.). Substituir por icones lucide (`ChevronLeft`/`ChevronRight`) ao detectar prev/next, evitando `v-html` mesmo que origem seja confiavel
[ ] **Botao "Agendar Consulta" usa `text-gray-900` sobre `bg-primary`** - [SearchConsultations.vue:325](resources/js/pages/Patient/SearchConsultations.vue#L325) depende da cor primaria ser clara o suficiente. Validar contraste WCAG AA (>= 4.5:1) com a paleta atual ou trocar para `text-primary-foreground`
[ ] **Sem cache de `specializations`** - lista buscada do banco a cada request ([PatientSearchConsultationsController.php:104-106](app/Http/Controllers/Patient/PatientSearchConsultationsController.php#L104-L106)). Como muda raramente, aplicar `Cache::remember('specializations.list', 3600, ...)` com invalidacao em CRUD de especializacao
[ ] **Filtros locais (`telemedicineOnly`, `availableNow`) nao persistem entre paginacoes** - mudar de pagina pelo `changePage` recarrega a query do backend e o estado local volta ao default. Mover esses filtros para query string e backend, ou removelos enquanto sao no-op

### Paciente - Agendar Consulta (`/patient/schedule-consultation?doctor_id=...`)

#### Decisoes de produto (registradas para implementacao)

[ ] **Redesign visual completo** - layout atual ([ScheduleConsultation.vue](resources/js/pages/Patient/ScheduleConsultation.vue)) tem header pequeno (`text-2xl`), barra de progresso de 3 passos enganosa (mostra "Informacoes" e "Horario" preenchidos sempre, "Pagamento" cinza fixo), secoes sem container/card delimitando, e CTA "Confirmar Agendamento" com `text-gray-900` sobre `bg-primary` (contraste depende da paleta). Definir wireframe: (a) lateral esquerda fixa com card do medico (sem botao de trocar), (b) coluna principal com fluxo linear "Modalidade -> Data -> Horario -> Resumo+Confirmar", (c) progresso real refletindo estado (`Selecionar horario -> Pagar -> Confirmar`)
[ ] **Remover toggle "Consulta Presencial"** - [ScheduleConsultation.vue:50](resources/js/pages/Patient/ScheduleConsultation.vue#L50) e [:237-258](resources/js/pages/Patient/ScheduleConsultation.vue#L237-L258) renderizam botao "Consulta Presencial" sem suporte completo (sem endereco, sem fluxo diferenciado, sem ServiceLocation linkado ao appointment). Decisao: MVP e telemedicina-only. Remover o botao, fixar `consultationType = 'online'`, e exibir frase informativa: **"Esta consulta sera realizada por video. Voce recebera o link de acesso 30 minutos antes do horario."** Manter modelo `ServiceLocation` no banco para futuro feature flag
[ ] **Remover botao "Trocar medico"** - [ScheduleConsultation.vue:201-205](resources/js/pages/Patient/ScheduleConsultation.vue#L201-L205) renderiza CTA dentro do `DoctorCard` que volta para search. Breadcrumb e botao "Voltar" ([:289-292](resources/js/pages/Patient/ScheduleConsultation.vue#L289-L292)) ja cumprem essa funcao. Remover o slot de actions do DoctorCard nesta tela
[ ] **Adicionar modal de revisao antes do submit** - hoje [confirmAppointment()](resources/js/pages/Patient/ScheduleConsultation.vue#L66-L121) envia direto para `appointments.store`. Adicionar modal que exibe: medico (nome, CRM, especialidades), data/hora formatada, modalidade (video), valor da consulta (`consultation_fee`), politica de cancelamento, link para termo de telemedicina, checkbox de consentimento. Botao "Confirmar e pagar" so habilita com checkbox marcado
[ ] **Implementar fluxo Agenda -> Paga -> Confirma** - decisao tomada: pagamento ANTES da confirmacao. Atualmente a pagina termina em "Confirmar Agendamento" sem pagamento ([ScheduleConsultation.vue:294-301](resources/js/pages/Patient/ScheduleConsultation.vue#L294-L301)) embora a barra de progresso ja insinue um passo "Pagamento" ([:186-189](resources/js/pages/Patient/ScheduleConsultation.vue#L186-L189)). Tarefas: (a) escolher gateway (Stripe/Pagar.me/Mercado Pago/Asaas), (b) criar tabela `payments` (`appointment_id`, `gateway`, `gateway_id`, `amount`, `status`, `paid_at`, `refunded_at`), (c) novo `STATUS_PENDING_PAYMENT` em `Appointments` (consulta criada mas reservada por X minutos), (d) job que libera o slot se nao pagar em 15min, (e) webhook do gateway atualiza para `STATUS_SCHEDULED` apos confirmacao, (f) tela `/patient/appointments/{id}/payment` ou step interno
[ ] **Politica de cancelamento visivel antes do confirm** - exibir copy fixo no resumo: **"Cancelamento gratuito ate 24h antes da consulta. Cancelamentos com menos de 24h: reembolso de 50%. Apos o horario marcado: sem reembolso."** Implementar tambem regra para **consulta no mesmo dia**: definir politica especifica (sugestao: "Para consultas marcadas para hoje, cancelamento gratuito ate 1h antes do horario") - validar com produto/juridico antes de fechar texto. Persistir as regras em `config/telemedicine.php` para alterar sem deploy
[ ] **Checkbox obrigatorio de consentimento de telemedicina (CFM Res. 2.314/2022)** - exigencia regulatoria. Antes de habilitar o "Confirmar e pagar", paciente precisa marcar: **"Li e concordo com o Termo de Consentimento Informado para Telemedicina"** com link para o termo completo (modal ou pagina dedicada). Persistir na criacao do `Appointment`: novo campo `telemedicine_consent_at` (timestamp) + `telemedicine_consent_version` (string, ex: "v1.0") para rastrear qual versao do termo foi aceita. Bloqueio backend: `FormRequest` deve recusar se nao houver consent. Conecta com pendencia ja listada em "Conformidade CFM"

#### Bugs e melhorias backend

[ ] **Loop de 30 dias chamando `ScheduleService->getAvailabilityForDate()` por dia** - [ScheduleConsultationController.php:84-123](app/Http/Controllers/Patient/ScheduleConsultationController.php#L84-L123) executa 30 chamadas ao service para montar `availableDates`. Dependendo do que o service faz internamente (queries em `availability_slots`, `blocked_dates`, `appointments`), pode ser ate 90+ queries por carregamento da pagina. Refatorar para single query agrupada por data: `AvailabilitySlot::whereBetween + BlockedDate + Appointments` em 3 queries totais e processar em memoria
[ ] `**doctor_id` da query string sem validacao de formato** - [ScheduleConsultationController.php:21](app/Http/Controllers/Patient/ScheduleConsultationController.php#L21) usa `$request->get('doctor_id')` direto em `findOrFail`. Se vier string nao-UUID dispara excecao no driver do banco (alguns drivers retornam 500 em vez de 404). Adicionar validacao `Rule::uuid` ou `Str::isUuid` com 404 amigavel
[ ] **Sem FormRequest/validacao no `index`** - filtros e identificadores aceitos sem regras explicitas. Criar `ShowScheduleConsultationRequest` com `doctor_id: required|uuid|exists:doctors,id`
[ ] `**payload.notes`enviado em portugues hardcoded do frontend** - [ScheduleConsultation.vue:83](resources/js/pages/Patient/ScheduleConsultation.vue#L83) gera string "Consulta online"/"Consulta presencial" e manda como`notes`. Anti-pattern: o campo `notes`do`Appointment`e para anotacoes do paciente/medico, nao para tipo. Trocar por campo dedicado`modality` (`enum: online,presential`) na tabela `appointments`e remover essa "documentacao" textual
[ ]`**metadata.type`redundante com`notes`** - [ScheduleConsultation.vue:84-86](resources/js/pages/Patient/ScheduleConsultation.vue#L84-L86) envia `metadata.type` mas o backend ja recebe `notes` cobrindo a mesma info. Apos criar campo `modality`, remover ambos
[ ] `**consultation_fee` enviado para o frontend sem validacao server-side no submit\*\* - paciente pode ver `consultation_fee` na prop, mas a confirmacao do appointment nao trava o valor. Quando implementar pagamento, garantir que o valor cobrado seja sempre lido do `Doctor::consultation_fee` no backend, nunca do payload do cliente

#### Bugs e melhorias frontend/UX

[ ] **Barra de progresso mostra estados estaticos** - [ScheduleConsultation.vue:177-190](resources/js/pages/Patient/ScheduleConsultation.vue#L177-L190) renderiza "Informacoes" e "Horario" sempre preenchidos em primary, "Pagamento" sempre cinza, sem refletir estado real. Substituir por componente `Stepper` que avance conforme: (1) modalidade selecionada, (2) data/hora selecionados, (3) revisao + consentimento, (4) pagamento, (5) confirmacao
[ ] **Erro de validacao client-side `errors.datetime` nao e exibido** - [ScheduleConsultation.vue:68](resources/js/pages/Patient/ScheduleConsultation.vue#L68) seta `errors.value.datetime` mas o template so renderiza `errors.general` ([:172](resources/js/pages/Patient/ScheduleConsultation.vue#L172)) e o loop generico ([:304-308](resources/js/pages/Patient/ScheduleConsultation.vue#L304-L308)). Mensagem fica visivel mas distante dos campos. Mover a exibicao para perto do `ScheduleSelector`
[ ] `**isSubmitting = true` mas erro nao reseta com `onError`** - se a request falhar antes do `onFinish`, `isSubmitting` continua `true` ate `onFinish` rodar (ok), mas a UI nao mostra feedback claro. Garantir que loader desligue em qualquer cenario e adicionar toast de erro
[x] **Valor da consulta ja exibido no card do medico** - confirmado em 2026-05-04: `ScheduleConsultation.vue` usa `DoctorCard`, e `DoctorCard.vue` renderiza `consultation_fee` quando disponivel. Pendencia futura opcional: reforcar o valor tambem no modal de revisao/pagamento
[x] **Fallback para `availableDates` vazio ja existe** - confirmado em 2026-05-04: `ScheduleSelector.vue` mostra empty state "Nenhuma disponibilidade encontrada."
[ ] `**scheduledAt`montado como string sem timezone** - [ScheduleConsultation.vue:76](resources/js/pages/Patient/ScheduleConsultation.vue#L76) faz`${selectedDate}T${selectedTime}:00`sem indicar timezone. Backend interpreta no`app.timezone`(provavel`America/Sao_Paulo`), mas paciente em outro fuso ve horario diferente do agendado. Padronizar: enviar com offset (`-03:00`) e armazenar UTC; exibir sempre no fuso do usuario
[ ] **Modal `IncompleteProfileModal` acionado por string-matching no erro** - [ScheduleConsultation.vue:106-112](resources/js/pages/Patient/ScheduleConsultation.vue#L106-L112) detecta perfil incompleto procurando substrings "cadastro completo"/"segunda etapa"/"contato de emergencia" na mensagem. Fragil: qualquer mudanca de copy quebra. Backend deve retornar erro estruturado (`error_code: PROFILE_INCOMPLETE`) e o frontend testar contra esse codigo
[ ] **Botao "Voltar" e botao "Confirmar" lado a lado sem diferencial visual forte** - [ScheduleConsultation.vue:288-301](resources/js/pages/Patient/ScheduleConsultation.vue#L288-L301) usam mesma altura (`h-9`) e mesmo padding, diferindo so por cor. CTA principal deveria ter peso visual maior (size lg, sombra, posicionamento)
[ ] **Bloco de erros geral abaixo dos botoes** - [ScheduleConsultation.vue:303-308](resources/js/pages/Patient/ScheduleConsultation.vue#L303-L308) coloca `<div class="w-full">`dentro do`flex justify-end`- quebra layout em mobile e exibe erros longe dos campos relacionados. Mover erros para perto do componente que falhou ou para um toast
[ ] **BUG: datas e horarios anteriores ao momento atual aparecem disponiveis** - reportado em QA e ainda plausivel em 2026-05-04. Backend filtra slots passados apenas quando`$date->isToday()`em [AvailabilityService.php](app/Services/AvailabilityService.php). Observacao de rebaseline:`config/app.php`ja usa`APP_TIMEZONE`com default`America/Sao_Paulo`, entao a hipotese "UTC default puro" ficou desatualizada; validar mismatch de timezone entre servidor, banco e cliente (inclusive parse local no frontend) e revisar comparacoes de data/hora no fluxo de disponibilidade
[ ] **Defesa em profundidade no frontend: `ScheduleSelector`aceita qualquer data/horario do backend** - [ScheduleSelector.vue:28-47](resources/js/components/ScheduleSelector.vue#L28-L47) e [:148-164](resources/js/components/ScheduleSelector.vue#L148-L164) renderizam tudo que vem na prop sem validar`item.date >= today`nem`slot > now`quando a data e hoje. Adicionar filtro client-side como rede de seguranca: descartar datas anteriores ao`new Date().toISOString().slice(0,10)`e, se a data for hoje, descartar slots cujo`HH:mm` ja passou (com margem de seguranca de 5 min, igual ao backend)

### Paciente - Mensagens (`/patient/messages`)

#### Bugs criticos

[ ] **PERFORMANCE CRITICA: `getConversations` carrega todas as mensagens do usuario em memoria** - confirmado em 2026-05-04: [MessageService.php](app/Services/MessageService.php) ainda faz `Message::where(...)->get()->groupBy(...)` sem limitacao para extrair preview de conversa
[ ] **N+1: `unreadCount` em loop por conversa** - confirmado em 2026-05-04: [MessageService.php](app/Services/MessageService.php) ainda executa `count()` por conversa dentro do loop
[ ] `**try/catch` no controller engole qualquer erro silenciosamente** - confirmado em 2026-05-04: [PatientMessagesController.php](app/Http/Controllers/Patient/PatientMessagesController.php) ainda retorna `conversations: []` em excecao
[ ] **BUG: link "ver perfil do medico" provavelmente quebrado** - confirmado em 2026-05-04: [Messages.vue](resources/js/pages/Patient/Messages.vue) envia `doctorId` (camelCase) com `conversation.id`; [DoctorPerfilController.php](app/Http/Controllers/Patient/DoctorPerfilController.php) espera `doctor_id`
[ ] **Conversa permanece habilitada para sempre apos qualquer appointment\*\* - confirmado em 2026-05-04: [MessageService.php](app/Services/MessageService.php) ainda valida permissao de chat apenas com `exists()` de appointment

#### Bugs frontend

[ ] **Status "Online" hardcoded** - [Messages.vue:250](resources/js/pages/Patient/Messages.vue#L250) renderiza `<p class="text-sm text-gray-500">Online</p>` sempre, mesmo quando o medico esta offline. Implementar presence channel via Reverb ou remover o indicador
[ ] `**<input type="text">` para mensagens (sem multilinha)** - [Messages.vue:315-322](resources/js/pages/Patient/Messages.vue#L315-L322) e single-line. Trocar por `<textarea>` com auto-resize + `Shift+Enter` quebra linha, `Enter` envia. Mensagem de telemedicina geralmente exige texto longo (sintomas, contexto)
[ ] `**<input>`sem`maxlength`no frontend** - rebaseline 2026-05-04: backend ja valida tamanho em [StoreMessageRequest.php](app/Http/Requests/StoreMessageRequest.php) com`max` configuravel (`telemedicine.messages.max_content_length`, default 5000). Pendencia remanescente: aplicar `maxlength`e feedback de limite no input da UI
[ ]`**scrollToBottom`usa`getElementById` em vez de template ref** - [Messages.vue:117-122](resources/js/pages/Patient/Messages.vue#L117-L122). Anti-pattern em Vue: ID hardcoded `messages-container` quebra se duas instancias do componente coexistirem. Trocar por `ref` reativa
[ ] **Click em `<div>` em vez de `<button>` para selecionar conversa** - [Messages.vue:175-217](resources/js/pages/Patient/Messages.vue#L175-L217). Sem suporte a teclado (Enter/Space) nem semantica para screen reader. Trocar por `<button>` ou adicionar `role="button" tabindex="0" @keyup.enter`
[ ] `**isLoading` compartilhado entre lista de conversas e mensagens** - confirmado em 2026-05-04: [Messages.vue](resources/js/pages/Patient/Messages.vue) usa um unico `isLoading` para ambos estados
[ ] **Conversa sem mensagens mostra timestamp do appointment** - [MessageService.php:177-179](app/Services/MessageService.php#L177-L179) usa `appointment->created_at` quando nao ha mensagens, com texto "Nenhuma mensagem ainda". Visualmente parece atividade recente. Diferenciar visualmente (ex: italic, sem timestamp, ou texto "Conversa nao iniciada")
[ ] **Busca filtra so por nome, nao pelo conteudo da ultima mensagem** - [Messages.vue:77-86](resources/js/pages/Patient/Messages.vue#L77-L86). Padrao de mercado (WhatsApp, Slack) tambem busca em mensagens. Adicionar filtro local sobre `lastMessage` ou ir ao backend para busca completa
[ ] **Botao Send com `p-2` (area de toque pequena em mobile)** - [Messages.vue:323-329](resources/js/pages/Patient/Messages.vue#L323-L329). WCAG/Apple HIG recomendam minimo 44x44px. Aumentar para `p-3` ou `min-h-[44px] min-w-[44px]`
[ ] **Layout `w-1/3 + flex-1` quebra em mobile\*\* - [Messages.vue:147-219](resources/js/pages/Patient/Messages.vue#L147-L219). Em telas <640px, lista de conversas vira coluna estreita demais e a area de chat fica espremida. Implementar comportamento "drill-down": mobile mostra so a lista, ao clicar abre chat fullscreen com botao de voltar; desktop mantem split

#### Funcionalidades faltando (importantes para telemedicina)

[ ] **Sem upload de imagens/anexos** - paciente nao consegue enviar foto de exame, receita, atestado, lesao na pele. E exigencia basica em telemedicina. Implementar upload com validacao de tipo (`image/*`, `application/pdf`), tamanho maximo configurado, scan de antivirus se possivel, e armazenamento em S3/disk privado com URL assinada
[ ] **Sem agrupamento de mensagens por data** - [Messages.vue:268-309](resources/js/pages/Patient/Messages.vue#L268-L309) renderiza lista plana. Adicionar separadores "Hoje", "Ontem", "12 de abril" entre grupos de mensagens
[ ] **Sem indicador "digitando..."** - via presence channel/whisper do Reverb
[ ] **Sem notificacoes desktop nem audio** - se paciente sai da aba, nao percebe nova mensagem. Implementar `Notification.requestPermission()` + audio de alerta opcional
[ ] **Sem paginacao/infinite-scroll de historico** - composable carrega so primeira pagina (50 mensagens). Em conversas longas o historico fica truncado. Implementar load-more no scroll-up usando o `beforeMessageId` que o backend ja suporta ([MessageService.php:58-68](app/Services/MessageService.php#L58-L68))
[ ] **Sem botao "marcar todas como lidas"** - paciente com 50 conversas precisa abrir uma a uma para zerar contadores
[ ] **Sem busca dentro de uma conversa especifica** - so existe filtro na lista. Adicionar busca inline na area de mensagens (atalho Ctrl+F que abre overlay)

#### Conformidade e seguranca

[ ] **Sem disclaimer de emergencia visivel** - exigencia regulatoria/etica para telemedicina. Banner fixo: **"Em caso de emergencia, ligue 192 (SAMU) ou procure o pronto-socorro mais proximo. Mensagens nao sao monitoradas 24/7."** Posicionar acima do input ou no topo do chat
[ ] **Sem aviso sobre limite do canal de mensageria** - texto explicito: **"Este canal nao substitui consulta. Para diagnosticos e prescricoes, agende uma teleconsulta."** Conecta com regras CFM
[ ] **Mensagens nao tem retencao/expurgo definidos** - LGPD exige politica de retencao para dados de saude. Definir prazo (ex: 5 anos pos-ultima consulta), automatizar expurgo via job, e exibir politica para o paciente
[ ] **Sem audit log de acesso a conversas** - quem acessou, quando. Critico para investigacao de vazamento. Tabela `message_access_logs` com `user_id`, `conversation_with`, `accessed_at`, `ip`
[x] **Rate limit no envio ja aplicado** - confirmado em 2026-05-04: POST `/api/messages` protegido com `throttle:30,1` em `routes/web/shared.php`
[ ] `**message.content` exibido com `{{ }}` (Vue escapa por padrao) - validar que nao ha `v-html` em nenhum descendente\*\* - confirmar que componentes filhos nao injetam HTML cru, especialmente se houver markdown/emoji rendering futuramente

### Paciente - Historico de Consultas (`/patient/history-consultations`)

#### Redesign (solicitado pelo usuario)

[ ] **Redesign visual completo da pagina** - layout atual ([HistoryConsultations.vue](resources/js/pages/Patient/HistoryConsultations.vue)) tem hierarquia plana: header + grid de 4 cards de stats + barra de tabs + lista + banner CTA, com Stats e Tabs visualmente desconectados (paciente ve "3 Proximas" mas precisa procurar o botao "Proximas" em outro bloco). Definir wireframe novo cobrindo: (a) **renomear pagina** - "Meu Historico" colide com tab "Proximas" e card "Proximas" (historico e passado); sugestao: trocar para "Minhas Consultas" e dividir visualmente em "Proximas" (cards expandidos com CTA de cancelar/reagendar/entrar na sala) e "Anteriores" (lista compacta com avaliar/baixar prescricao/ver detalhes); (b) **stats cards clicaveis** que aplicam o filtro correspondente (eliminando a barra de tabs duplicada); (c) **filtros adicionais visiveis**: data range, busca por nome de medico/especialidade, ordenacao (mais recente/mais antiga); (d) **acoes contextuais por status** no card da consulta em vez de botao "Ver detalhes" generico; (e) **empty state diferenciado por filtro** com CTA especifico

#### Bugs criticos

[ ] **BUG: CTA "Agendar Nova Consulta" leva a tela de erro** - confirmado em 2026-05-04: [HistoryConsultations.vue](resources/js/pages/Patient/HistoryConsultations.vue) chama `patientRoutes.scheduleConsultation()` sem `doctor_id`; backend redireciona para busca de medicos quando o parametro falta
[ ] **BUG: pagina atual fica invisivel na paginacao** - confirmado em 2026-05-04: [HistoryConsultations.vue](resources/js/pages/Patient/HistoryConsultations.vue) usa `data-current:bg-white` sobre fundo branco para item ativo
[ ] **Botao kebab (`MoreVertical`) renderizado mas sem handler** - confirmado em 2026-05-04: icone esta na UI sem `@click`/dropdown associados em [HistoryConsultations.vue](resources/js/pages/Patient/HistoryConsultations.vue)

#### Bugs e melhorias backend

[ ] **4 queries `count()` redundantes para stats** - confirmado em 2026-05-04: [PatientHistoryConsultationsController.php](app/Http/Controllers/Patient/PatientHistoryConsultationsController.php) ainda faz 4 `count()` separados para `total/upcoming/completed/cancelled`
[ ] **Filtros `rescheduled` e `no_show` aceitos no backend mas sem botao no UI** - [PatientHistoryConsultationsController.php:38-42](app/Http/Controllers/Patient/PatientHistoryConsultationsController.php#L38-L42) aceita esses status, mas [HistoryConsultations.vue:201-247](resources/js/pages/Patient/HistoryConsultations.vue#L201-L247) so renderiza 4 botoes (upcoming/completed/cancelled/all). Decidir: (a) adicionar botoes "Reagendadas" e "Faltei" na UI, ou (b) restringir o backend para os 4 valores oficiais via `Rule::in([...])`
[ ] **Sem validacao do query param `status`** - confirmado em 2026-05-04: controller ainda usa `$request->get('status')` sem FormRequest/`Rule::in`
[ ] **Payload nao inclui informacoes uteis para a tela**: `consultation_fee`, modalidade (online/presencial), local da consulta, motivo de cancelamento. Se redesign exigir exibir esses dados, expandir o `through()` em [PatientHistoryConsultationsController.php:48-71](app/Http/Controllers/Patient/PatientHistoryConsultationsController.php#L48-L71)
[ ] `**orderBy('scheduled_at', 'desc')` aplicado para todos os filtros\*\* - confirmado em 2026-05-04: ordenacao unica `desc` permanece no query base, inclusive para `upcoming`

#### Bugs e melhorias frontend/UX

[ ] **Tipo TypeScript `activeFilter` desalinhado com backend** - confirmado em 2026-05-04: [HistoryConsultations.vue](resources/js/pages/Patient/HistoryConsultations.vue) restringe union a 4 valores e faz cast `as any` para valor vindo do backend
[ ] **Texto do header promete o que a pagina nao entrega** - [HistoryConsultations.vue:152-154](resources/js/pages/Patient/HistoryConsultations.vue#L152-L154) diz "Acesse detalhes, **avalie atendimentos** e **gerencie seus acompanhamentos**." Mas so existe botao "Ver detalhes" - nao tem avaliar nem gerenciar. Implementar avaliacao pos-consulta (modal de rating + comentario com persistencia em tabela `appointment_ratings`) ou ajustar o copy
[ ] **Cards de stats nao sao clicaveis** - [HistoryConsultations.vue:158-198](resources/js/pages/Patient/HistoryConsultations.vue#L158-L198) sao `<div>` estaticos. Padrao moderno: card clicavel que aplica o filtro correspondente, eliminando a barra de tabs separada
[ ] **Empty state generico** - [HistoryConsultations.vue:288-290](resources/js/pages/Patient/HistoryConsultations.vue#L288-L290) mostra so texto "Nenhuma consulta encontrada para o filtro selecionado." Adicionar CTA contextual: filtro `upcoming` -> "Agendar agora"; filtro `completed` -> "Voce ainda nao concluiu nenhuma consulta"; filtro `all` -> "Ainda nao ha consultas registradas. [Agendar primeira]"
[ ] **Paginacao usa `<<`/`>>` como texto** - confirmado em 2026-05-04: [HistoryConsultations.vue](resources/js/pages/Patient/HistoryConsultations.vue) ainda renderiza texto cru para navegacao da paginacao
[ ] **Sem confirmacao em filtros de URL nao listados** - se paciente acessa `/patient/history-consultations?status=foo`, backend devolve "all" silenciosamente mas a URL continua com o param invalido. Limpar via redirect ou exibir aviso
[ ] **Banner CTA fixo no rodape independente do estado** - [HistoryConsultations.vue:336-351](resources/js/pages/Patient/HistoryConsultations.vue#L336-L351) sempre aparece. Considerar esconder quando `activeFilter === 'upcoming'` e ja ha agendamentos futuros (CTA fica redundante)
[ ] **Sem ordenacao configuravel** - lista vem ordenada por `scheduled_at desc`. Adicionar select "Ordenar por: Mais recente / Mais antiga / Status"
[ ] **Sem filtro de data** - paciente nao consegue achar consulta de "abril/2025". Adicionar date range picker ou filtros pre-definidos ("Ultimos 30 dias", "Ano passado", etc.)
[ ] **Sem busca por nome do medico** - util quando o paciente tem muitas consultas
[ ] **Filtros aplicam `preserveState: true`** mas como `router.get` recarrega props, o estado preservado e so o de scroll. Validar comportamento esperado vs o que esta acontecendo
[ ] **Avatar do medico carregado na prop mas nao usado no template** - [PatientHistoryConsultationsController.php:61](app/Http/Controllers/Patient/PatientHistoryConsultationsController.php#L61) envia `avatar` mas o template passa para `AppointmentSummary` so id/name/specializations ([HistoryConsultations.vue:259-263](resources/js/pages/Patient/HistoryConsultations.vue#L259-L263)). Verificar se `AppointmentSummary` consome avatar; se nao, remover do payload ou estender o componente

### Paciente - Prontuario Medico (`/patient/medical-records`)

#### Bugs criticos de seguranca/privacidade

[x] **CRITICO LGPD: documentos do prontuario expostos via URL direta sem auth** - rebaseline 2026-05-08: resolvido/obsoleto. [DocumentsTab.vue](resources/js/components/Patient/MedicalRecord/tabs/DocumentsTab.vue) usa `patientMedicalRecordRoutes.documents.download`; [MedicalRecordDocumentController.php](app/Http/Controllers/MedicalRecordDocumentController.php) valida ownership/visibilidade, usa storage privado via `FileStorageManager` e registra `logAccess(..., 'download')`. `config/telemedicine.php` define `medical_documents`, `prescriptions` e `certificates` como `visibility => private`.
[x] **Visibility "Apenas medico" disponivel no upload do paciente** - rebaseline 2026-05-08: resolvido. [DocumentsTab.vue](resources/js/components/Patient/MedicalRecord/tabs/DocumentsTab.vue) expoe apenas `patient` e `shared`; [MedicalRecordDocumentController.php](app/Http/Controllers/MedicalRecordDocumentController.php) so permite `VISIBILITY_DOCTOR` quando `request->user()->isDoctor()`.
[ ] `**extractFilters` aceita filtros backend que o frontend nao expoe** - confirmado em 2026-05-04: [PatientMedicalRecordController.php](app/Http/Controllers/Patient/PatientMedicalRecordController.php) ainda processa filtros extras sem FormRequest dedicado
[x] `**logAccess`so e disparado no view do paciente, nao em download de documentos individuais** - rebaseline 2026-05-08: resolvido. [MedicalRecordDocumentController.php](app/Http/Controllers/MedicalRecordDocumentController.php) registra `logAccess(..., 'download', ['document_id' => ...])` antes de entregar o arquivo.
[ ] **Sem assinatura digital ICP-Brasil no PDF exportado** - FORA DO ESCOPO DESTA ONDA (integracao externa). Ja listado em "Conformidade CFM"; depende de provedor ICP-Brasil/e-CNPJ real.

#### Bugs criticos de funcionamento

[x] **BUG: export PDF quebra fluxo Inertia** - rebaseline 2026-05-08: resolvido. [PatientMedicalRecordController.php](app/Http/Controllers/Patient/PatientMedicalRecordController.php) enfileira `GenerateMedicalRecordPDF` e retorna JSON `202` quando `expectsJson()`; [useMedicalRecordExport.ts](resources/js/composables/Patient/useMedicalRecordExport.ts) chama `axios.post(..., Accept: application/json)` e exibe status/erro local.
[ ] **Rate limit do export possivelmente restritivo** - rebaseline 2026-05-08: item antigo dizia `tooManyAttempts(..., 1)`, mas o código atual usa 3 solicitações por hora. Validar com produto se 3/h é adequado para LGPD/UX.
[ ] **Payload completo carregado em um unico request** - confirmado em 2026-05-04: [MedicalRecordService.php](app/Services/MedicalRecordService.php) continua retornando pacote completo (timeline + consultas + prescricoes + exames + documentos + sinais vitais + metricas)
[ ] **Filtros aplicados via `replace: true`** - confirmado em 2026-05-04: [MedicalRecord.vue](resources/js/pages/Patient/MedicalRecord.vue) ainda usa `replace: true` em `router.get`
[x] **Sem watcher em `filtersState`** - rebaseline 2026-05-08: item nao e bug no layout atual; filtros disparam por acao explicita `@apply="applyFilters"` em [MedicalRecord.vue](resources/js/pages/Patient/MedicalRecord.vue), e o composable ainda oferece debounce caso seja reativado no futuro.

#### Bugs e melhorias frontend/UX

[x] **God-component de 1659 linhas dual-mode (paciente/medico)** - rebaseline 2026-05-08: resolvido em grande parte. Existem [Patient/MedicalRecord.vue](resources/js/pages/Patient/MedicalRecord.vue) e [Doctor/PatientMedicalRecord.vue](resources/js/pages/Doctor/PatientMedicalRecord.vue); a tela do paciente foi quebrada em componentes por header, filtros e tabs em `resources/js/components/Patient/MedicalRecord/`.
[ ] **Badge "Privada/Compartilhada" em anotacoes para o paciente nao faz sentido** - [MedicalRecord.vue:1564-1569](resources/js/pages/Patient/MedicalRecord.vue#L1564-L1569). Backend ja filtra `is_private=false` para o paciente em [MedicalRecordService.php:355-360](app/Services/MedicalRecordService.php#L355-L360), entao o badge sempre mostra "Compartilhada". Remover o badge da visao do paciente
[ ] **10 tabs e navegacao excessiva** - paciente comum usa 2-3 secoes (consultas, prescricoes, exames). Avaliar consolidacao: agrupar em "Resumo / Consultas / Documentos & Exames" ou usar barra lateral com agrupamentos
[ ] **Texto "Historial medico" no header (typo e exposicao)** - [MedicalRecord.vue:721](resources/js/pages/Patient/MedicalRecord.vue#L721) usa "Historial" (espanhol/incomum em PT-BR). Trocar para "Historico medico" ou "Antecedentes". Alem disso, exibir o conteudo completo no header expoe info sensivel (browser cache, print, screen share). Truncar com "Ver mais" toggle
[ ] `**patient.allergies` e `current_medications` carregados na prop mas nao exibidos** - [MedicalRecord.vue:170-174](resources/js/pages/Patient/MedicalRecord.vue#L170-L174) tem campos cruciais de seguranca clinica (alergias, medicacao continua) que nao aparecem em lugar nenhum no template. Adicionar destaque visual no header (banner amarelo de alerta para alergias, lista de medicacoes em uso)
[ ] `**patient.height/weight/bmi`carregados e nao usados** - mesmas linhas. Ou exibir junto dos sinais vitais ou remover do payload
[ ] **Empty states extremamente genericos** - "Nenhuma prescricao disponivel", "Nenhum exame encontrado", "Nenhuma anotacao..." em todas as tabs sem CTA. Adicionar contexto util ("Suas prescricoes aparecerao aqui apos a consulta") e CTAs onde fizer sentido (ex: "Agendar consulta")
[ ]`**accept=".pdf,.jpg,.jpeg,.png"`** - [MedicalRecord.vue:1477](resources/js/pages/Patient/MedicalRecord.vue#L1477) limita no front, mas backend tem que validar tambem (mime real, nao extensao). Verificar `MedicalRecordDocumentController` para garantir validacao server-side com `Rule::file()->types(['pdf','jpg','png'])` + scan opcional
[ ] **Tab "Evolucao" so mostra sinais vitais** - nome sugere evolucao clinica/temporal mas so renderiza VitalSigns. Renomear para "Sinais vitais" ou expandir para incluir grafico de evolucao temporal (peso/PA/glicemia)
[ ] **Tab "Consultas Futuras" duplica funcionalidade do Historico de Consultas** - paciente ja tem `/patient/history-consultations`. Avaliar remocao da tab daqui ou manter so como visao rapida com link "Ver todas"
[ ] `**flashStatus` lido de `page.props.flash?.status`** - [MedicalRecord.vue:690](resources/js/pages/Patient/MedicalRecord.vue#L690) sem typing de `page.props.flash`. Se o middleware nao setar a struct esperada, undefined silencioso. Tipar via interface
[ ] `**expandedItems = new Set()`recriado a cada toggle** - [MedicalRecord.vue:655-663](resources/js/pages/Patient/MedicalRecord.vue#L655-L663) cria novo Set para forcar reatividade. Em Vue 3 o ref de Set ja e reativo nativamente; substituir por`expandedItems.value.add/delete`direto
[ ]`**docs/MedicalRecord` usa cores azuis hardcoded (`bg-blue-600`, `text-blue-700`)** - inconsistente com `bg-primary` (paleta turquesa do projeto). Padronizar
[ ] **Botao "Atualizar resultados" so aparece para medico** - [MedicalRecord.vue:1371-1382](resources/js/pages/Patient/MedicalRecord.vue#L1371-L1382). Paciente tambem se beneficiaria de pull manual quando exame esta "Aguardando resultado". Avaliar liberar com rate limit
[ ] **Sinais vitais em grid 2x4 sem unidades grandes/destaques** - [MedicalRecord.vue:1626-1634](resources/js/pages/Patient/MedicalRecord.vue#L1626-L1634) listagem plana sem comparacao temporal nem highlight de valores fora da normalidade (ex: PA > 140/90 em vermelho). Util para o paciente entender evolucao
[ ] `**exam.results?.summary`** - [MedicalRecord.vue:1403](resources/js/pages/Patient/MedicalRecord.vue#L1403) so mostra `summary`. Resultados FHIR completos vem em `results` mas paciente nao consegue ver detalhes. Considerar modal "Ver resultado completo" com renderizacao do bundle
[ ] `**prescription.medications` renderizado como lista simples\*\* - [MedicalRecord.vue:1352-1357](resources/js/pages/Patient/MedicalRecord.vue#L1352-L1357) sem destaque para horarios, duracao, alertas de interacao. Padrao moderno: cards individuais por medicamento com timeline ("tomar agora", "proximo as 14h")

#### Conformidade

[ ] **Sem aviso/aceite de visualizacao do prontuario** - LGPD recomenda que paciente reconheca que esta acessando dados sensiveis. Banner informativo no primeiro acesso ou consentimento documentado de quem mais pode acessar (responsavel legal de menor, etc.)
[ ] **Sem versionamento/imutabilidade do prontuario** - exigencia CFM Resolucao 1.821/2007: prontuario nao pode ser alterado, so anexado. Validar que registros tem `created_at` e nao podem ser editados (apenas anotacoes adicionais com `parent_id`). Documentos uploadados pelo paciente: sem politica de delete (paciente nao deveria conseguir apagar documento medico apos upload)
[ ] **Documentos uploadados pelo paciente sem trilha de proveniencia** - hash do arquivo, IP de upload, timestamp imutavel. Necessario para validade clinica/legal

---

# RESUMO

| Categoria                                    | Pendente |
| -------------------------------------------- | -------- |
| Seguranca (critico)                          | 5        |
| Conformidade CFM (bloqueante)                | 13       |
| Videoconferencia                             | 8        |
| Backend - controllers/policies               | 7        |
| Backend - migrations                         | 5        |
| Backend - tasks/scheduler                    | 4        |
| Backend - servicos incompletos               | 4        |
| Backend - CRUDs e features                   | 10       |
| Frontend - dados hardcoded                   | 6        |
| Frontend - TODOs e bugs                      | 8        |
| Config e ambiente                            | 0        |
| Testes gerais                                | 12       |
| Interoperabilidade (branch)                  | 3        |
| Gravacao de sessao                           | 6        |
| Observabilidade                              | 5        |
| **QA Manual - Landing Page**                 | **8**    |
| **QA Manual - Login**                        | **3**    |
| **QA Manual - Registro Paciente**            | **6**    |
| **QA Manual - Registro Medico**              | **3**    |
| **QA Manual - Dashboard Medico**             | **17**   |
| **QA Manual - Dashboard Paciente**           | **7**    |
| **QA Manual - Agenda/Disponibilidade**       | **1**    |
| **QA Manual - Pacientes Medico**             | **7**    |
| **QA Manual - Detalhes do Paciente**         | **4**    |
| **QA Manual - Historico Medico**             | **5**    |
| **QA Manual - Emissao Documentos**           | **15**   |
| **QA Manual - Interoperabilidade**           | **24**   |
| **QA Manual - Pesquisar Medicos (Paciente)** | **23**   |
| **QA Manual - Agendar Consulta (Paciente)**  | **23**   |
| **QA Manual - Mensagens (Paciente)**         | **24**   |
| **QA Manual - Historico (Paciente)**         | **18**   |
| **QA Manual - Prontuario (Paciente)**        | **21**   |
| **TOTAL**                                    | **~305** |
