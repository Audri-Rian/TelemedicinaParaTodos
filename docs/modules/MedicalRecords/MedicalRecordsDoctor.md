# 🩺 Módulo de Prontuários Médicos - Visão do Médico (Medical Records Doctor)

## 📑 Sumário Navegável

- [🎯 Objetivo](#-objetivo)
- [📊 Requisitos](#-requisitos)
- [⚖️ Regras de Negócio](#️-regras-de-negócio)
- [🔧 Funcionalidades](#-funcionalidades)
- [🎨 UX Detalhado](#-ux-detalhado)
- [🔄 Fluxo de Interação](#-fluxo-de-interação)
- [🔗 Integrações com Outros Módulos](#-integrações-com-outros-módulos)
- [🔐 Permissões de Acesso](#-permissões-de-acesso)
- [👥 Relação com a Visão do Paciente](#-relação-com-a-visão-do-paciente)
- [📝 Estrutura de Dados](#-estrutura-de-dados)
- [🗄️ Modelos Envolvidos](#️-modelos-envolvidos)
- [🔒 Segurança e Privacidade](#-segurança-e-privacidade)
- [📋 Auditoria e Rastreabilidade](#-auditoria-e-rastreabilidade)
- [❌ O Que Falta Implementar](#-o-que-falta-implementar)
- [💡 Recomendações de Melhoria](#-recomendações-de-melhoria)
- [✅ Checklist de Features](#-checklist-de-features)

---

## 🎯 Objetivo

O módulo de **Prontuários Médicos - Visão do Médico** tem como objetivo fornecer aos profissionais de saúde uma interface completa e funcional para visualizar, gerenciar e atualizar prontuários médicos de seus pacientes, permitindo um atendimento mais eficiente, preciso e seguro.

### Principais Objetivos:

1. **Acesso Completo**: Visualizar histórico médico completo dos pacientes atendidos
2. **Gestão Clínica**: Registrar diagnósticos, prescrições, exames e evoluções
3. **Contexto Durante Consulta**: Acesso imediato ao prontuário durante consultas em andamento
4. **Decisões Informadas**: Histórico completo para tomada de decisões clínicas precisas
5. **Compliance Legal**: Atender regulamentações médicas e de proteção de dados (LGPD/CFM)
6. **Auditoria Completa**: Rastreabilidade total de todas as ações realizadas
7. **Integração**: Conectar-se com outros módulos (prescrições, exames, chat, etc.)

### Diferenças da Visão do Paciente:

Enquanto o paciente tem acesso apenas para **visualizar** seu prontuário, o médico tem acesso para:
- **Visualizar** prontuário completo (incluindo anotações privadas)
- **Editar** informações clínicas durante consulta
- **Registrar** diagnósticos e CID-10
- **Emitir** prescrições digitais
- **Solicitar** exames
- **Anexar** documentos e laudos
- **Registrar** anotações clínicas (públicas e privadas)
- **Emitir** atestados
- **Gerar** PDF de consultas

---

## 📊 Requisitos

### Requisitos Funcionais

#### RF001 - Lista de Pacientes Atendidos
- **Descrição**: Médico deve poder visualizar lista de todos os pacientes que já teve consultas
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Detalhes**:
  - Acesso através da rota `/doctor/patients` ou `/doctor/medical-records`
  - Lista paginada de pacientes com histórico de consultas
  - Filtros por nome, CPF, data da última consulta, diagnóstico
  - Ordenação por data da última consulta, nome, número de consultas
  - Busca textual rápida
  - Cards ou tabela responsiva

#### RF002 - Visualização do Prontuário Completo
- **Descrição**: Médico deve poder visualizar prontuário completo de pacientes atendidos
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Detalhes**:
  - Acesso através de `/doctor/patient/{id}/medical-record` ou `/doctor/patients/{id}`
  - Visualização durante consulta em andamento (`/doctor/consultations/{appointment_id}`)
  - Dados pessoais completos do paciente
  - Histórico médico completo (incluindo consultas de outros médicos, se relevante)
  - Anotações privadas do médico (não visíveis ao paciente)
  - Contexto durante consulta atual

#### RF003 - Organização por Abas/Seções
- **Descrição**: Prontuário deve ser organizado em seções temáticas otimizadas para uso médico
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Seções Planejadas**:
  1. **Visão Geral** - Resumo executivo do paciente (dados pessoais, alertas, última consulta)
  2. **Histórico Clínico** - Timeline completa de consultas e eventos médicos
  3. **Consultas** - Lista detalhada com filtros e busca avançada
  4. **Diagnósticos** - Histórico de diagnósticos e CID-10
  5. **Prescrições** - Medicamentos prescritos, histórico de medicamentos
  6. **Exames** - Exames solicitados, resultados, laudos
  7. **Documentos** - Documentos anexados, laudos, imagens
  8. **Evolução** - Registros de evolução clínica, sinais vitais
  9. **Anotações Clínicas** - Notas privadas do médico e compartilhadas

#### RF004 - Registro de Diagnóstico
- **Descrição**: Médico deve poder registrar diagnóstico com CID-10 durante consulta
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Busca inteligente por CID-10 (código ou descrição)
  - Sugestões baseadas em sintomas
  - Histórico de diagnósticos anteriores do paciente
  - Múltiplos diagnósticos por consulta (principal e secundários)
  - Associação automática à consulta atual
  - Validação de código CID-10
  - Tags e categorias de diagnóstico

#### RF005 - Emissão de Prescrições Digitais
- **Descrição**: Médico deve poder emitir prescrições médicas digitais
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Formulário de prescrição estruturado
  - Busca de medicamentos (API externa ou base local)
  - Validação de interações medicamentosas
  - Alerta de alergias conhecidas
  - Posologia detalhada (dose, frequência, duração)
  - Instruções especiais para uso
  - Assinatura digital do médico (CRM)
  - Geração de PDF da prescrição
  - Validade da prescrição
  - Envio automático ao paciente
  - Armazenamento no prontuário

#### RF006 - Solicitação de Exames
- **Descrição**: Médico deve poder solicitar exames laboratoriais e de imagem
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Catálogo de exames disponíveis
  - Busca por tipo (laboratorial, imagem, outros)
  - Seleção múltipla de exames
  - Instruções pré-exame para o paciente
  - Data sugerida para realização
  - Prioridade (normal, urgente)
  - Justificativa clínica
  - Envio automático ao paciente
  - Status de aprovação/realização (integração futura com laboratórios)

#### RF007 - Upload e Anexo de Documentos
- **Descrição**: Médico deve poder anexar documentos ao prontuário
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Upload de múltiplos arquivos (PDF, imagens, documentos)
  - Drag & drop interface
  - Categorização de documentos (laudo, exame, relatório, imagem)
  - Associação com consulta específica
  - Preview de documentos antes de salvar
  - Validação de tipo e tamanho de arquivo
  - Armazenamento seguro (S3 ou local)
  - Controle de versão de documentos

#### RF008 - Registro de Anotações Clínicas
- **Descrição**: Médico deve poder registrar anotações clínicas (públicas e privadas)
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Anotações privadas (visíveis apenas ao médico)
  - Anotações compartilhadas (visíveis ao paciente)
  - Editor de texto rico
  - Templates de anotações pré-definidos
  - Histórico de versões de anotações
  - Associação com consulta ou data específica
  - Tags e categorização
  - Busca em anotações

#### RF009 - Registro de Sinais Vitais
- **Descrição**: Médico deve poder registrar sinais vitais durante consulta
- **Prioridade**: Média
- **Status**: ✅ Implementado
- **Sinais a Registrar**:
  - Pressão arterial (sistólica/diastólica)
  - Temperatura corporal
  - Frequência cardíaca (pulso)
  - Frequência respiratória
  - Saturação de oxigênio (SpO2)
  - Peso e altura (atualização)
  - IMC (cálculo automático)
- **Funcionalidades**:
  - Formulário rápido de registro
  - Gráficos de evolução histórica
  - Comparação com valores anteriores
  - Alertas para valores fora do normal
  - Exportação de gráficos

#### RF010 - Emissão de Atestados
- **Descrição**: Médico deve poder emitir atestados médicos digitais
- **Prioridade**: Média
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Templates de atestados (afastamento, comparecimento, etc.)
  - Campos editáveis (período, motivo, restrições)
  - Assinatura digital (CRM)
  - Geração de PDF
  - Código de verificação único
  - Validade do atestado
  - Envio ao paciente
  - Armazenamento no prontuário

#### RF011 - Plano Terapêutico
- **Descrição**: Médico deve poder criar e gerenciar planos terapêuticos
- **Prioridade**: Média
- **Status**: ❌ Não Implementado
- **Componentes**:
  - Medicamentos e posologias
  - Recomendações de exercícios
  - Orientações alimentares
  - Encaminhamentos (especialistas, fisioterapia, etc.)
  - Objetivos clínicos
  - Prazos e metas
  - Acompanhamento e revisões
- **Funcionalidades**:
  - Templates de planos terapêuticos
  - Edição e atualização de planos
  - Histórico de planos anteriores
  - Compartilhamento com paciente

#### RF012 - Geração de PDF de Consulta
- **Descrição**: Médico deve poder gerar PDF resumido de cada consulta
- **Prioridade**: Baixa
- **Status**: ❌ Não Implementado
- **Conteúdo do PDF**:
  - Dados do paciente e médico
  - Data e horário da consulta
  - Queixa principal
  - Diagnóstico e CID-10
  - Prescrições
  - Exames solicitados
  - Orientações
- **Funcionalidades**:
  - Template profissional
  - Assinatura digital
  - Campos editáveis antes de gerar
  - Download imediato
  - Envio ao paciente (opcional)

#### RF013 - Busca e Filtros Avançados
- **Descrição**: Sistema deve permitir busca e filtros avançados no prontuário
- **Prioridade**: Alta
- **Status**: ❌ Não Implementado
- **Funcionalidades**:
  - Busca textual em todo o prontuário (diagnóstico, anotações, prescrições)
  - Filtro por período (data início/fim)
  - Filtro por tipo de consulta
  - Filtro por diagnóstico/CID-10
  - Filtro por medicamento prescrito
  - Filtro por exame realizado
  - Filtros combinados (AND/OR)
  - Salvar filtros favoritos
  - Histórico de buscas recentes

#### RF014 - Alertas e Notificações
- **Descrição**: Sistema deve exibir alertas relevantes no prontuário
- **Prioridade**: Média
- **Status**: ❌ Não Implementado
- **Tipos de Alertas**:
  - Alergias conhecidas (destacado)
  - Interações medicamentosas
  - Medicamentos contraindicados
  - Exames pendentes de resultado
  - Prescrições expirando
  - Consultas não comparecidas (no-show)
  - Valores de sinais vitais fora do normal

### Requisitos Não Funcionais

#### NF001 - Segurança e Privacidade Médica
- **Descrição**: Dados médicos devem ser protegidos com segurança máxima e compliance legal
- **Prioridade**: Crítica
- **Status**: 🔄 Parcialmente Implementado
- **Medidas Necessárias**:
  - Criptografia de dados sensíveis em repouso e em trânsito
  - Controle de acesso baseado em roles e relacionamentos médicos
  - Logs de auditoria para todas as ações (LGPD/CFM)
  - Assinatura digital de documentos médicos
  - Políticas de retenção de dados conforme CFM
  - Compliance com LGPD e Resolução CFM 1.821/2007
  - Bloqueio automático após período de inatividade

#### NF002 - Performance e Responsividade
- **Descrição**: Interface deve ser rápida e responsiva para uso durante consultas
- **Prioridade**: Alta
- **Status**: 🔄 Parcialmente Implementado
- **Medidas**:
  - Carregamento rápido de prontuário (< 2 segundos)
  - Cache inteligente de dados frequentemente acessados
  - Paginação eficiente de listas longas
  - Lazy loading de seções
  - Atualização em tempo real durante consulta
  - Otimização de queries com índices adequados
  - Compressão de imagens e documentos

#### NF003 - Disponibilidade e Confiabilidade
- **Descrição**: Sistema deve estar sempre disponível, especialmente durante consultas
- **Prioridade**: Crítica
- **Status**: ✅ Implementado
- **Detalhes**:
  - Uptime de 99.9% ou superior
  - Backup automático e redundância
  - Monitoramento de disponibilidade em tempo real
  - Plano de recuperação de desastres
  - Sincronização offline (futuro)

#### NF004 - Usabilidade em Consulta
- **Descrição**: Interface deve ser intuitiva para uso rápido durante consultas
- **Prioridade**: Alta
- **Status**: ❌ Não Implementado
- **Aspectos**:
  - Atalhos de teclado para ações frequentes
  - Templates pré-preenchidos
  - Autocomplete inteligente
  - Interface touch-friendly para tablets
  - Modo de consulta (tela simplificada)
  - Navegação rápida entre seções
  - Feedback visual imediato

#### NF005 - Escalabilidade
- **Descrição**: Sistema deve suportar crescimento de médicos e pacientes
- **Prioridade**: Média
- **Status**: ✅ Implementado
- **Detalhes**:
  - Arquitetura preparada para escala horizontal
  - Estratégias de arquivamento de dados antigos
  - Otimização de armazenamento de documentos
  - Load balancing e CDN para assets

#### NF006 - Integração com Sistemas Externos
- **Descrição**: Sistema deve permitir integração com laboratórios e outras plataformas
- **Prioridade**: Média
- **Status**: ❌ Não Implementado
- **Integrações Planejadas**:
  - Laboratórios: Importação automática de resultados
  - Farmácias: Envio de prescrições digitais
  - Seguradoras: Validação de cobertura
  - Sistemas hospitalares: Interoperabilidade (HL7/FHIR)

---

## ⚖️ Regras de Negócio

### RB001 - Acesso ao Prontuário do Paciente
- **Regra**: Médico pode acessar prontuário apenas de pacientes que tiveram consultas com ele
- **Validação**: Verificar existência de `Appointment` com `doctor_id` e `patient_id` correspondentes
- **Exceções**: 
  - Em consulta em andamento, acesso é permitido mesmo se não houver histórico prévio
  - Em emergências, acesso pode ser liberado com autorização especial (futuro)
- **Implementação**: Policy `MedicalRecordPolicy::view()`

### RB002 - Visualização de Consultas de Outros Médicos
- **Regra**: Médico vê apenas suas próprias consultas, mas pode ver dados gerais do paciente
- **Validação**: Filtrar consultas por `doctor_id` do médico autenticado
- **Exceções**: 
  - Durante consulta em andamento, pode ver histórico completo se relevante (opcional)
  - Com consentimento do paciente, pode ver prontuário compartilhado (futuro)
- **Implementação**: Query com filtro `where('doctor_id', auth()->user()->doctor->id)`

### RB003 - Edição de Dados Clínicos
- **Regra**: Médico pode editar dados clínicos apenas durante consulta em andamento ou após
- **Validação**: Verificar se consulta está `in_progress` ou `completed`
- **Restrições**:
  - Dados de consultas finalizadas não podem ser editados (apenas complementados)
  - Dados pessoais do paciente são editáveis apenas pelo paciente ou com permissão especial
- **Implementação**: Middleware e validações no Service

### RB004 - Registro de Diagnóstico
- **Regra**: Diagnóstico deve ser registrado durante ou após consulta
- **Validação**: 
  - CID-10 obrigatório para diagnóstico principal
  - Múltiplos diagnósticos permitidos (principal e secundários)
  - Validação de código CID-10 contra base oficial
- **Implementação**: Form Request e Service de validação

### RB005 - Emissão de Prescrições
- **Regra**: Prescrições devem ser assinadas digitalmente pelo médico
- **Validação**:
  - CRM obrigatório para assinatura
  - Medicamento deve estar em catálogo válido
  - Verificação de alergias antes de prescrever
  - Verificação de interações medicamentosas
- **Implementação**: Service de prescrições com validações

### RB006 - Solicitação de Exames
- **Regra**: Exames solicitados devem ter justificativa clínica
- **Validação**:
  - Campo de justificativa obrigatório
  - Tipo de exame deve ser válido
  - Prioridade deve ser definida
  - Instruções pré-exame recomendadas
- **Implementação**: Form Request com validação

### RB007 - Retenção de Dados Médicos
- **Regra**: Prontuários médicos devem ser mantidos por período mínimo de 20 anos (CFM)
- **Implementação**: Soft delete, não permite exclusão permanente
- **Exceções**: Apenas administradores com auditoria completa podem excluir
- **Arquivamento**: Dados antigos podem ser arquivados, mas não excluídos

### RB008 - Auditoria de Ações Médicas
- **Regra**: Todas as ações no prontuário devem ser registradas com timestamp e usuário
- **Ações Auditadas**:
  - Visualização do prontuário
  - Registro de diagnóstico
  - Emissão de prescrição
  - Solicitação de exame
  - Upload de documento
  - Edição de dados clínicos
  - Emissão de atestado
  - Geração de PDF
- **Registro**: Incluir `user_id`, `doctor_id`, `patient_id`, `action`, `timestamp`, `ip_address`, `metadata`

### RB009 - Assinatura Digital
- **Regra**: Documentos médicos críticos devem ser assinados digitalmente
- **Documentos que Requerem Assinatura**:
  - Prescrições
  - Atestados
  - Laudos médicos
  - Relatórios de consulta
- **Implementação**: Certificado digital (ICP-Brasil) ou assinatura eletrônica validada

### RB010 - Privacidade de Anotações
- **Regra**: Anotações privadas do médico não são visíveis ao paciente
- **Validação**: Campo `is_private` no modelo de anotações
- **Implementação**: Filtrar anotações por `is_private = false` na visão do paciente

### RB011 - Travamento Após Finalização
- **Regra**: Consulta finalizada não pode ter dados críticos editados
- **Dados Bloqueados Após Finalização**:
  - Diagnóstico
  - CID-10
  - Prescrições emitidas
  - Exames solicitados
- **Dados Permitidos**:
  - Comentários e complementos
  - Anexos adicionais
  - Correções de erros (com justificativa e auditoria)
- **Implementação**: Middleware e validações no Service

### RB012 - Notificações ao Paciente
- **Regra**: Paciente deve ser notificado de ações importantes
- **Ações que Requerem Notificação**:
  - Prescrição emitida
  - Exame solicitado
  - Resultado de exame disponível
  - Atestado emitido
  - Documento anexado
- **Implementação**: Sistema de notificações (email, push, SMS)

---

## 🔧 Funcionalidades

### Funcionalidades Implementadas ✅

#### 1. Página de Lista de Pacientes
- **Arquivo**: `resources/js/pages/Doctor/Patients.vue`
- **Controller**: `app/Http/Controllers/Doctor/DoctorPatientsController.php`
- **Rota**: `/doctor/patients`
- **Status**: ✅ Interface Básica Implementada
- **Funcionalidades Atuais**:
  - Lista básica de pacientes (mock data)
  - Navegação para detalhes do paciente

#### 2. Página de Detalhes do Paciente
- **Arquivo**: `resources/js/pages/Doctor/PatientDetails.vue`
- **Controller**: `app/Http/Controllers/Doctor/PatientDetailsController.php`
- **Rota**: `/doctor/patient/{id}`
- **Status**: ✅ Interface Básica Implementada
- **Funcionalidades Atuais**:
  - Visualização básica de dados do paciente (mock)
  - Cards com informações pessoais
  - Lista de consultas recentes

### Funcionalidades Implementadas ✅

#### 1. Estrutura de Rotas Completa
- **Status**: ✅ Rotas implementadas e funcionais
- **Rotas Implementadas**:
  - `/doctor/patients` - Lista de pacientes
  - `/doctor/patient/{id}` - Detalhes do paciente
  - `/doctor/patients/{patient}/medical-record` - Prontuário completo
  - `/doctor/consultations/{appointment}` - Consulta em andamento
  - `/doctor/consultations/{appointment}/start` - Iniciar consulta
  - `/doctor/consultations/{appointment}/save-draft` - Salvar rascunho
  - `/doctor/consultations/{appointment}/finalize` - Finalizar consulta
  - `/doctor/consultations/{appointment}/complement` - Adicionar complemento
  - `/doctor/consultations/{appointment}/pdf` - Gerar PDF
  - `/doctor/patients/{patient}/medical-record/export` - Exportar prontuário
  - `/doctor/patients/{patient}/medical-record/diagnoses` - Criar diagnóstico
  - `/doctor/patients/{patient}/medical-record/prescriptions` - Criar prescrição
  - `/doctor/patients/{patient}/medical-record/examinations` - Criar exame
  - `/doctor/patients/{patient}/medical-record/notes` - Criar anotação
  - `/doctor/patients/{patient}/medical-record/certificates` - Criar atestado
  - `/doctor/patients/{patient}/medical-record/vital-signs` - Registrar sinais vitais
  - `/doctor/patients/{patient}/medical-record/documents` - Anexar documento

#### 2. Service Layer para Medical Records (Médico)
- **Arquivo**: `app/MedicalRecord/Application/Services/MedicalRecordService.php` ✅ Implementado
- **Métodos Implementados**:
  - `getDoctorPatientList(Doctor $doctor, array $filters = []): Collection` ✅
  - `getDoctorPatientMedicalRecord(Doctor $doctor, Patient $patient): array` ✅
  - `canDoctorViewPatientRecord(Doctor $doctor, Patient $patient): bool` ✅
  - `registerDiagnosis(Appointment $appointment, array $diagnosisData): void` ✅
  - `issuePrescription(Doctor $doctor, Patient $patient, Appointment $appointment, array $medicationData): Prescription` ✅
  - `requestExamination(Doctor $doctor, Patient $patient, Appointment $appointment, array $examData): Examination` ✅
  - `uploadDocument(Doctor $doctor, Patient $patient, array $fileData): MedicalDocument` ✅
  - `createClinicalNote(Doctor $doctor, Patient $patient, array $noteData): ClinicalNote` ✅
  - `issueCertificate(Doctor $doctor, Patient $patient, Appointment $appointment, array $certificateData): Certificate` - Emitir atestado
  - `registerVitalSigns(Appointment $appointment, array $vitalSigns): VitalSign` - Registrar sinais vitais
  - `generateConsultationPDF(Appointment $appointment): string` - Gerar PDF de consulta

#### 2. Medical Record Policy (Médico)
- **Arquivo**: `app/Policies/MedicalRecordPolicy.php`
- **Métodos Específicos para Médico**:
  - `viewAny(User $user): bool` - Listar prontuários (médicos podem listar)
  - `view(User $user, Patient $patient): bool` - Visualizar prontuário (validar relacionamento)
  - `update(User $user, Patient $patient): bool` - Editar dados clínicos
  - `registerDiagnosis(User $user, Patient $patient): bool` - Registrar diagnóstico
  - `issuePrescription(User $user, Patient $patient): bool` - Emitir prescrição
  - `requestExamination(User $user, Patient $patient): bool` - Solicitar exame
  - `uploadDocument(User $user, Patient $patient): bool` - Upload de documento
  - `createNote(User $user, Patient $patient): bool` - Criar anotação clínica
  - `issueCertificate(User $user, Patient $patient): bool` - Emitir atestado

#### 3. Controller para Prontuário Médico (Médico)
- **Arquivo**: `app/Http/Controllers/Doctor/DoctorPatientMedicalRecordController.php`
- **Métodos**:
  - `index(string $patientId): Response` - Visualizar prontuário completo
  - `update(string $patientId, Request $request): Response` - Atualizar dados clínicos
  - `storeDiagnosis(string $patientId, Request $request): Response` - Registrar diagnóstico
  - `storePrescription(string $patientId, Request $request): Response` - Emitir prescrição
  - `storeExamination(string $patientId, Request $request): Response` - Solicitar exame
  - `storeDocument(string $patientId, Request $request): Response` - Upload documento
  - `storeNote(string $patientId, Request $request): Response` - Criar anotação
  - `storeCertificate(string $patientId, Request $request): Response` - Emitir atestado
  - `storeVitalSigns(string $patientId, Request $request): Response` - Registrar sinais vitais
  - `generatePDF(string $patientId, string $appointmentId): Response` - Gerar PDF

#### 4. Modelo de Prescrições
- **Tabela**: `prescriptions`
- **Migration**: `create_prescriptions_table.php`
- **Campos**:
  ```php
  - id (UUID)
  - appointment_id (FK, nullable)
  - doctor_id (FK)
  - patient_id (FK)
  - medications (JSON) // Array de medicamentos com posologia
  - instructions (text)
  - valid_until (date)
  - status (enum: active, expired, cancelled, completed)
  - signature_hash (string) // Hash da assinatura digital
  - crm_number (string) // CRM do médico assinante
  - metadata (JSON)
  - created_at, updated_at, deleted_at
  ```
- **Relacionamentos**:
  - `belongsTo(Appointment::class)`
  - `belongsTo(Doctor::class)`
  - `belongsTo(Patient::class)`

#### 5. Modelo de Exames
- **Tabela**: `examinations`
- **Migration**: `create_examinations_table.php`
- **Campos**:
  ```php
  - id (UUID)
  - appointment_id (FK, nullable)
  - doctor_id (FK)
  - patient_id (FK)
  - type (enum: lab, image, other)
  - name (string)
  - description (text, nullable)
  - justification (text) // Justificativa clínica
  - priority (enum: normal, urgent)
  - instructions (text, nullable) // Instruções pré-exame
  - requested_at (date)
  - completed_at (date, nullable)
  - results (JSON ou text, nullable)
  - attachment_url (string, nullable)
  - status (enum: requested, approved, in_progress, completed, cancelled)
  - metadata (JSON)
  - created_at, updated_at, deleted_at
  ```
- **Relacionamentos**:
  - `belongsTo(Appointment::class)`
  - `belongsTo(Doctor::class)`
  - `belongsTo(Patient::class)`

#### 6. Modelo de Anotações Clínicas
- **Tabela**: `clinical_notes`
- **Migration**: `create_clinical_notes_table.php`
- **Campos**:
  ```php
  - id (UUID)
  - appointment_id (FK, nullable)
  - doctor_id (FK)
  - patient_id (FK)
  - title (string)
  - content (text) // Rich text
  - is_private (boolean) // true = apenas médico, false = visível ao paciente
  - category (enum: general, diagnosis, treatment, follow_up, other)
  - tags (JSON, nullable)
  - version (integer) // Controle de versão
  - parent_id (FK, nullable) // Para histórico de edições
  - created_at, updated_at, deleted_at
  ```
- **Relacionamentos**:
  - `belongsTo(Appointment::class)`
  - `belongsTo(Doctor::class)`
  - `belongsTo(Patient::class)`
  - `belongsTo(ClinicalNote::class, 'parent_id')` // Para histórico

#### 7. Modelo de Atestados
- **Tabela**: `medical_certificates`
- **Migration**: `create_medical_certificates_table.php`
- **Campos**:
  ```php
  - id (UUID)
  - appointment_id (FK, nullable)
  - doctor_id (FK)
  - patient_id (FK)
  - type (enum: absence, attendance, disability, other)
  - start_date (date)
  - end_date (date, nullable)
  - days (integer)
  - reason (text)
  - restrictions (text, nullable)
  - signature_hash (string)
  - crm_number (string)
  - verification_code (string, unique) // Código de verificação
  - pdf_url (string)
  - status (enum: active, expired, cancelled)
  - created_at, updated_at, deleted_at
  ```
- **Relacionamentos**:
  - `belongsTo(Appointment::class)`
  - `belongsTo(Doctor::class)`
  - `belongsTo(Patient::class)`

#### 8. Modelo de Sinais Vitais
- **Tabela**: `vital_signs`
- **Migration**: `create_vital_signs_table.php`
- **Campos**:
  ```php
  - id (UUID)
  - appointment_id (FK)
  - patient_id (FK)
  - doctor_id (FK)
  - blood_pressure_systolic (integer, nullable)
  - blood_pressure_diastolic (integer, nullable)
  - temperature (decimal:1, nullable) // em Celsius
  - heart_rate (integer, nullable)
  - respiratory_rate (integer, nullable)
  - oxygen_saturation (integer, nullable) // SpO2 em %
  - weight (decimal:2, nullable) // em kg
  - height (decimal:2, nullable) // em cm
  - bmi (decimal:2, nullable) // Calculado
  - notes (text, nullable)
  - recorded_at (datetime)
  - created_at, updated_at
  ```
- **Relacionamentos**:
  - `belongsTo(Appointment::class)`
  - `belongsTo(Patient::class)`
  - `belongsTo(Doctor::class)`

#### 9. Modelo de Diagnósticos
- **Tabela**: `diagnoses` (ou adicionar em appointments.metadata)
- **Opção 1**: Tabela separada
- **Campos**:
  ```php
  - id (UUID)
  - appointment_id (FK)
  - doctor_id (FK)
  - patient_id (FK)
  - cid10_code (string)
  - cid10_description (string)
  - diagnosis_type (enum: principal, secondary)
  - description (text, nullable)
  - created_at, updated_at
  ```
- **Opção 2**: Usar `appointments.metadata` JSON com estrutura padronizada

#### 10. Interface de Prontuário Completo
- **Arquivo**: `resources/js/pages/Doctor/PatientMedicalRecord.vue`
- **Componentes Necessários**:
  - `DoctorMedicalRecordHeader.vue` - Header com dados do paciente
  - `DoctorMedicalRecordTabs.vue` - Navegação por abas
  - `DoctorMedicalRecordOverview.vue` - Visão geral
  - `DoctorMedicalRecordHistory.vue` - Histórico clínico
  - `DoctorMedicalRecordConsultations.vue` - Lista de consultas
  - `DoctorMedicalRecordDiagnoses.vue` - Diagnósticos
  - `DoctorMedicalRecordPrescriptions.vue` - Prescrições
  - `DoctorMedicalRecordExaminations.vue` - Exames
  - `DoctorMedicalRecordDocuments.vue` - Documentos
  - `DoctorMedicalRecordEvolution.vue` - Evolução
  - `DoctorMedicalRecordNotes.vue` - Anotações clínicas

#### 11. Componente de Registro de Diagnóstico
- **Arquivo**: `resources/js/components/doctor/DiagnosisForm.vue`
- **Funcionalidades**:
  - Busca de CID-10 (autocomplete)
  - Seleção de tipo (principal/secundário)
  - Múltiplos diagnósticos
  - Histórico de diagnósticos anteriores
  - Validação de código

#### 12. Componente de Emissão de Prescrição
- **Arquivo**: `resources/js/components/doctor/PrescriptionForm.vue`
- **Funcionalidades**:
  - Busca de medicamentos
  - Formulário de posologia
  - Validação de interações
  - Alerta de alergias
  - Preview da prescrição
  - Assinatura digital

#### 13. Componente de Solicitação de Exames
- **Arquivo**: `resources/js/components/doctor/ExaminationRequestForm.vue`
- **Funcionalidades**:
  - Catálogo de exames
  - Seleção múltipla
  - Campos de justificativa
  - Instruções pré-exame
  - Prioridade

#### 14. Componente de Upload de Documentos
- **Arquivo**: `resources/js/components/doctor/DocumentUpload.vue`
- **Funcionalidades**:
  - Drag & drop
  - Preview
  - Categorização
  - Validação de tipo e tamanho

#### 15. Componente de Registro de Sinais Vitais
- **Arquivo**: `resources/js/components/doctor/VitalSignsForm.vue`
- **Funcionalidades**:
  - Formulário rápido
  - Validação de valores
  - Comparação com histórico
  - Alertas de valores anormais

---

## 🎨 UX Detalhado

### 1. Página de Lista de Pacientes Atendidos

#### Rota: `/doctor/patients` ou `/doctor/medical-records`

#### Layout e Design
- **Header Fixo**:
  - Título: "Meus Pacientes" ou "Prontuários Médicos"
  - Contador total de pacientes
  - Botão de busca rápida
  
- **Barra de Filtros e Busca**:
  - **Busca Textual**: Input de busca com autocomplete
    - Busca por nome do paciente
    - Busca por CPF
    - Busca por número do prontuário
    - Busca por diagnóstico
    - Sugestões em tempo real
  
  - **Filtros Avançados** (Sidebar ou Accordion):
    - **Por Data da Última Consulta**:
      - Últimos 7 dias
      - Último mês
      - Últimos 3 meses
      - Último ano
      - Período customizado (date picker)
    
    - **Por Diagnóstico/CID-10**:
      - Select com autocomplete de CID-10
      - Múltiplos diagnósticos
    
    - **Por Status**:
      - Pacientes ativos
      - Pacientes inativos
      - Todos
    
    - **Por Número de Consultas**:
      - Primeira consulta
      - Consultas recorrentes (2+)
      - Pacientes frequentes (5+)
    
    - **Por Especialidade**:
      - Filtrar por especialidade da consulta
  
  - **Ordenação**:
    - Data da última consulta (mais recente primeiro)
    - Nome (A-Z)
    - Número de consultas (maior primeiro)
    - Data da primeira consulta

#### Visualização: Cards vs Tabela
- **Modo Cards** (Padrão para Desktop):
  - Card por paciente com:
    - Avatar e nome
    - Idade e gênero
    - Data da última consulta
    - Número total de consultas
    - Diagnóstico principal (último)
    - Badge de alertas (alergias, exames pendentes)
    - Botão "Ver Prontuário"
  
- **Modo Tabela** (Alternativo):
  - Colunas: Nome, Idade, Última Consulta, Total Consultas, Diagnóstico, Ações
  - Ordenação por colunas
  - Paginação na parte inferior

#### Paginação
- 20 pacientes por página
- Navegação: Anterior, Próxima, Números de página
- Mostrar total de resultados

#### Empty States
- Quando não há pacientes: "Você ainda não tem pacientes cadastrados."
- Quando filtro não retorna resultados: "Nenhum paciente encontrado com os filtros selecionados."

### 2. Página de Prontuário Completo do Paciente

#### Rota: `/doctor/patient/{id}/medical-record`

#### Header do Paciente
- **Informações Principais**:
  - Avatar e nome do paciente (grande, destacado)
  - Idade, gênero, data de nascimento
  - ID do paciente (prontuário)
  - Status (ativo, inativo)
  
- **Alertas e Avisos** (Banner destacado):
  - ⚠️ Alergias conhecidas (sempre visível no topo)
  - ⚠️ Exames pendentes
  - ⚠️ Prescrições expirando
  - ⚠️ Interações medicamentosas ativas
  
- **Ações Rápidas** (Botões no header):
  - "Nova Consulta"
  - "Enviar Mensagem"
  - "Gerar PDF do Prontuário"
  - "Compartilhar" (futuro)

#### Barra de Tabs (Navegação Principal)
- **Tabs Disponíveis**:
  1. **Visão Geral** 🏠
     - Resumo executivo
     - Dados pessoais
     - Última consulta
     - Alertas principais
  
  2. **Histórico Clínico** 📅
     - Timeline completa
     - Todas as consultas
     - Eventos médicos
  
  3. **Consultas** 🩺
     - Lista detalhada
     - Filtros e busca
     - Detalhes expandidos
  
  4. **Diagnósticos** 🏥
     - Lista de diagnósticos
     - CID-10
     - Histórico cronológico
  
  5. **Prescrições** 💊
     - Prescrições ativas
     - Histórico completo
     - Status de validade
  
  6. **Exames** 🔬
     - Exames solicitados
     - Resultados disponíveis
     - Status e prioridade
  
  7. **Documentos** 📄
     - Documentos anexados
     - Laudos e imagens
     - Upload de novos
  
  8. **Evolução** 📈
     - Gráficos de sinais vitais
     - Evolução de peso/IMC
     - Marcadores importantes
  
  9. **Anotações** 📝
     - Anotações privadas
     - Anotações compartilhadas
     - Histórico de versões

#### Aba: Visão Geral
- **Cards Resumo**:
  - **Dados Pessoais**:
    - Nome completo
    - CPF (mascarado)
    - Data de nascimento e idade
    - Gênero
    - Telefone e email
    - Endereço
    - Contato de emergência
  
  - **Dados Médicos Básicos**:
    - Tipo sanguíneo
    - Altura e peso atuais
    - IMC e categoria
    - Alergias conhecidas (lista destacada)
    - Medicações em uso
    - Histórico médico resumido
  
  - **Última Consulta**:
    - Data e horário
    - Diagnóstico
    - Prescrições ativas
    - Exames pendentes
  
  - **Estatísticas**:
    - Total de consultas
    - Primeira consulta
    - Última consulta
    - Taxa de comparecimento
    - Diagnóstico mais frequente

#### Aba: Histórico Clínico
- **Timeline Vertical**:
  - Eventos ordenados cronologicamente (mais recente no topo)
  - Conector visual entre eventos
  - Ícones por tipo: Consulta, Diagnóstico, Prescrição, Exame, Documento, Anotação
  - Cards expansíveis
  
- **Card de Evento**:
  - Data formatada (ex: "20 de Julho, 2024 - 10:00")
  - Tipo de evento (badge colorido)
  - Resumo (médico, diagnóstico, etc.)
  - Botão "Ver Detalhes"
  
- **Detalhes Expandidos**:
  - Informações completas do evento
  - Ações rápidas (editar, anexar, etc.)

#### Aba: Consultas
- **Lista de Consultas com Filtros**:
  - Filtros: Período, Status, Tipo
  - Busca textual
  - Ordenação
  
- **Card/Item de Consulta**:
  - Data e horário
  - Duração
  - Status (badge)
  - Diagnóstico
  - Ações: Ver detalhes, Gerar PDF, Editar (se permitido)
  
- **Modal/Detalhes da Consulta**:
  - Informações completas
  - Diagnóstico e CID-10
  - Sintomas relatados
  - Prescrições emitidas
  - Exames solicitados
  - Anotações do médico
  - Documentos anexados
  - Sinais vitais registrados

#### Aba: Diagnósticos
- **Lista de Diagnósticos**:
  - Cards com:
    - CID-10 (código e descrição)
    - Data do diagnóstico
    - Médico responsável
    - Tipo (principal/secundário)
    - Consulta associada
  
- **Botão "Registrar Diagnóstico"**:
  - Abre modal/formulário
  - Busca de CID-10
  - Campos: Código, Descrição, Tipo, Observações

#### Aba: Prescrições
- **Prescrições Ativas** (Seção no topo):
  - Cards com medicamentos ativos
  - Status de validade
  - Alertas de expiração
  
- **Histórico de Prescrições**:
  - Lista completa ordenada por data
  - Status: Ativa, Expirada, Cancelada
  - Visualização completa
  - Download de PDF
  
- **Botão "Nova Prescrição"**:
  - Abre formulário completo
  - Busca de medicamentos
  - Validações de interações e alergias

#### Aba: Exames
- **Exames Pendentes** (Seção destacada):
  - Lista de exames solicitados aguardando resultado
  - Prioridade (normal, urgente)
  - Data de solicitação
  
- **Exames Concluídos**:
  - Lista de exames com resultados
  - Status: Solicitado, Em andamento, Concluído, Cancelado
  - Download de laudo
  - Visualização de resultados
  
- **Botão "Solicitar Exame"**:
  - Formulário de solicitação
  - Catálogo de exames
  - Campos obrigatórios

#### Aba: Documentos
- **Galeria de Documentos**:
  - Grid de documentos
  - Filtro por categoria
  - Busca por nome
  - Thumbnails para imagens
  
- **Upload de Documentos**:
  - Drag & drop area
  - Seleção múltipla
  - Categorização obrigatória
  - Preview antes de salvar

#### Aba: Evolução
- **Gráficos**:
  - Evolução de peso/IMC (linha)
  - Pressão arterial (linha dupla)
  - Frequência cardíaca (linha)
  - Temperatura (linha)
  
- **Marcadores de Eventos**:
  - Consultas marcadas no gráfico
  - Exames importantes
  - Mudanças de medicação

#### Aba: Anotações
- **Abas Internas**:
  - Privadas (apenas médico)
  - Compartilhadas (visível ao paciente)
  
- **Lista de Anotações**:
  - Cards com título e preview
  - Data e médico
  - Tags
  - Ações: Editar, Excluir, Compartilhar/Tornar privada
  
- **Editor de Anotações**:
  - Rich text editor
  - Templates pré-definidos
  - Tags e categorias

### 3. Interface Durante Consulta em Andamento

#### Rota: `/doctor/consultations/{appointment_id}`

#### Layout Especial
- **Sidebar Esquerda**: Prontuário do paciente (scrollável, compacto)
- **Área Central**: Interface da consulta (vídeo, chat, formulários)
- **Sidebar Direita**: Ações rápidas (prescrição, exame, anotação)

#### Painel de Consulta
- **Informações da Consulta**:
  - Paciente (nome, idade)
  - Data e horário
  - Tempo decorrido
  - Botão "Finalizar Consulta"
  
- **Formulário de Consulta** (Aba ou Seção):
  - **Queixa Principal**: Textarea
  - **Anamnese**: Textarea expandido
  - **Exame Físico**: Campos estruturados
  - **Sinais Vitais**: Formulário rápido
  - **Diagnóstico**: Busca CID-10
  - **Prescrições**: Formulário inline
  - **Exames**: Formulário inline
  - **Orientações**: Textarea
  - **Anotações**: Editor
  
- **Botão "Salvar Rascunho"**: Salvar sem finalizar
- **Botão "Finalizar Consulta"**: Validar e finalizar (bloqueia edição)

---

## 🔄 Fluxo de Interação

### Fluxo 1: Médico Acessa Lista de Pacientes

```
1. Médico faz login
   ↓
2. Navega para "Pacientes" ou "Prontuários" no menu
   ↓
3. Sistema valida acesso (middleware: auth, verified, doctor)
   ↓
4. Controller busca pacientes:
   - Query: Appointments.where('doctor_id', auth()->user()->doctor->id)
   - Agrupa por patient_id
   - Conta número de consultas por paciente
   - Busca última consulta de cada paciente
   ↓
5. Aplica filtros (se houver):
   - Por nome/CPF (busca textual)
   - Por data da última consulta
   - Por diagnóstico
   - Por status
   ↓
6. Ordena resultados conforme seleção
   ↓
7. Pagina resultados (20 por página)
   ↓
8. Frontend renderiza lista (cards ou tabela)
   ↓
9. Médico pode:
   - Buscar paciente específico
   - Filtrar por critérios
   - Clicar em "Ver Prontuário" de um paciente
```

### Fluxo 2: Médico Visualiza Prontuário do Paciente

```
1. Médico clica em "Ver Prontuário" de um paciente
   ↓
2. Sistema valida acesso:
   - Médico autenticado?
   - Médico teve consulta com este paciente?
   - OU há consulta em andamento?
   ↓
3. Se válido:
   - Controller busca dados completos do prontuário
   - Service agrega dados de múltiplas fontes:
     * Dados do paciente (Patient model)
     * Consultas do médico com este paciente
     * Diagnósticos registrados
     * Prescrições emitidas
     * Exames solicitados
     * Documentos anexados
     * Anotações clínicas (privadas e compartilhadas)
     * Sinais vitais históricos
   ↓
4. Retorna dados formatados para frontend via Inertia
   ↓
5. Frontend renderiza página MedicalRecord.vue (visão médico)
   ↓
6. Por padrão, mostra aba "Visão Geral"
   ↓
7. Médico pode:
   - Navegar entre abas
   - Ver histórico completo
   - Registrar novo diagnóstico
   - Emitir prescrição
   - Solicitar exame
   - Anexar documento
   - Criar anotação clínica
```

### Fluxo 3: Médico Registra Diagnóstico Durante Consulta

```
1. Médico está em consulta em andamento
   ↓
2. Clica em "Registrar Diagnóstico"
   ↓
3. Abre modal com formulário:
   - Campo de busca de CID-10 (autocomplete)
   - Lista de diagnósticos recentes do paciente
   - Seleção de tipo (principal/secundário)
   ↓
4. Médico busca e seleciona CID-10
   ↓
5. Sistema valida:
   - CID-10 é válido?
   - Médico tem permissão?
   - Consulta está em andamento ou completada?
   ↓
6. Médico preenche observações (opcional)
   ↓
7. Clica em "Salvar"
   ↓
8. Backend processa:
   - Service registra diagnóstico:
     * Cria registro em diagnoses (ou atualiza appointments.metadata)
     * Associa com appointment_id
     * Associa com doctor_id e patient_id
     * Salva timestamp
   - Registra log de auditoria:
     * action: 'diagnosis_registered'
     * user_id: médico
     * patient_id: paciente
     * appointment_id: consulta
     * metadata: { cid10, type, description }
   ↓
9. Retorna sucesso ao frontend
   ↓
10. Frontend atualiza interface:
    - Adiciona diagnóstico na timeline
    - Atualiza aba de diagnósticos
    - Mostra badge/indicador visual
    - Notifica paciente (se prescrição compartilhada)
```

### Fluxo 4: Médico Emite Prescrição Digital

```
1. Médico acessa prontuário ou está em consulta
   ↓
2. Clica em "Nova Prescrição" ou "Prescrever"
   ↓
3. Abre formulário de prescrição:
   - Busca de medicamento (autocomplete)
   - Campos: Nome, Dose, Frequência, Duração, Instruções
   - Múltiplos medicamentos (lista)
   ↓
4. Médico adiciona medicamento:
   - Digita nome ou busca no catálogo
   - Sistema mostra sugestões
   ↓
5. Sistema valida em tempo real:
   - Medicamento existe no catálogo?
   - Paciente tem alergia a este medicamento? ⚠️
   - Interação com medicações ativas? ⚠️
   ↓
6. Se há alerta:
   - Exibe alerta visual destacado
   - Médico confirma ou altera medicamento
   ↓
7. Médico preenche posologia:
   - Dose: "500mg"
   - Frequência: "8/8 horas"
   - Duração: "7 dias"
   - Instruções especiais: "Tomar após as refeições"
   ↓
8. Adiciona mais medicamentos (se necessário)
   ↓
9. Define validade da prescrição (padrão: 30 dias)
   ↓
10. Clica em "Gerar Prescrição"
    ↓
11. Backend processa:
    - Service cria prescrição:
      * Valida todos os medicamentos
      * Verifica alergias e interações
      * Calcula validade
      * Gera código único de prescrição
    - Gera PDF da prescrição:
      * Template profissional
      * Inclui: Dados do médico (nome, CRM)
      * Inclui: Dados do paciente
      * Inclui: Lista de medicamentos formatada
      * Inclui: Data de emissão e validade
      * Inclui: Assinatura digital (hash do CRM)
    - Assina digitalmente:
      * Hash da prescrição com CRM
      * Armazena signature_hash
    - Cria registro em prescriptions:
      * appointment_id, doctor_id, patient_id
      * medications (JSON), instructions, valid_until
      * status: 'active'
      * pdf_url, signature_hash
    - Registra log de auditoria
    ↓
12. Retorna sucesso com PDF gerado
    ↓
13. Frontend:
    - Mostra preview da prescrição
    - Oferece download do PDF
    - Atualiza lista de prescrições
    - Envia notificação ao paciente:
      * "Nova prescrição disponível"
      * Link para visualizar no app
    ↓
14. Prescrição fica disponível no prontuário do paciente
```

### Fluxo 5: Médico Solicita Exame

```
1. Médico acessa prontuário ou está em consulta
   ↓
2. Clica em "Solicitar Exame"
   ↓
3. Abre formulário:
   - Catálogo de exames (busca por tipo/nome)
   - Seleção múltipla permitida
   ↓
4. Médico seleciona exames:
   - Busca por nome: "Hemograma completo"
   - Ou por categoria: Laboratoriais, Imagem, Outros
   ↓
5. Para cada exame selecionado:
   - Justificativa clínica (obrigatório)
   - Prioridade (normal/urgente)
   - Instruções pré-exame (opcional)
   - Data sugerida (opcional)
   ↓
6. Clica em "Solicitar"
   ↓
7. Backend processa:
    - Service cria solicitações de exame:
      * Para cada exame, cria registro em examinations
      * status: 'requested'
      * Associa com appointment_id, doctor_id, patient_id
    - Registra log de auditoria
    ↓
8. Retorna sucesso
    ↓
9. Frontend:
    - Atualiza lista de exames
    - Mostra exames pendentes
    - Envia notificação ao paciente:
      * "Novos exames solicitados"
      * Lista de exames
      * Instruções pré-exame (se houver)
    ↓
10. Exames aparecem no prontuário do paciente
    ↓
11. (Futuro) Integração com laboratório:
    - Exames aparecem no sistema do laboratório
    - Quando resultado estiver pronto, é importado automaticamente
    - Notificação ao médico e paciente
```

### Fluxo 6: Médico Faz Upload de Documento

```
1. Médico acessa aba "Documentos" do prontuário
   ↓
2. Clica em "Anexar Documento" ou arrasta arquivo
   ↓
3. Seleciona arquivo(s):
   - Tipos permitidos: PDF, JPG, PNG, DOC, DOCX
   - Tamanho máximo: 10MB por arquivo
   ↓
4. Frontend valida:
   - Tipo de arquivo permitido?
   - Tamanho dentro do limite?
   ↓
5. Mostra preview do arquivo
   ↓
6. Médico preenche metadados:
   - Categoria: (Laudo, Exame, Relatório, Imagem, Outro)
   - Descrição (opcional)
   - Data do documento
   - Associar com consulta específica (opcional)
   ↓
7. Clica em "Salvar"
   ↓
8. Frontend faz upload:
   - Envia arquivo para backend (multipart/form-data)
   - Mostra barra de progresso
   ↓
9. Backend processa:
    - Service valida arquivo:
      * Tipo e tamanho
      * Scan de vírus (se disponível)
    - Faz upload para storage (S3 ou local):
      * Path: medical-documents/{patient_id}/{uuid}.{ext}
    - Cria registro em medical_documents:
      * patient_id, doctor_id, appointment_id (se associado)
      * category, name, file_path, file_type, file_size
      * uploaded_by: user_id do médico
    - Registra log de auditoria
    ↓
10. Retorna sucesso
    ↓
11. Frontend:
    - Adiciona documento na galeria
    - Mostra thumbnail/preview
    - Atualiza contador de documentos
    - Notifica paciente (se categoria permitir visibilidade)
```

### Fluxo 7: Médico Registra Anotação Clínica

```
1. Médico acessa aba "Anotações" do prontuário
   ↓
2. Clica em "Nova Anotação"
   ↓
3. Abre editor de anotações:
   - Título (obrigatório)
   - Conteúdo (rich text editor)
   - Checkbox "Anotação privada" (default: true)
   - Categoria (dropdown)
   - Tags (input com autocomplete)
   - Associar com consulta (opcional)
   ↓
4. Médico escreve anotação:
   - Pode usar templates pré-definidos
   - Formatação de texto (negrito, itálico, listas)
   - Pode inserir links
   ↓
5. Define visibilidade:
   - Privada: apenas médico vê
   - Compartilhada: paciente também vê
   ↓
6. Clica em "Salvar"
   ↓
7. Backend processa:
    - Service cria anotação:
      * Cria registro em clinical_notes
      * is_private: conforme seleção
      * version: 1 (primeira versão)
      * Associa com appointment_id (se houver), doctor_id, patient_id
    - Registra log de auditoria
    ↓
8. Retorna sucesso
    ↓
9. Frontend:
    - Adiciona anotação na lista
    - Mostra badge de privacidade
    - Atualiza contador
    - Se compartilhada, notifica paciente
```

### Fluxo 8: Médico Finaliza Consulta

```
1. Médico está em consulta em andamento
   ↓
2. Preencheu todos os dados necessários:
   - Diagnóstico (obrigatório)
   - Queixa principal e anamnese
   - (Opcional) Prescrições
   - (Opcional) Exames solicitados
   - (Opcional) Anotações
   ↓
3. Clica em "Finalizar Consulta"
   ↓
4. Sistema valida:
   - Diagnóstico foi registrado?
   - Consulta está em status 'in_progress'?
   - Médico é o responsável pela consulta?
   ↓
5. Se validação OK:
   - Mostra modal de confirmação:
     * "Tem certeza que deseja finalizar a consulta?"
     * "Após finalizar, os dados críticos não poderão ser editados."
     * Checkbox: "Gerar PDF da consulta automaticamente"
   ↓
6. Médico confirma
   ↓
7. Backend processa:
    - Service finaliza consulta:
      * Atualiza appointments:
        - status: 'completed'
        - ended_at: now()
        - notes: consolida todas as informações
      * Bloqueia edição de dados críticos
    - Gera PDF da consulta (se solicitado):
      * Template de relatório de consulta
      * Inclui todos os dados registrados
      * Assinatura digital
      * Armazena em storage
    - Registra log de auditoria:
      * action: 'consultation_completed'
      * duration: calcula diferença entre started_at e ended_at
    - Notifica paciente:
      * "Sua consulta foi finalizada"
      * "Acesse seu prontuário para ver os detalhes"
      * Link para prontuário
    ↓
8. Retorna sucesso
    ↓
9. Frontend:
    - Redireciona para prontuário do paciente
    - Ou mostra mensagem de sucesso
    - Consulta aparece no histórico como "Finalizada"
    - Dados críticos ficam bloqueados para edição
```

---

## 🔗 Integrações com Outros Módulos

### 1. Módulo de Consultas (Appointments)

#### Relacionamento
- **Tipo**: Prontuário médico consome e produz dados de Appointments
- **Direção**: Bidirecional (Appointments ↔ Medical Records)

#### Dados Consumidos
- Consultas do médico com o paciente
- Status da consulta (para validação de edição)
- Metadados da consulta (diagnóstico, sintomas, etc.)
- Timestamps (scheduled_at, started_at, ended_at)

#### Dados Produzidos
- Diagnósticos registrados durante consulta
- Prescrições emitidas
- Exames solicitados
- Documentos anexados
- Anotações clínicas
- Sinais vitais registrados
- Atualização de `appointments.metadata`

#### Impacto
- Quando consulta é finalizada, todos os dados registrados aparecem no prontuário
- Prontuário pode ser acessado durante consulta em andamento
- Dados do prontuário são usados para preencher contexto da consulta

#### Arquivos Relacionados
- `app/Models/Appointments.php`
- `app/Services/AppointmentService.php`
- `app/Http/Controllers/AppointmentsController.php`

### 2. Módulo de Pacientes (Patients)

#### Relacionamento
- **Tipo**: Prontuário médico visualiza e complementa dados de Patient
- **Direção**: Patient → Medical Records (leitura) e Medical Records → Patient (atualização limitada)

#### Dados Utilizados (Leitura)
- Informações pessoais completas
- Histórico médico (`medical_history`)
- Alergias (`allergies`)
- Medicações atuais (`current_medications`)
- Tipo sanguíneo, altura, peso
- Dados demográficos

#### Dados Atualizados (Escrita Limitada)
- `last_consultation_at` (atualizado quando consulta é finalizada)
- Sinais vitais (altura, peso podem ser atualizados)
- Alguns campos podem ser complementados pelo médico (com auditoria)

#### Impacto
- Alterações no prontuário podem refletir em dados do paciente
- Dados do paciente são a base do prontuário

#### Arquivos Relacionados
- `app/Models/Patient.php`
- `app/MedicalRecord/Application/Services/MedicalRecordService.php`

### 3. Módulo de Médicos (Doctors)

#### Relacionamento
- **Tipo**: Médico é o ator principal que interage com prontuário
- **Direção**: Doctor → Medical Records (médico acessa e edita prontuário)

#### Dados Utilizados
- Informações do médico autenticado
- CRM (para assinaturas digitais)
- Especialidades (para contexto e filtros)

#### Impacto
- Todas as ações no prontuário são associadas ao médico
- Assinaturas digitais usam CRM do médico
- Filtros e permissões baseados no médico

#### Arquivos Relacionados
- `app/Models/Doctor.php`
- `app/Policies/MedicalRecordPolicy.php`

### 4. Módulo de Prescrições (Futuro)

#### Relacionamento Previsto
- **Tipo**: Medical Records produz prescrições
- **Direção**: Medical Records → Prescriptions

#### Dados Produzidos
- Prescrições digitais emitidas
- Histórico de medicamentos prescritos
- Validação de interações medicamentosas

#### Integração
- API externa de medicamentos (se disponível)
- Sistema de validação de interações
- Integração com farmácias (futuro)

### 5. Módulo de Exames (Futuro)

#### Relacionamento Previsto
- **Tipo**: Medical Records produz solicitações de exame
- **Direção**: Medical Records → Examinations

#### Dados Produzidos
- Solicitações de exames
- Resultados de exames (quando disponíveis)
- Laudos e anexos

#### Integração Futura
- Integração com laboratórios (importação automática de resultados)
- Catálogo de exames disponíveis
- Sistema de aprovação (seguradoras)

### 6. Módulo de Chat/Mensagens

#### Relacionamento Previsto
- **Tipo**: Integração para comunicação durante consulta
- **Direção**: Bidirecional

#### Funcionalidades
- Chat em tempo real durante consulta
- Mensagens sobre prontuário (exames prontos, prescrições)
- Notificações de ações importantes

### 7. Módulo de Notificações

#### Relacionamento Previsto
- **Tipo**: Medical Records produz notificações
- **Direção**: Medical Records → Notifications

#### Notificações Geradas
- Prescrição emitida → notifica paciente
- Exame solicitado → notifica paciente
- Resultado de exame disponível → notifica médico e paciente
- Documento anexado → notifica paciente (se aplicável)
- Consulta finalizada → notifica paciente

### 8. Módulo de Arquivos/Storage

#### Relacionamento
- **Tipo**: Medical Records consome storage para documentos
- **Direção**: Medical Records → Storage

#### Uso
- Upload de documentos médicos
- Armazenamento de PDFs gerados (prescrições, atestados, relatórios)
- Imagens e laudos
- Gravações de consulta (se houver)

#### Configuração
- Storage local ou S3
- Políticas de retenção
- Compressão de imagens
- Backup automático

---

## 🔐 Permissões de Acesso

### Controle de Acesso Baseado em Relacionamento

#### Regra Principal
**Médico só pode acessar prontuário de pacientes que tiveram consultas com ele.**

#### Validação de Acesso
```php
// Exemplo de validação no Policy
public function view(User $user, Patient $patient): bool
{
    if (!$user->isDoctor()) {
        return false;
    }
    
    $doctor = $user->doctor;
    
    // Médico pode ver se teve consulta com o paciente
    $hasAppointment = Appointments::where('doctor_id', $doctor->id)
        ->where('patient_id', $patient->id)
        ->exists();
    
    // OU se há consulta em andamento
    $hasActiveAppointment = Appointments::where('doctor_id', $doctor->id)
        ->where('patient_id', $patient->id)
        ->where('status', Appointments::STATUS_IN_PROGRESS)
        ->exists();
    
    return $hasAppointment || $hasActiveAppointment;
}
```

### Permissões Específicas por Ação

#### Visualizar Prontuário
- **Permissão**: `MedicalRecordPolicy::view()`
- **Validação**: Relacionamento médico-paciente via consultas
- **Exceções**: Consulta em andamento permite acesso mesmo sem histórico

#### Editar Dados Clínicos
- **Permissão**: `MedicalRecordPolicy::update()`
- **Validação**: 
  - Consulta deve estar `in_progress` ou `completed`
  - Médico deve ser o responsável pela consulta
  - Dados críticos só podem ser editados durante consulta

#### Registrar Diagnóstico
- **Permissão**: `MedicalRecordPolicy::registerDiagnosis()`
- **Validação**:
  - Consulta em andamento ou completada
  - Médico responsável pela consulta
  - CID-10 válido

#### Emitir Prescrição
- **Permissão**: `MedicalRecordPolicy::issuePrescription()`
- **Validação**:
  - Médico autenticado e ativo
  - CRM válido
  - Paciente do médico

#### Solicitar Exame
- **Permissão**: `MedicalRecordPolicy::requestExamination()`
- **Validação**:
  - Médico autenticado
  - Justificativa clínica fornecida

#### Upload de Documento
- **Permissão**: `MedicalRecordPolicy::uploadDocument()`
- **Validação**:
  - Médico autenticado
  - Relacionamento com paciente
  - Tipo e tamanho de arquivo válidos

#### Criar Anotação Clínica
- **Permissão**: `MedicalRecordPolicy::createNote()`
- **Validação**:
  - Médico autenticado
  - Relacionamento com paciente

#### Emitir Atestado
- **Permissão**: `MedicalRecordPolicy::issueCertificate()`
- **Validação**:
  - Médico autenticado
  - CRM válido
  - Consulta em andamento ou recente

### Hierarquia de Permissões

1. **Paciente**: Visualiza apenas seu próprio prontuário
2. **Médico**: Visualiza e edita prontuários de seus pacientes
3. **Administrador**: Acesso total (com auditoria)

### Middleware de Proteção

```php
// routes/web.php
Route::middleware(['auth', 'verified', 'doctor'])->prefix('doctor')->group(function () {
    Route::get('patient/{patient}/medical-record', [DoctorPatientMedicalRecordController::class, 'index'])
        ->middleware('can:view,App\Models\Patient');
    
    Route::post('patient/{patient}/diagnosis', [DoctorPatientMedicalRecordController::class, 'storeDiagnosis'])
        ->middleware('can:registerDiagnosis,App\Models\Patient');
    
    // ... outras rotas
});
```

---

## 👥 Relação com a Visão do Paciente

### Dados que o Médico Vê que o Paciente Não Vê

#### 1. Anotações Privadas
- **Médico**: Vê todas as anotações (públicas e privadas)
- **Paciente**: Vê apenas anotações marcadas como "compartilhadas"
- **Implementação**: Campo `is_private` em `clinical_notes`

#### 2. Consultas de Outros Médicos
- **Médico**: Vê apenas suas próprias consultas
- **Paciente**: Vê todas as consultas que teve
- **Implementação**: Filtro por `doctor_id` na visão do médico

#### 3. Detalhes Técnicos
- **Médico**: Vê detalhes técnicos completos (códigos internos, metadados)
- **Paciente**: Vê versão simplificada e amigável

#### 4. Histórico de Edições
- **Médico**: Pode ver histórico de versões de anotações e documentos
- **Paciente**: Vê apenas versão atual

#### 5. Dados de Auditoria
- **Médico**: Pode ver logs de acesso ao prontuário (futuro)
- **Paciente**: Não tem acesso a logs

### Dados que o Paciente Vê que o Médico Também Vê

#### 1. Dados Pessoais
- ✅ Ambos veem: Nome, idade, gênero, data de nascimento
- ✅ Ambos veem: Alergias conhecidas
- ✅ Ambos veem: Medicações atuais

#### 2. Consultas
- ✅ Ambos veem: Consultas completadas
- ⚠️ Diferença: Médico vê apenas suas consultas; Paciente vê todas

#### 3. Diagnósticos
- ✅ Ambos veem: Diagnósticos registrados e CID-10

#### 4. Prescrições
- ✅ Ambos veem: Prescrições ativas e histórico
- ✅ Ambos veem: PDF das prescrições

#### 5. Exames
- ✅ Ambos veem: Exames solicitados e resultados

#### 6. Documentos Compartilhados
- ✅ Ambos veem: Documentos não marcados como privados

### Ações que o Médico Toma que Refletem na Visão do Paciente

#### 1. Registrar Diagnóstico
- **Ação do Médico**: Registra diagnóstico durante consulta
- **Reflexo no Paciente**: 
  - Diagnóstico aparece no prontuário do paciente
  - Notificação: "Novo diagnóstico registrado"
  - Aparece na timeline e aba de diagnósticos

#### 2. Emitir Prescrição
- **Ação do Médico**: Emite prescrição digital
- **Reflexo no Paciente**:
  - Prescrição aparece no prontuário
  - Notificação: "Nova prescrição disponível"
  - PDF disponível para download
  - Aparece na aba de prescrições

#### 3. Solicitar Exame
- **Ação do Médico**: Solicita exame
- **Reflexo no Paciente**:
  - Exame aparece na lista de exames solicitados
  - Notificação: "Novos exames solicitados"
  - Instruções pré-exame enviadas

#### 4. Anexar Documento
- **Ação do Médico**: Anexa documento (laudo, resultado)
- **Reflexo no Paciente**:
  - Documento aparece na galeria (se não privado)
  - Notificação: "Novo documento disponível"
  - Disponível para download

#### 5. Criar Anotação Compartilhada
- **Ação do Médico**: Cria anotação e marca como "compartilhada"
- **Reflexo no Paciente**:
  - Anotação aparece no prontuário do paciente
  - Notificação: "Nova anotação do médico"
  - Visível na aba de anotações

#### 6. Finalizar Consulta
- **Ação do Médico**: Finaliza consulta
- **Reflexo no Paciente**:
  - Consulta aparece como "Finalizada" no histórico
  - Notificação: "Consulta finalizada - Acesse o prontuário"
  - Todos os dados registrados ficam visíveis

### Como a Privacidade é Garantida

#### 1. Anotações Privadas
- **Campo**: `is_private = true`
- **Visão Médico**: Vê todas as anotações
- **Visão Paciente**: Filtro automático `WHERE is_private = false`
- **Implementação**: Query condicional baseada no role do usuário

#### 2. Filtro de Consultas
- **Médico**: Query filtra por `doctor_id`
- **Paciente**: Query filtra por `patient_id`
- **Implementação**: Scopes diferentes no Model

#### 3. Campos Sensíveis
- **Criptografia**: Dados sensíveis criptografados em repouso
- **Acesso Logado**: Logs de acesso para auditoria
- **LGPD**: Compliance com regulamentações de proteção de dados

### Sincronização entre Visões

#### 1. Frontend
- **Componentes Compartilhados**: Alguns componentes Vue são reutilizados
- **Props Condicionais**: Visibilidade controlada por props (`isDoctorView`)
- **Exemplo**:
  ```vue
  <ClinicalNotes 
    :notes="notes" 
    :showPrivate="isDoctorView" 
  />
  ```

#### 2. Backend
- **Service Único**: `MedicalRecordService` serve ambas as visões
- **Métodos Específicos**: 
  - `getPatientMedicalRecord()` - Visão paciente
  - `getDoctorPatientMedicalRecord()` - Visão médico
- **Formatação Condicional**: Resource classes formatam dados conforme role

#### 3. Consistência de Dados
- **Single Source of Truth**: Dados armazenados uma vez no banco
- **Queries Filtradas**: Filtros aplicados conforme role
- **Cache Separado**: Cache diferente para médico e paciente (evita vazamento)

### Diagrama de Relacionamento

```
┌─────────────────────────────────────────────────────────┐
│                    PRONTUÁRIO MÉDICO                     │
│                  (Single Source of Truth)                │
└─────────────────────────────────────────────────────────┘
                          │
                          │
        ┌─────────────────┴─────────────────┐
        │                                   │
        ▼                                   ▼
┌──────────────────┐              ┌──────────────────┐
│  VISÃO MÉDICO    │              │  VISÃO PACIENTE  │
│                  │              │                  │
│ • Anotações      │              │ • Anotações      │
│   Privadas       │              │   Compartilhadas │
│ • Suas           │              │ • Todas as       │
│   Consultas      │              │   Consultas      │
│ • Dados          │              │ • Dados          │
│   Completos      │              │   Pessoais       │
│                  │              │                  │
│ [PODE EDITAR]    │              │ [SOMENTE LEITURA]│
└──────────────────┘              └──────────────────┘
        │                                   │
        │                                   │
        ▼                                   ▼
┌─────────────────────────────────────────────────────────┐
│              AÇÕES DO MÉDICO REFLETEM NO PACIENTE       │
│  • Diagnóstico → Aparece no prontuário do paciente      │
│  • Prescrição → Notificação + PDF para paciente         │
│  • Exame → Solicitação visível ao paciente              │
│  • Documento → Disponível para download                 │
└─────────────────────────────────────────────────────────┘
```

---

## 📝 Estrutura de Dados

### Modelos Principais

#### 1. Patient (Já Existe)
- **Tabela**: `patients`
- **Relacionamentos**: `hasMany(Appointments)`, `hasMany(Prescriptions)`, etc.

#### 2. Appointments (Já Existe)
- **Tabela**: `appointments`
- **Campo Relevante**: `metadata` (JSON) - armazena diagnóstico, sintomas, etc.
- **Relacionamentos**: `belongsTo(Doctor)`, `belongsTo(Patient)`

#### 3. Prescription (✅ Implementado)
- **Tabela**: `prescriptions`
- **Campos Principais**:
  ```sql
  id UUID PRIMARY KEY
  appointment_id UUID FK (nullable)
  doctor_id UUID FK
  patient_id UUID FK
  medications JSON -- Array de medicamentos
  instructions TEXT
  valid_until DATE
  status ENUM('active', 'expired', 'cancelled', 'completed')
  signature_hash STRING
  crm_number STRING
  pdf_url STRING
  metadata JSON
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP (soft delete)
  ```

#### 4. Examination (✅ Implementado)
- **Tabela**: `examinations`
- **Campos Principais**:
  ```sql
  id UUID PRIMARY KEY
  appointment_id UUID FK (nullable)
  doctor_id UUID FK
  patient_id UUID FK
  type ENUM('lab', 'image', 'other')
  name STRING
  description TEXT
  justification TEXT -- Obrigatório
  priority ENUM('normal', 'urgent')
  instructions TEXT
  requested_at DATE
  completed_at DATE (nullable)
  results JSON/TEXT (nullable)
  attachment_url STRING (nullable)
  status ENUM('requested', 'approved', 'in_progress', 'completed', 'cancelled')
  metadata JSON
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP (soft delete)
  ```

#### 5. ClinicalNote (✅ Implementado)
- **Tabela**: `clinical_notes`
- **Campos Principais**:
  ```sql
  id UUID PRIMARY KEY
  appointment_id UUID FK (nullable)
  doctor_id UUID FK
  patient_id UUID FK
  title STRING
  content TEXT -- Rich text
  is_private BOOLEAN DEFAULT true
  category ENUM('general', 'diagnosis', 'treatment', 'follow_up', 'other')
  tags JSON (nullable)
  version INTEGER DEFAULT 1
  parent_id UUID FK (nullable) -- Para histórico
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP (soft delete)
  ```

#### 6. MedicalCertificate (✅ Implementado)
- **Tabela**: `medical_certificates`
- **Campos Principais**:
  ```sql
  id UUID PRIMARY KEY
  appointment_id UUID FK (nullable)
  doctor_id UUID FK
  patient_id UUID FK
  type ENUM('absence', 'attendance', 'disability', 'other')
  start_date DATE
  end_date DATE (nullable)
  days INTEGER
  reason TEXT
  restrictions TEXT (nullable)
  signature_hash STRING
  crm_number STRING
  verification_code STRING UNIQUE
  pdf_url STRING
  status ENUM('active', 'expired', 'cancelled')
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP (soft delete)
  ```

#### 7. VitalSign (✅ Implementado)
- **Tabela**: `vital_signs`
- **Campos Principais**:
  ```sql
  id UUID PRIMARY KEY
  appointment_id UUID FK
  patient_id UUID FK
  doctor_id UUID FK
  blood_pressure_systolic INTEGER (nullable)
  blood_pressure_diastolic INTEGER (nullable)
  temperature DECIMAL(3,1) (nullable)
  heart_rate INTEGER (nullable)
  respiratory_rate INTEGER (nullable)
  oxygen_saturation INTEGER (nullable) -- SpO2 %
  weight DECIMAL(5,2) (nullable) -- kg
  height DECIMAL(5,2) (nullable) -- cm
  bmi DECIMAL(4,2) (nullable) -- Calculado
  notes TEXT (nullable)
  recorded_at DATETIME
  created_at TIMESTAMP
  updated_at TIMESTAMP
  ```

#### 8. MedicalDocument (✅ Implementado)
- **Tabela**: `medical_documents`
- **Campos Principais**:
  ```sql
  id UUID PRIMARY KEY
  patient_id UUID FK
  appointment_id UUID FK (nullable)
  doctor_id UUID FK (nullable)
  category ENUM('exam', 'prescription', 'report', 'image', 'other')
  name STRING
  file_path STRING
  file_type STRING
  file_size INTEGER -- bytes
  uploaded_by UUID FK -- user_id
  description TEXT (nullable)
  is_private BOOLEAN DEFAULT false
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP (soft delete)
  ```

#### 9. Diagnosis (✅ Implementado)
- **Tabela**: `diagnoses` (tabela separada implementada)
- **Campos (se tabela separada)**:
  ```sql
  id UUID PRIMARY KEY
  appointment_id UUID FK
  doctor_id UUID FK
  patient_id UUID FK
  cid10_code STRING
  cid10_description STRING
  diagnosis_type ENUM('principal', 'secondary')
  description TEXT (nullable)
  created_at TIMESTAMP
  updat