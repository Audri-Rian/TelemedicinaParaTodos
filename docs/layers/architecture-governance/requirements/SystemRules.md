# 📜 Regras do Sistema

## ✅ Regras Vigentes (Março/2026)

> Esta seção consolida as regras efetivamente implementadas no código atual.  
> O conteúdo anterior permanece como histórico e referência evolutiva.

### Matriz de Regras Executáveis (Código como Fonte)

| RN | Regra | Situação | Evidências |
|---|---|---|---|
| RN001 | Agendamento somente com médico ativo | ✅ | `AppointmentService::validateDoctorActive`, `Doctor::STATUS_ACTIVE` |
| RN002 | Paciente precisa cadastro completo para agendar | ✅ | `AppointmentService::validatePatientComplete` |
| RN003 | Conflito de horário em consultas | ✅ | `AppointmentService::validateNoConflict` |
| RN004 | Transições controladas de status | ✅ | `AppointmentService::validateStatusTransition` |
| RN005 | Janela de início da consulta | ✅ | `telemedicine.appointment.lead_minutes`, `DoctorConsultationDetailController` |
| RN006 | Janela de cancelamento/reagendamento | ✅ | `telemedicine.appointment.cancel_before_hours`, `AppointmentService` |
| RN007 | Somente participantes acessam consulta | ✅ | `AppointmentPolicy` |
| RN008 | Restrição de acesso e edição do prontuário | ✅ | `MedicalRecordPolicy`, `MedicalRecordService` |
| RN009 | Emissão clínica condicionada a CRM | ✅ | `MedicalRecordPolicy`, validações de médico |
| RN010 | Notificações com limites anti-spam | ✅ | `NotificationService`, `DebounceNotifications` |
| RN011 | Regras de agenda e disponibilidade por médico | ✅ | `ScheduleService`, `AvailabilityService`, requests de agenda |
| RN012 | Timeline por propriedade do evento | ✅ | `TimelineEventPolicy` |
| RN013 | Lembretes automáticos de consulta | ✅ | `SendAppointmentReminders`, `routes/console.php` |
| RN014 | Configuração central de parâmetros de domínio | ✅ | `config/telemedicine.php` |
| RN015 | Pagamentos eletrônicos | 📋 Planejado | Sem artefato implementado no backend atual |
| RN016 | Fluxo completo de chamada de vídeo por rotas de produção | 🔄 Parcial | Base SFU existe; rotas públicas específicas ainda parciais |

### Regras por Módulo (Atualizado)

#### Usuários (Users, Doctors, Patients)
- Registro separado de médico e paciente.
- Email único por usuário.
- CRM único e alfanumérico em caixa alta.
- Paciente registra dados básicos e pode complementar dados clínicos.
- Consulta depende de médico ativo e paciente completo.

#### Consultas (Appointments)
- Estados suportados: `scheduled`, `in_progress`, `completed`, `no_show`, `cancelled`, `rescheduled`.
- Conflitos de agenda validados antes de criar/atualizar/reagendar.
- Início, cancelamento e reagendamento obedecem janelas configuráveis em `config/telemedicine.php`.
- Logs de eventos da consulta registrados com `AppointmentLog` e observers.

#### Prontuário e Documentos Clínicos
- Módulo clínico implementado (diagnóstico, prescrição, exame, nota clínica, atestado, sinais vitais, documento).
- Acesso controlado por `MedicalRecordPolicy` com base em vínculo do atendimento.
- Exportação de documentos clínicos/PDF suportada.
- Auditoria de ações clínicas registrada.

#### Agenda e Disponibilidade
- Médico mantém locais de atendimento, slots recorrentes/específicos e bloqueio de datas.
- Geração/consulta de disponibilidade com janela configurável.
- Duração mínima de slot e limites são configuráveis.

#### Comunicação (Mensagens e Notificações)
- Mensageria interna via endpoints `/api/messages/*`.
- Notificações in-app e envio de e-mail para eventos relevantes.
- Debounce e limites de paginação para reduzir ruído/perda de performance.

#### LGPD
- Consentimento, portabilidade, relatório de acesso e solicitação de esquecimento estão expostos por rotas dedicadas.
- Controle por autenticação e limites de taxa em operações sensíveis.

#### Videoconferência
- Base técnica SFU com entidades `Call` e `Room` e eventos de vídeo.
- Fluxo ainda em evolução para fechamento de todo ciclo de chamada nas rotas de produção.

### Decisões de Governança Registradas

