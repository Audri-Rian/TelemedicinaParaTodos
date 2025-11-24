# 📚 Glossário - Telemedicina para Todos

## Sobre Este Documento

Este glossário centraliza as definições de termos técnicos, siglas e conceitos específicos do domínio de telemedicina utilizados no projeto. Ele serve como referência rápida para desenvolvedores, stakeholders e novos colaboradores.

---

## 📑 Sumário Navegável
- [📚 Sobre Este Documento](#sobre-este-documento)
- [🔤 Índice Alfabético](#-índice-alfabético)
- [📖 Definições](#-definições)
  - [A](#a) - [B](#b) - [C](#c) - [D](#d) - [E](#e) - [H](#h) - [I](#i) - [L](#l) - [M](#m) - [N](#n) - [P](#p) - [R](#r) - [S](#s) - [T](#t) - [U](#u) - [V](#v)
- [🔗 Referências Cruzadas](#-referências-cruzadas)
- [📝 Como Usar Este Glossário](#-como-usar-este-glossário)

## 🔤 Índice Alfabético

- [A](#a) | [B](#b) | [C](#c) | [D](#d) | [E](#e) | [F](#f) | [G](#g) | [H](#h) | [I](#i) | [J](#j) | [K](#k) | [L](#l) | [M](#m) | [N](#n) | [O](#o) | [P](#p) | [Q](#q) | [R](#r) | [S](#s) | [T](#t) | [U](#u) | [V](#v) | [W](#w) | [X](#x) | [Y](#y) | [Z](#z)

---

## A

### **AvailabilitySlot** ⏰
**Definição Técnica**: Entidade que representa um slot de disponibilidade de um médico para atendimento.

**Definição Leiga**: São os "horários" que o médico marca como disponíveis para consultas.

**Tipos**:
- `recurring` - Recorrente (toda segunda-feira, por exemplo)
- `specific` - Específico (uma data específica)

**Características**:
- Horário de início e fim
- Dia da semana (para recorrentes) ou data específica
- Local de atendimento associado
- Status ativo/inativo

**Relacionamentos**: N:1 com DOCTORS e DOCTOR_SERVICE_LOCATIONS

**Ver também**: [Sistema de Agenda](../modules/appointments/AppointmentsLogica.md)

### **Appointment** 📅
**Definição Técnica**: Entidade que representa uma consulta médica agendada no sistema.

**Definição Leiga**: É a "marcação" da consulta entre médico e paciente.

**Ciclo de Vida**:
- `SCHEDULED` - Consulta agendada e confirmada
- `IN_PROGRESS` - Consulta em andamento (vídeo ativo)
- `COMPLETED` - Consulta finalizada com sucesso
- `CANCELLED` - Consulta cancelada
- `NO_SHOW` - Paciente não compareceu
- `RESCHEDULED` - Consulta reagendada

**Relacionamentos**: Conecta um `Doctor` e um `Patient` em uma data/hora específica. Pode ter múltiplos relacionamentos com prontuário (prescrições, diagnósticos, exames, anotações clínicas, atestados, sinais vitais, documentos).

**Ver também**: [Consultas - Lógica](../modules/appointments/AppointmentsLogica.md), [Implementação](../modules/appointments/AppointmentsImplementationStudy.md)

---

## B

### **BlockedDate** 🚫
**Definição Técnica**: Entidade que representa uma data bloqueada para atendimento por um médico.

**Definição Leiga**: É quando o médico marca que não vai atender em um dia específico.

**Uso no Sistema**: Usado para bloquear datas específicas na agenda do médico (feriados, férias, etc.).

**Campos**: `blocked_date`, `reason`

**Relacionamentos**: N:1 com DOCTORS

**Ver também**: [Sistema de Agenda](../modules/appointments/AppointmentsLogica.md)

---

## C

### **CID-10** 🏷️
**Definição Técnica**: Classificação Estatística Internacional de Doenças e Problemas Relacionados à Saúde - 10ª Revisão.

**Definição Leiga**: É o código internacional usado pelos médicos para classificar doenças e diagnósticos.

**Uso no Sistema**: Usado em diagnósticos (`Diagnosis`) para padronizar e classificar condições médicas.

**Formato**: Código alfanumérico (ex: A00.0, E11.9)

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **ClinicalNote** 📝
**Definição Técnica**: Entidade que representa uma anotação clínica feita pelo médico durante ou após uma consulta.

**Definição Leiga**: São as "anotações" que o médico faz sobre o paciente.

**Características**:
- Pode ser privada (apenas médico) ou compartilhada (paciente vê)
- Suporta versões (histórico de edições)
- Categorização e tags
- Vinculada a consultas ou independente

**Relacionamentos**: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **CRM** 🩺
**Definição Técnica**: Conselho Regional de Medicina - registro profissional obrigatório para médicos no Brasil.

**Definição Leiga**: É o "número de registro" do médico, como uma carteira de identidade profissional.

**Uso no Sistema**: Campo obrigatório e único para identificação e validação de médicos.

**Formato**: Número seguido de UF (ex: 123456/SP)

### **Consulta** 🏥
**Sinônimo**: Appointment, Agendamento

**Definição Técnica**: Sessão médica entre um médico e um paciente, realizada através da plataforma.

**Definição Leiga**: É a "conversa" entre médico e paciente para diagnóstico ou acompanhamento.

**Componentes**:
- Agendamento prévio
- Chamada de vídeo
- Prontuário digital
- Prescrição (se necessário)

---

## D

### **Diagnosis** 🩺
**Definição Técnica**: Entidade que representa um diagnóstico médico registrado no sistema.

**Definição Leiga**: É o "diagnóstico" que o médico faz sobre a condição do paciente.

**Características**:
- Código CID-10 obrigatório
- Tipo: `principal` ou `secondary`
- Descrição detalhada
- Vinculado a consulta, médico e paciente

**Relacionamentos**: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **Doctor** 👨‍⚕️
**Definição Técnica**: Entidade que representa um médico cadastrado no sistema.

**Definição Leiga**: É o profissional da saúde que atende os pacientes.

**Atributos Principais**:
- CRM obrigatório
- Especialidade principal
- Disponibilidade para consultas
- Status (ativo/inativo)

**Relacionamentos**: Herda de `User` e pode ter múltiplos `Appointments`.

**Ver também**: [Regras de Negócio](Rules/SystemRules.md#doctors-médicos)

### **DTO (Data Transfer Object)** 📦
**Definição Técnica**: Padrão de design que encapsula dados para transferência entre camadas da aplicação.

**Definição Leiga**: É uma "caixinha" que organiza as informações que vão de um lugar para outro no sistema.

**Uso no Sistema**: Usado entre Controllers e Services para garantir tipagem e validação.

**Exemplo**: `CreateAppointmentDTO`, `UpdatePatientDTO`

**Ver também**: [Arquitetura](Architecture/Arquitetura.md#dtos-data-transfer-objects)

---

## E

### **Examination** 🔬
**Definição Técnica**: Entidade que representa um exame médico solicitado ou realizado.

**Definição Leiga**: São os "exames" que o médico pede para o paciente fazer.

**Tipos**:
- `lab` - Exames laboratoriais
- `image` - Exames de imagem
- `other` - Outros tipos

**Status**:
- `requested` - Solicitado
- `in_progress` - Em andamento
- `completed` - Concluído
- `cancelled` - Cancelado

**Características**:
- Resultados em JSON
- Anexos de arquivos
- Datas de solicitação e conclusão

**Relacionamentos**: N:1 com APPOINTMENTS, PATIENTS e DOCTORS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **Eloquent** 🔗
**Definição Técnica**: ORM (Object-Relational Mapping) do Laravel para interação com banco de dados.

**Definição Leiga**: É a ferramenta que permite ao sistema "conversar" com o banco de dados usando linguagem mais simples.

**Uso no Sistema**: Todos os Models (`User`, `Doctor`, `Patient`, `Appointment`) usam Eloquent.

---

## H

### **Histórico Médico** 📋
**Definição Técnica**: Conjunto de informações sobre saúde do paciente armazenadas no sistema.

**Definição Leiga**: É o "prontuário digital" com todas as informações médicas do paciente.

**Componentes**:
- Consultas anteriores
- Diagnósticos
- Medicações
- Exames
- Alergias

**Segurança**: Dados criptografados conforme LGPD.

---

## I

### **Inertia.js** ⚡
**Definição Técnica**: Biblioteca que conecta Laravel (backend) com Vue.js (frontend) sem API REST.

**Definição Leiga**: É a "ponte" que permite ao sistema web funcionar de forma mais rápida e integrada.

**Benefícios**:
- Menos requisições HTTP
- Interface mais responsiva
- Desenvolvimento mais simples

**Ver também**: [Arquitetura Frontend](Architecture/Arquitetura.md#estrutura-do-frontend)

---

## L

### **LGPD** 🔒
**Definição Técnica**: Lei Geral de Proteção de Dados - regulamentação brasileira sobre privacidade.

**Definição Leiga**: É a "lei de privacidade" que protege os dados pessoais dos usuários.

**Aplicação no Sistema**:
- Consentimento explícito para telemedicina
- Criptografia de dados sensíveis
- Logs de auditoria
- Direito ao esquecimento

**Ver também**: [Segurança e Compliance](Rules/SystemRules.md#segurança-e-compliance)

---

## M

### **MedicalCertificate** 📜
**Definição Técnica**: Entidade que representa um atestado médico emitido pelo sistema.

**Definição Leiga**: É o "atestado" que o médico emite para o paciente.

**Características**:
- Código de verificação único
- Período de validade (start_date, end_date)
- Razão e restrições
- Assinatura digital (hash)
- PDF gerado automaticamente
- Status (active, expired, cancelled)

**Relacionamentos**: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **MedicalDocument** 📎
**Definição Técnica**: Entidade que representa um documento médico anexado ao prontuário.

**Definição Leiga**: São "documentos" (laudos, exames, receitas) que ficam guardados no prontuário do paciente.

**Categorias**:
- `exam` - Exames
- `prescription` - Prescrições
- `report` - Relatórios
- `other` - Outros

**Visibilidade**:
- `patient` - Apenas paciente vê
- `doctor` - Apenas médico vê
- `shared` - Ambos veem

**Relacionamentos**: N:1 com PATIENTS, APPOINTMENTS, DOCTORS e USERS (uploaded_by)

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **MedicalRecordAuditLog** 📊
**Definição Técnica**: Entidade que registra todas as ações realizadas em prontuários médicos para auditoria e compliance.

**Definição Leiga**: É o "log" que registra tudo que foi feito no prontuário do paciente.

**Finalidade**:
- Compliance LGPD
- Rastreabilidade de ações
- Auditoria médica
- Segurança de dados

**Campos**: `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `metadata`

**Relacionamentos**: N:1 com PATIENTS e USERS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md), [LGPD](#lgpd)

### **Migration** 🗄️
**Definição Técnica**: Arquivo que define mudanças na estrutura do banco de dados.

**Definição Leiga**: É o "plano de construção" de cada tabela do banco de dados.

**Uso no Sistema**: Cada tabela (`users`, `doctors`, `patients`, `appointments`) tem sua migration correspondente.

**Localização**: `database/migrations/`

**Ver também**: [Diagrama do Banco](diagrama_banco_dados.md)

---

## N

### **No-Show** ❌
**Definição Técnica**: Status de consulta quando o paciente não comparece no horário agendado.

**Definição Leiga**: É quando o paciente "falta" à consulta marcada.

**Impacto**:
- Slot de horário fica disponível
- Médico pode ser notificado
- Histórico de faltas é registrado

**Ver também**: [Lógica de Consultas](Appointments/AppointmentsLogica.md)

---

## P

### **Prescription** 💊
**Definição Técnica**: Entidade que representa uma prescrição médica digital emitida pelo sistema.

**Definição Leiga**: É a "receita" que o médico passa para o paciente.

**Características**:
- Medicamentos em JSON (nome, dosagem, frequência)
- Instruções de uso
- Data de validade
- Status: `active`, `expired`, `cancelled`
- Data de emissão

**Relacionamentos**: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

### **Patient** 👤
**Definição Técnica**: Entidade que representa um paciente cadastrado no sistema.

**Definição Leiga**: É a pessoa que busca atendimento médico.

**Atributos Principais**:
- Data de nascimento
- Contato de emergência
- Consentimento para telemedicina
- Histórico médico

**Relacionamentos**: Herda de `User` e pode ter múltiplos `Appointments`.

**Ver também**: [Regras de Negócio](Rules/SystemRules.md#patients-pacientes)

### **Prontuário Digital** 📄
**Sinônimo**: Histórico Médico Digital

**Definição Técnica**: Registro eletrônico de informações médicas do paciente.

**Definição Leiga**: É o "caderninho médico digital" com todas as informações de saúde.

**Vantagens**:
- Acesso rápido
- Sem perda de informações
- Compartilhamento seguro
- Backup automático

---

## R

### **Reverb** 📡
**Definição Técnica**: Servidor de broadcasting em tempo real do Laravel.

**Definição Leiga**: É a tecnologia que permite notificações instantâneas no sistema.

**Uso no Sistema**:
- Notificações de agendamento
- Status de consultas
- Mensagens em tempo real

**Ver também**: [Arquitetura](Architecture/Arquitetura.md)

---

## S

### **ServiceLocation** 📍
**Definição Técnica**: Entidade que representa um local de atendimento de um médico.

**Definição Leiga**: São os "lugares" onde o médico atende (consultório, hospital, teleconsulta).

**Tipos**:
- `teleconsultation` - Teleconsulta (online)
- `office` - Consultório
- `hospital` - Hospital
- `clinic` - Clínica

**Características**:
- Endereço físico (para tipos presenciais)
- Telefone de contato
- Descrição
- Status ativo/inativo

**Relacionamentos**: N:1 com DOCTORS, 1:N com DOCTOR_AVAILABILITY_SLOTS

**Ver também**: [Sistema de Agenda](../modules/appointments/AppointmentsLogica.md)

### **Service** ⚙️
**Definição Técnica**: Camada que contém a lógica de negócio da aplicação.

**Definição Leiga**: É onde ficam as "regras" do sistema - o que pode e não pode ser feito.

**Responsabilidades**:
- Validar regras de negócio
- Coordenar operações complexas
- Interagir com Models
- Retornar dados processados

**Exemplos**: `AppointmentService`, `DoctorService`, `PatientService`

**Ver também**: [Arquitetura](Architecture/Arquitetura.md#services)

### **Soft Delete** 🗑️
**Definição Técnica**: Técnica que marca registros como excluídos sem removê-los fisicamente do banco.

**Definição Leiga**: É como "mover para lixeira" - o dado fica oculto mas não é apagado para sempre.

**Benefícios**:
- Auditoria completa
- Possibilidade de recuperação
- Histórico preservado

**Uso no Sistema**: Todas as entidades principais (`User`, `Doctor`, `Patient`) usam soft delete.

---

## T

### **TimelineEvent** 📅
**Definição Técnica**: Entidade que representa um evento na timeline profissional (educação, cursos, certificados, projetos).

**Definição Leiga**: São os "eventos" que aparecem na linha do tempo do perfil do médico (formação, cursos, etc.).

**Tipos**:
- `education` - Educação formal
- `course` - Cursos
- `certificate` - Certificados
- `project` - Projetos

**Características**:
- Período (start_date, end_date)
- Descrição e mídia
- Tipo de grau (para educação)
- Visibilidade pública/privada
- Prioridade de ordenação

**Relacionamentos**: N:1 com USERS

**Ver também**: [Arquitetura](../Architecture/Arquitetura.md)

### **Telemedicina** 📹
**Definição Técnica**: Prática médica realizada à distância através de tecnologias de comunicação.

**Definição Leiga**: É fazer consulta médica pela internet, sem sair de casa.

**Componentes**:
- Consulta por vídeo
- Prontuário digital
- Prescrição eletrônica
- Segurança de dados

**Regulamentação**: Resolução CFM nº 2.314/2022

---

## U

### **User** 👥
**Definição Técnica**: Entidade base do sistema que representa qualquer usuário (médico ou paciente).

**Definição Leiga**: É a "conta" básica no sistema - pode ser de médico ou paciente.

**Atributos**:
- Email único
- Senha segura
- Status (ativo/inativo)
- Timestamps de auditoria

**Relacionamentos**: Base para `Doctor` e `Patient` (polimorfismo).

**Ver também**: [Regras de Negócio](Rules/SystemRules.md#users-usuários-base)

---

## V

### **VideoCallEvent** 📹
**Definição Técnica**: Entidade que registra eventos ocorridos durante uma videoconferência.

**Definição Leiga**: São os "eventos" que acontecem durante uma chamada de vídeo (entrada, saída, etc.).

**Uso no Sistema**: Rastreamento de eventos de videoconferência para auditoria e análise.

**Relacionamentos**: Relacionado com VIDEO_CALL_ROOMS

**Ver também**: [Videoconferência](../modules/videocall/VideoCallImplementation.md)

### **VideoCallRoom** 🏠
**Definição Técnica**: Entidade que representa uma sala de videoconferência criada para uma consulta.

**Definição Leiga**: É a "sala virtual" onde médico e paciente se encontram para a consulta por vídeo.

**Características**:
- Criada automaticamente para consultas
- Expiração automática
- Integração com appointments

**Relacionamentos**: Relacionado com APPOINTMENTS e VIDEO_CALL_EVENTS

**Ver também**: [Videoconferência](../modules/videocall/VideoCallImplementation.md)

### **VideoCall** 📞
**Definição Técnica**: Sistema de videoconferência integrado à plataforma.

**Definição Leiga**: É a "chamada de vídeo" entre médico e paciente.

**Componentes**:
- Estabelecimento de conexão
- Transmissão de áudio/vídeo
- Compartilhamento de tela
- Gravação (se autorizada)
- Salas de videoconferência (VideoCallRoom)
- Eventos de videoconferência (VideoCallEvent)

**Implementação**: WebRTC com Laravel Reverb

**Ver também**: [Implementação](../modules/videocall/VideoCallImplementation.md), [Tarefas](../modules/videocall/VideoCallTasks.md)

### **VitalSign** 💓
**Definição Técnica**: Entidade que representa os sinais vitais de um paciente registrados durante uma consulta.

**Definição Leiga**: São as "medidas" que o médico faz do paciente (pressão, temperatura, etc.).

**Campos Registrados**:
- Pressão arterial (sistólica e diastólica)
- Temperatura
- Frequência cardíaca
- Frequência respiratória
- Saturação de oxigênio
- Peso e altura
- Notas adicionais

**Relacionamentos**: N:1 com APPOINTMENTS, PATIENTS e DOCTORS

**Ver também**: [Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)

---

## 🔗 Referências Cruzadas

### Por Domínio
- **Autenticação**: User, Doctor, Patient, LGPD
- **Consultas**: Appointment, Consulta, No-Show, Prontuário Digital
- **Prontuários**: Diagnosis, Prescription, Examination, ClinicalNote, MedicalCertificate, VitalSign, MedicalDocument, MedicalRecordAuditLog
- **Agenda**: ServiceLocation, AvailabilitySlot, BlockedDate
- **Videoconferência**: VideoCall, VideoCallRoom, VideoCallEvent
- **Timeline**: TimelineEvent
- **Técnico**: DTO, Service, Migration, Eloquent, Inertia.js
- **Compliance**: LGPD, CRM, Soft Delete, MedicalRecordAuditLog

### Por Documento
- **[Regras de Negócio](../requirements/SystemRules.md)**: User, Doctor, Patient, LGPD, Soft Delete
- **[Lógica de Consultas](../modules/appointments/AppointmentsLogica.md)**: Appointment, Consulta, No-Show, ServiceLocation, AvailabilitySlot
- **[Prontuários Médicos](../modules/MedicalRecords/MedicalRecordsDoctor.md)**: Diagnosis, Prescription, Examination, ClinicalNote, MedicalCertificate, VitalSign, MedicalDocument, MedicalRecordAuditLog
- **[Arquitetura](../Architecture/Arquitetura.md)**: DTO, Service, Eloquent, Inertia.js, TimelineEvent
- **[Videoconferência](../modules/videocall/VideoCallImplementation.md)**: VideoCall, VideoCallRoom, VideoCallEvent, WebRTC

---

## 📝 Como Usar Este Glossário

### Para Desenvolvedores
- Consulte antes de implementar novos termos
- Atualize quando criar novos conceitos
- Use como referência em code reviews

### Para Stakeholders
- Consulte para entender termos técnicos
- Use em reuniões e documentações
- Sugira novos termos quando necessário

### Para Novos Colaboradores
- Leia antes de começar no projeto
- Consulte durante o onboarding
- Mantenha atualizado conforme aprende

---

*Última atualização: Janeiro 2025*
*Versão: 2.0*
