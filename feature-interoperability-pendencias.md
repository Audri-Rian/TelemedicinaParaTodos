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

# QA MANUAL - TESTES DE PAGINA (em andamento)

Realizado em: 2026-04-16

### Landing Page (`/`)

- [ ] Ajustar navbar: botoes "Registrar-se para Pacientes" e "Faca parte da equipe" estao muito colados, quebrando nomes como "A quem servimos"
- [ ] Corrigir links do dropdown da navbar que nao redirecionam para lugar nenhum. Devem redirecionar para login (se nao autenticado) e depois para a pagina relacionada (ex: Documentacao API -> login -> /api/documentation; Interoperabilidade -> login -> secao de integracao)
- [ ] Corrigir height do botao "Conheca nossa visao" que esta desalinhado em relacao ao botao "Agendar agora" - devem ter o mesmo tamanho
- [ ] Corrigir botao "Conheca agora" que nao redireciona para login -> dashboard
- [ ] Corrigir links da section 2 ("Descubra por que a Telemedicina para todos...") para direcionar ao login (se nao autenticado) ou ao dashboard (se ja logado)
- [ ] Corrigir botao "Agendar consulta agora" na penultima section para redirecionar ao login (se nao autenticado) ou a pagina de agendamentos (se ja logado)
- [ ] Corrigir links do footer para redirecionarem corretamente (passando pelo login se nao autenticado):
  - Especialidades -> pagina de agendamentos
  - Como funciona -> pagina de Dashboard
  - Sobre telemedicina -> landing page
  - Entrar no sistema -> pagina de Dashboard
- [ ] Redesenhar botao "Entrar" na navbar que esta escondido/pouco visivel

### Login (`/login`)

- [ ] Botoes de conexao com Google, Apple e Meta nao tem feature implementada (placeholder)
- [ ] Botao "Cadastre-se" redireciona apenas para /register/patient. Adicionar link/opcao para redirecionar tambem para /register/doctor
- [ ] Pagina e funcionalidade de "Esqueceu senha" nao esta implementada (design + backend)

### Registro Paciente (`/register/patient`)

- [ ] Imagem pendente/faltando na pagina
- [ ] Campo de data de nascimento nao tem seletor por calendario, apenas input de texto
- [ ] Botao "Criar conta" em telas grandes esta no canto inferior esquerdo - ajustar design (centralizar)
- [ ] Funcionalidade de registro com Google e outros provedores sociais nao implementada
- [ ] Container da esquerda ("Comece sua jornada") deve ter a mesma altura do container do formulario
- [ ] Criar componente padrao de select para o campo Genero

### Registro Medico (`/register/doctor`)

- [ ] Ajustar tamanho do input de especializacoes que esta fora de ordem visual comparado aos campos nome, CRM etc.
- [ ] Avaliar melhoria de arquitetura: carregar catalogo de especializacoes por endpoint dedicado/cache no frontend, reduzindo payload em respostas com erro de validacao (atualmente traz lista completa no retorno da tela)
- [ ] Separar conceitualmente dados de especializacoes disponiveis (renderizacao) das especializacoes selecionadas (formulario), para evitar confusao

### Dashboard Medico (`/doctor/dashboard`)

- [ ] Corrigir funcionalidade do tour que esta totalmente quebrada
- [ ] Corrigir: clicar em "Explorar por conta propria" ainda faz o tour aparecer
- [ ] Corrigir posicionamento das instrucoes do tour (aparecem no canto superior esquerdo em vez de junto ao elemento alvo)
- [ ] Implementar responsividade do tour
- [ ] Corrigir persistencia do estado do tour: fechar no X antes do fim e dar F5 nao deve reabrir o tour
- [ ] Investigar e corrigir lentidao/performance do tour
- [ ] Melhorar performance geral da pagina (Lighthouse)
- [ ] Corrigir KPI "Taxa de cumprimento" que mostra valor semanticamente incorreto
- [ ] Ajustar calculo de "Taxa de cumprimento" para considerar estados reais (concluidas, canceladas, no_show)
- [ ] Corrigir card "Pacientes agendados": UI informa "Proximas 24h" mas o numero exibido nao representa esse recorte
- [ ] Ajustar acoes da "Proxima consulta" e da tabela para levarem a consulta especifica, nao para paginas genericas
- [ ] Corrigir graficos semanal e mensal que subcontam consultas
- [ ] Fazer grafico semanal considerar toda a janela da estatistica semanal (nao apenas Seg a Sex)
- [ ] Fazer grafico mensal considerar corretamente meses com 5 semanas
- [ ] Implementar ou remover acoes visuais que hoje nao executam o que prometem
- [ ] Implementar handler real para o botao "Cancelar"
- [ ] Fazer botoes de "entrar em chamada" e "detalhes" levarem para a consulta/agendamento especifico

### Dashboard Paciente (`/patient/dashboard`)

- [ ] Problema de performance detectado no Lighthouse
- [ ] Tour esta quebrado
- [ ] Corrigir secao "Historico de Consultas": aponta para rota errada (search-consultations em vez de historico do paciente)
- [ ] Corrigir acoes da "Proxima Consulta" que nao levam para a consulta especifica:
  - "Entrar na videochamada" leva para tela generica video-call
  - "Reagendar" leva para search-consultations
  - "Cancelar" nao tem acao implementada
  - Tela de video-call esta marcada como "em atualizacao"
- [ ] Corrigir filtro de "Convenio" na secao "Encontrar Medico": estado insuranceFilter existe mas nao participa do filteredDoctors (filtro sem efeito real)
- [ ] Corrigir tour/welcome: comportamento incompativel com documentacao do projeto. "Explorar por conta" nao deveria iniciar tour; persistencia da decisao e fragil; fechar no X nao marca nada no backend
- [ ] Remover payload desnecessario do controller: recentAppointments e stats sao montados no backend mas nao aparecem na tela; reminders e healthTips sao sempre arrays vazios

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
| **QA Manual - Landing Page**     | **8**    |
| **QA Manual - Login**            | **3**    |
| **QA Manual - Registro Paciente**| **6**    |
| **QA Manual - Registro Medico**  | **3**    |
| **QA Manual - Dashboard Medico** | **17**   |
| **QA Manual - Dashboard Paciente**| **7**   |
| **TOTAL**                        | **~140** |