- Regras variáveis de negócio centralizadas em `config/telemedicine.php`.
- Controle de acesso em profundidade: middleware + policy + service.
- Documentação deve usar “implementado/parcial/planejado” com evidência de arquivo.
- Divergência código-doc deve ser resolvida priorizando o código.

---

> **Implementação técnica das regras**  
> Os parâmetros técnicos destas regras (janelas de agendamento, durações padrão, limites de histórico, lembretes, etc.) são configuráveis no backend via `config/telemedicine.php`.  
> A rastreabilidade entre regras de negócio e configurações está documentada em `docs/Tasks/TASK_11_MIGRACAO_CONFIG_TELEMEDICINE.md`.

## 🎯 Objetivo
Esse projeto tem como objetivo de criar uma platarforma de Telemedicina Moderna, segura e acessível desenvolvida com Laravel(PHP). Ele conecta médicos e pacientes de forma remota, oferecendo consultas online, agendamento inteligente, prontuários digitais e comunicação segura tudo em um único sistema integrado.

# 🏥 Regras de Negócio 

### Módulo Usuários e Informações

#### 👥 USERS (Usuários Base)
- **Tabela central** de autenticação (polimórfica: médico OU paciente)
- **Email único** e obrigatório, verificação obrigatória
- **Senha segura** (mínimo 8 caracteres, maiúsculas, números)
- **Status**: ativo, inativo, suspenso, bloqueado
- **Soft delete** para auditoria completa

#### 👨‍⚕️ DOCTORS (Médicos)
- **Extensão de USERS** com relacionamento 1:1
- **CRM obrigatório** e único por estado/região
- **Especialidade principal** obrigatória
- **Controle de agenda** e disponibilidade para consultas
- **Apenas ativos** podem receber agendamentos

#### 👤 PATIENTS (Pacientes)
##### Alguns dados do patient não são obrigatorios no inicio
- **Extensão de USERS** com relacionamento 1:1
- **Data de nascimento** obrigatória para cálculos médicos
- **Contato de emergência**  Obrigatorio apos a primeira etapa de autenticação.
- **Consentimento explícito** para telemedicina, não precisa no register incial
- **Histórico médico** para diagnósticos precisos, não precisa no register incial

#### 🔗 Relacionamentos
- **USERS** é a entidade base obrigatória
- **DOCTORS/PATIENTS** dependem de USERS existentes
- **Exclusão em cascata** com soft delete para auditoria
- **Apenas entidades ativas** podem se relacionar

#### 🛡️ Segurança e Compliance
- **Criptografia** de dados sensíveis (histórico médico)
- **Logs de auditoria** para todas as ações médicas
- **Controle de acesso** baseado em roles
- **Compliance LGPD** e regulamentações médicas
- **Backup diário** com logs de auditoria

---

### Módulo de Agenda e Disponibilidade

#### 📅 SERVICE_LOCATIONS (Locais de Atendimento)
- **Múltiplos locais** por médico
- **Tipos**: teleconsultation, office, hospital, clinic
- **Status ativo/inativo** para controle
- **Teleconsulta obrigatória**: Todo médico deve ter pelo menos um local do tipo `teleconsultation`
- **Endereço físico** obrigatório para tipos presenciais (office, hospital, clinic)
- **Soft delete** para histórico

#### ⏰ AVAILABILITY_SLOTS (Slots de Disponibilidade)
- **Dois tipos**:
  - `recurring` - Recorrente (toda segunda-feira, por exemplo)
  - `specific` - Data específica
- **Horário obrigatório**: start_time e end_time
- **Local opcional**: Pode estar vinculado a um ServiceLocation ou ser geral
- **Status ativo/inativo** para controle
- **Validação de conflitos**: Não pode haver sobreposição de horários no mesmo local
- **Soft delete** para histórico

#### 🚫 BLOCKED_DATES (Datas Bloqueadas)
- **Data obrigatória**: blocked_date
- **Motivo opcional**: reason
- **Bloqueio total**: Quando uma data está bloqueada, nenhum slot funciona nessa data
- **Validação**: Não pode bloquear datas passadas
- **Soft delete** para histórico

#### 📋 Regras de Agenda
- **Agenda obrigatória**: Médico deve configurar pelo menos um slot de disponibilidade
- **Disponibilidade padrão**: Sistema pode criar disponibilidade padrão se médico não configurar
- **Validação de agendamento**: Consultas só podem ser agendadas em slots ativos e não bloqueados
- **Conflito de horários**: Sistema valida conflitos antes de criar slots

