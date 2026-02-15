# 🧩 Camada de Domínio & Aplicação (Domain Layer)

Responsável pelas **regras de negócio**, **fluxos funcionais** e **casos de uso** do sistema. Cada módulo de domínio define o *o quê* e o *porquê*; as camadas técnicas (Persistência, Sinalização, Mídia, Infraestrutura) fornecem o *como*.

Os documentos desta camada estão em **`docs/modules/`**. Este README é o **índice da camada**, mapeando cada módulo e suas dependências em relação às outras camadas.

---

## 🎯 Responsabilidades da camada

- **Regras de negócio**: quem pode fazer o quê, em que estado, com que validações.
- **Fluxos de domínio**: agendamento, consulta, registro, mensagens, videoconferência, prontuário.
- **Casos de uso**: descrição dos comportamentos esperados por papel (médico, paciente, sistema).
- **Integração entre módulos**: como Auth, Appointments, Messages, Videochamada e Prontuário se relacionam.

---

## 📂 Módulos e documentação

### 🔐 Autenticação (`auth`)

Registro, login, roles, redirecionamento por tipo de usuário e fluxo de onboarding.

| Documento | Descrição |
|-----------|-----------|
| [README](../../modules/auth/README.md) | Índice do módulo |
| [AuthSystemOverview](../../modules/auth/AuthSystemOverview.md) | Visão geral do sistema de autenticação |
| [RegistrationLogic](../../modules/auth/RegistrationLogic.md) | Fluxos de registro e login |
| [RoleBasedAccess](../../modules/auth/RoleBasedAccess.md) | Acesso baseado em papéis |
| [FrontendRouting](../../modules/auth/FrontendRouting.md) | Rotas e redirecionamento no frontend |

**Dependências de camada:** Persistência (User, Doctor, Patient), Arquitetura & Governança (regras de segurança).

---

### 📅 Consultas e agendamento (`appointments` + `agendament`)

Busca de consultas, agendamento, cancelamento, no-show, remarcação, janelas de horário e integração com agenda do médico.

| Documento | Descrição |
|-----------|-----------|
| [appointments/README](../../modules/appointments/README.md) | Índice do módulo de consultas |
| [AppointmentsLogica](../../modules/appointments/AppointmentsLogica.md) | Regras de agendamento e fluxos |
| [AppointmentsArchitecture](../../modules/appointments/AppointmentsArchitecture.md) | Arquitetura do módulo |
| [AppointmentsUXFlow](../../modules/appointments/AppointmentsUXFlow.md) | Fluxo de UX |
| [AppointmentsFrontendIntegration](../../modules/appointments/AppointmentsFrontendIntegration.md) | Integração frontend |
| [AppointmentsImplementationStudy](../../modules/appointments/AppointmentsImplementationStudy.md) | Estudo de implementação |
| [agendament/AgendamentBD](../../modules/agendament/AgendamentBD.md) | Modelo de dados do agendamento |
| [agendament/AgendamentDoctor](../../modules/agendament/AgendamentDoctor.md) | Visão do médico (agenda) |

**Dependências de camada:** Persistência (Appointments, AvailabilitySlot, BlockedDate), Sinalização (eventos de mudança de status), Arquitetura & Governança (regras de sistema).

---

### 💬 Mensagens (`messages`)

Chat entre médico e paciente: quem pode trocar mensagens, histórico, não lidas. A documentação detalhada está na **Camada de Sinalização** (tempo real).

| Documento | Descrição |
|-----------|-----------|
| [README (signaling)](../signaling/messages/README.md) | Sistema de mensagens completo (regras, API, Echo) |
| [MELHORIAS_AVANCADAS](../signaling/messages/MELHORIAS_AVANCADAS.md) | Delivery semantics, índices, paginação |

**Regras de domínio:** Apenas usuários com appointment podem trocar mensagens; conversas baseadas em appointments.

**Dependências de camada:** Persistência (Message, Appointments), Sinalização (Laravel Reverb, MessageSent).

