# 📚 Glossário - Telemedicina para Todos

## Sobre Este Documento

Este glossário centraliza as definições de termos técnicos, siglas e conceitos específicos do domínio de telemedicina utilizados no projeto. Ele serve como referência rápida para desenvolvedores, stakeholders e novos colaboradores.

---

## 📑 Sumário Navegável
- [📚 Sobre Este Documento](#sobre-este-documento)
- [🔤 Índice Alfabético](#-índice-alfabético)
- [📖 Definições](#-definições)
  - [A](#a) - [C](#c) - [D](#d) - [E](#e) - [H](#h) - [I](#i) - [L](#l) - [M](#m) - [N](#n) - [P](#p) - [R](#r) - [S](#s) - [T](#t) - [U](#u) - [V](#v)
- [🔗 Referências Cruzadas](#-referências-cruzadas)
- [📝 Como Usar Este Glossário](#-como-usar-este-glossário)

## 🔤 Índice Alfabético

- [A](#a) | [B](#b) | [C](#c) | [D](#d) | [E](#e) | [F](#f) | [G](#g) | [H](#h) | [I](#i) | [J](#j) | [K](#k) | [L](#l) | [M](#m) | [N](#n) | [O](#o) | [P](#p) | [Q](#q) | [R](#r) | [S](#s) | [T](#t) | [U](#u) | [V](#v) | [W](#w) | [X](#x) | [Y](#y) | [Z](#z)

---

## A

### **Appointment** 📅
**Definição Técnica**: Entidade que representa uma consulta médica agendada no sistema.

**Definição Leiga**: É a "marcação" da consulta entre médico e paciente.

**Ciclo de Vida**:
- `SCHEDULED` - Consulta agendada e confirmada
- `IN_PROGRESS` - Consulta em andamento (vídeo ativo)
- `COMPLETED` - Consulta finalizada com sucesso
- `CANCELLED` - Consulta cancelada
- `NO_SHOW` - Paciente não compareceu

**Relacionamentos**: Conecta um `Doctor` e um `Patient` em uma data/hora específica.

**Ver também**: [Consultas - Lógica](Appointments/AppointmentsLogica.md), [Implementação](Appointments/AppointmentsImplementationStudy.md)

---

## C

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

### **VideoCall** 📞
**Definição Técnica**: Sistema de videoconferência integrado à plataforma.

**Definição Leiga**: É a "chamada de vídeo" entre médico e paciente.

**Componentes**:
- Estabelecimento de conexão
- Transmissão de áudio/vídeo
- Compartilhamento de tela
- Gravação (se autorizada)

**Implementação**: WebRTC com Laravel Reverb

**Ver também**: [Implementação](VideoCall/VideoCallImplementation.md), [Tarefas](VideoCall/VideoCallTasks.md)

---

## 🔗 Referências Cruzadas

### Por Domínio
- **Autenticação**: User, Doctor, Patient, LGPD
- **Consultas**: Appointment, Consulta, No-Show, Prontuário Digital
- **Técnico**: DTO, Service, Migration, Eloquent, Inertia.js
- **Compliance**: LGPD, CRM, Soft Delete

### Por Documento
- **[Regras de Negócio](Rules/SystemRules.md)**: User, Doctor, Patient, LGPD, Soft Delete
- **[Lógica de Consultas](Appointments/AppointmentsLogica.md)**: Appointment, Consulta, No-Show
- **[Arquitetura](Architecture/Arquitetura.md)**: DTO, Service, Eloquent, Inertia.js
- **[Videochamadas](VideoCall/VideoCallImplementation.md)**: VideoCall, WebRTC

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

*Última atualização: Dezembro 2024*
*Versão: 1.0*