---

### Módulo de Prontuários Médicos

#### 💊 PRESCRIPTIONS (Prescrições)
- **Vinculação obrigatória**: Deve estar vinculada a um Appointment, Doctor e Patient
- **Medicamentos em JSON**: Array estruturado com nome, dosagem, frequência
- **Validade**: Campo valid_until opcional
- **Status**: active, expired, cancelled
- **Data de emissão**: issued_at registrado automaticamente
- **Soft delete** para histórico

#### 🩺 DIAGNOSES (Diagnósticos)
- **CID-10 obrigatório**: cid10_code deve ser válido
- **Tipo**: principal ou secondary
- **Vinculação**: Appointment, Doctor e Patient obrigatórios
- **Descrição opcional**: Campo description para detalhes
- **Soft delete** para histórico

#### 🔬 EXAMINATIONS (Exames)
- **Tipos**: lab, image, other
- **Status**: requested, in_progress, completed, cancelled
- **Resultados em JSON**: Campo results estruturado
- **Anexos**: attachment_url para laudos e imagens
- **Datas**: requested_at e completed_at para rastreamento
- **Soft delete** para histórico

#### 📝 CLINICAL_NOTES (Anotações Clínicas)
- **Privacidade**: Campo is_private (true = apenas médico, false = paciente vê)
- **Categorização**: Campo category para organização
- **Tags**: Campo tags em JSON para busca
- **Versionamento**: Campo version e parent_id para histórico de edições
- **Vinculação**: Appointment opcional, Doctor e Patient obrigatórios
- **Soft delete** para histórico

#### 📜 MEDICAL_CERTIFICATES (Atestados)
- **Código único**: verification_code único e obrigatório
- **Período**: start_date obrigatório, end_date opcional
- **Dias calculados**: Campo days calculado automaticamente
- **Assinatura digital**: signature_hash para validação
- **PDF gerado**: pdf_url após geração
- **Status**: active, expired, cancelled
- **Soft delete** para histórico

#### 💓 VITAL_SIGNS (Sinais Vitais)
- **Registro automático**: recorded_at com timestamp
- **Campos opcionais**: Todos os sinais são opcionais
- **Vinculação**: Appointment opcional, Patient obrigatório, Doctor opcional
- **Sem soft delete**: Registros históricos permanecem

#### 📎 MEDICAL_DOCUMENTS (Documentos Médicos)
- **Categorias**: exam, prescription, report, other
- **Visibilidade**: patient, doctor, shared
- **Upload**: uploaded_by registra quem fez upload
- **Metadados**: file_type, file_size, description
- **Soft delete** para histórico

#### 📊 MEDICAL_RECORD_AUDIT_LOGS (Logs de Auditoria)
- **Rastreabilidade completa**: Todas as ações em prontuários são registradas
- **Campos obrigatórios**: action, patient_id
- **Metadados**: resource_type, resource_id, ip_address, user_agent
- **Compliance LGPD**: Logs não podem ser excluídos
- **Sem soft delete**: Logs permanecem para auditoria

#### 📋 Regras de Prontuário
- **Acesso restrito**: Apenas médicos que atenderam o paciente podem editar prontuário
- **Visualização paciente**: Paciente vê apenas itens não privados
- **Auditoria obrigatória**: Todas as ações geram log de auditoria
- **Exportação**: Paciente e médico podem exportar prontuário completo em PDF
- **Integridade**: Dados não podem ser excluídos fisicamente (soft delete)

---

### Módulo de Videoconferência

#### 🏠 VIDEO_CALL_ROOMS (Salas de Videoconferência)
- **Criação automática**: Salas criadas automaticamente para consultas
- **Expiração**: Salas expiram automaticamente após período configurado
- **Vinculação**: Relacionadas com Appointments
- **Jobs automáticos**: ExpireVideoCallRooms executa limpeza periódica

#### 📹 VIDEO_CALL_EVENTS (Eventos de Videoconferência)
- **Rastreamento**: Todos os eventos de videoconferência são registrados
- **Limpeza automática**: CleanupOldVideoCallEvents remove eventos antigos
- **Integração**: UpdateAppointmentFromRoom atualiza consulta a partir da sala

#### 📋 Regras de Videoconferência
- **Acesso restrito**: Apenas médico e paciente da consulta podem acessar
- **Expiração automática**: Salas expiram após término da consulta
- **Eventos rastreados**: Entrada, saída e ações são registradas

---