---

### 📹 Videochamadas (`videocall`)

Início/encerramento de chamada, vínculo com consulta, janela de acesso. A documentação detalhada está na **Camada de Sinalização** (eventos) e **Camada de Mídia** (WebRTC).

| Documento | Descrição |
|-----------|-----------|
| [README (signaling)](../signaling/videocall/README.md) | Visão geral, fluxo, requisitos |
| [VideoCallImplementation](../signaling/videocall/VideoCallImplementation.md) | Implementação (sinalização + P2P) |
| [VideoCallTasks](../signaling/videocall/VideoCallTasks.md) | Checklist e evolução do módulo |

**Regras de domínio:** Chamada vinculada a appointment; apenas médico/paciente da consulta; janela de tempo (lead/duration/grace).

**Dependências de camada:** Persistência (VideoCallRoom, VideoCallEvent, Appointments), Sinalização (RequestVideoCall, RequestVideoCallStatus), Mídia (PeerJS/WebRTC).

---

### 🩺 Prontuários médicos (`MedicalRecords`)

Prescrições, diagnósticos, exames, anotações clínicas, atestados, sinais vitais, documentos e auditoria (LGPD).

| Documento | Descrição |
|-----------|-----------|
| [MedicalRecordsDoctor](../../modules/MedicalRecords/MedicalRecordsDoctor.md) | Gestão completa pelo médico |
| [MedicalRecordsPatient](../../modules/MedicalRecords/MedicalRecordsPatient.md) | Visualização pelo paciente |

**Dependências de camada:** Persistência (Prescription, Diagnosis, Examination, ClinicalNote, MedicalCertificate, VitalSign, MedicalDocument, MedicalRecordAuditLog), Arquitetura & Governança (regras de compliance).

---

## 🔗 Dependências entre camadas

Cada módulo de domínio **usa** uma ou mais camadas técnicas:

| Módulo        | Persistência | Sinalização | Mídia | Infraestrutura | Arquitetura & Governança |
|---------------|--------------|-------------|-------|----------------|---------------------------|
| Auth          | ✅           | —           | —     | —              | ✅ (segurança)            |
| Appointments  | ✅           | ✅ (eventos)| —     | —              | ✅ (regras)                |
| Messages      | ✅           | ✅          | —     | —              | ✅                        |
| Videochamada  | ✅           | ✅          | ✅    | —              | ✅                        |
| MedicalRecords| ✅           | —           | —     | —              | ✅ (LGPD)                 |

---

## 📋 Convenção: “Dependências de camada” por módulo

Em cada módulo (em `docs/modules/` ou no README da camada de domínio), você pode documentar de forma curta:

1. **Regras de negócio** que o módulo implementa.
2. **Camadas técnicas** que consome (Persistência, Sinalização, Mídia, Infraestrutura).
3. **Documentos de referência** em outras camadas (ex.: link para `../signaling/videocall/README.md` ou `../persistence/database/diagrama_banco_dados.md`).

Exemplo para um novo módulo:

```markdown
## Dependências de camada
- **Persistência**: Tabelas X, Y; ver [modelo de dados](../../layers/persistence/database/README.md).
- **Sinalização**: Evento Z; ver [eventos](../../layers/signaling/README.md).
- **Arquitetura & Governança**: [SystemRules](../../layers/architecture-governance/requirements/SystemRules.md).
```

---

## 🧭 Navegação

- **Por funcionalidade:** use a tabela de módulos acima e os links para cada documento.
- **Por camada técnica:** a partir da [visão geral das camadas](../README.md), vá em Persistência, Sinalização, Mídia ou Infraestrutura conforme o que quiser estudar.
- **Por papel:** Auth (todos), Appointments/Agendament (médico e paciente), MedicalRecords (médico vs paciente), Messages e Videochamada (ambos).

---

*Documento em: `docs/layers/domain/` (Camada de Domínio & Aplicação).*  
*Última atualização: Fevereiro 2025*