### Módulo de Timeline

#### 📅 TIMELINE_EVENTS (Eventos de Timeline)
- **Tipos**: education, course, certificate, project
- **Período**: start_date obrigatório, end_date opcional (em andamento)
- **Visibilidade**: is_public controla se aparece no perfil público
- **Grau**: degree_type para educação (fundamental, medio, graduacao, pos, etc.)
- **Ordenação**: order_priority para controle de exibição
- **Mídia**: media_url para certificados e imagens
- **Soft delete** para histórico

#### 📋 Regras de Timeline
- **Apenas médicos**: Timeline events são para perfis de médicos
- **Validação de período**: end_date deve ser posterior a start_date
- **Ordenação**: Eventos ordenados por order_priority, depois por data

---

### Módulo de Consultas (Atualizado)

#### 📅 APPOINTMENTS (Consultas)
- **Status atualizados**: scheduled, in_progress, completed, no_show, cancelled, rescheduled
- **Código único**: access_code único para cada consulta
- **Relacionamentos expandidos**: Agora conecta com múltiplas entidades de prontuário
- **Logs obrigatórios**: AppointmentLog registra todas as mudanças
- **Integração com prontuário**: Consultas podem ter prescrições, diagnósticos, exames, anotações, atestados, sinais vitais e documentos

#### 📋 Regras de Consulta Atualizadas
- **Prontuário durante consulta**: Médico pode acessar e editar prontuário durante consulta em andamento
- **Finalização**: Ao finalizar consulta, prontuário é bloqueado para edição (exceto complementos)
- **Complementos**: Médico pode adicionar complementos após finalização
- **PDF de consulta**: Sistema pode gerar PDF completo da consulta com prontuário

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../../../index/VisaoGeral.md)** - Índice central da documentação
- **[📊 Matriz de Rastreabilidade](../../../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../../../index/Glossario.md)** - Definições de termos técnicos
- **[🏗️ Arquitetura](../Architecture/Arquitetura.md)** - Estrutura e padrões do sistema
- **[⚙️ Lógica de Consultas](../../../modules/appointments/AppointmentsLogica.md)** - Regras de agendamento
- **[🔐 Autenticação](../../../modules/auth/RegistrationLogic.md)** - Fluxos de registro e login

### Implementações Relacionadas
- **[User Model](../../app/Models/User.php)** - Entidade base de usuários
- **[Doctor Model](../../app/Models/Doctor.php)** - Entidade de médicos
- **[Patient Model](../../app/Models/Patient.php)** - Entidade de pacientes
- **[Auth Middleware](../../app/Http/Middleware/)** - Controle de acesso
- **[Database Migrations](../../../../database/migrations/)** - Estrutura do banco

### Termos do Glossário
- **[User](../../../index/Glossario.md#u)** - Entidade base do sistema
- **[Doctor](../../../index/Glossario.md#d)** - Entidade que representa um médico
- **[Patient](../../../index/Glossario.md#p)** - Entidade que representa um paciente
- **[ServiceLocation](../../../index/Glossario.md#s)** - Local de atendimento
- **[AvailabilitySlot](../../../index/Glossario.md#a)** - Slot de disponibilidade
- **[BlockedDate](../../../index/Glossario.md#b)** - Data bloqueada
- **[Prescription](../../../index/Glossario.md#p)** - Prescrição médica
- **[Diagnosis](../../../index/Glossario.md#d)** - Diagnóstico
- **[Examination](../../../index/Glossario.md#e)** - Exame médico
- **[ClinicalNote](../../../index/Glossario.md#c)** - Anotação clínica
- **[MedicalCertificate](../../../index/Glossario.md#m)** - Atestado médico
- **[VitalSign](../../../index/Glossario.md#v)** - Sinal vital
- **[MedicalDocument](../../../index/Glossario.md#m)** - Documento médico
- **[MedicalRecordAuditLog](../../../index/Glossario.md#m)** - Log de auditoria
- **[VideoCallRoom](../../../index/Glossario.md#v)** - Sala de videoconferência
- **[VideoCallEvent](../../../index/Glossario.md#v)** - Evento de videoconferência
- **[TimelineEvent](../../../index/Glossario.md#t)** - Evento de timeline
- **[LGPD](../../../index/Glossario.md#l)** - Lei Geral de Proteção de Dados
- **[Soft Delete](../../../index/Glossario.md#s)** - Exclusão lógica para auditoria

---

*Última atualização: Janeiro 2025*
*Versão: 2.0*
