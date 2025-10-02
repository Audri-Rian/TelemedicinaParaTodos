# 📅 Módulo de Consultas

Este módulo gerencia todo o sistema de agendamento e gestão de consultas médicas.

## 📁 Arquivos

- **[⚙️ Lógica de Consultas](AppointmentsLogica.md)** - Regras de agendamento e fluxos de negócio
- **[🔧 Implementação de Consultas](AppointmentsImplementationStudy.md)** - Detalhes técnicos e checklist

## 🎯 Funcionalidades

### Agendamento
- **Criação** de consultas por pacientes
- **Disponibilidade** de médicos
- **Confirmação** automática
- **Reagendamento** quando necessário

### Gestão
- **Status** da consulta (scheduled, in_progress, completed, etc.)
- **Código de acesso** único
- **Histórico** completo
- **Logs** de auditoria

### Estados
- **SCHEDULED** - Consulta agendada
- **IN_PROGRESS** - Consulta em andamento
- **COMPLETED** - Consulta finalizada
- **CANCELLED** - Consulta cancelada
- **NO_SHOW** - Paciente não compareceu
- **RESCHEDULED** - Consulta reagendada

## 🔗 Relacionamentos

### Dependências
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de agendamento
- **[🏗️ Arquitetura](../architecture/Arquitetura.md)** - Padrões de implementação
- **[📊 Matriz de Requisitos](../index/MatrizRequisitos.md)** - RF003

### Implementações
- **[Appointment Model](../../../app/Models/Appointments.php)** - Entidade de consultas
- **[Appointment Service](../../../app/Services/AppointmentService.php)** - Lógica de negócio
- **[Appointment Observer](../../../app/Observers/AppointmentsObserver.php)** - Eventos automáticos

## 🏗️ Arquitetura

### Fluxo de Agendamento
1. **Seleção** → Paciente escolhe médico e horário
2. **Validação** → Verificação de disponibilidade
3. **Criação** → Geração de consulta com código único
4. **Notificação** → Confirmação para ambas as partes
5. **Monitoramento** → Acompanhamento do status

### Fluxo de Consulta
1. **Início** → Médico inicia a consulta
2. **Progresso** → Status atualizado para IN_PROGRESS
3. **Finalização** → Consulta concluída
4. **Registro** → Dados salvos no histórico

## 📊 Requisitos Implementados

- **RF003** - Agendamento de Consultas ✅
- **RF004** - Realizar Consultas Online 🔄 (parcial)

## 🧪 Testes

- **[AppointmentsTest](../../../tests/Unit/AppointmentsTest.php)** - Testes unitários
- **Cobertura**: Model, Service, Observer
- **Cenários**: Criação, transições, validações

---

*Última atualização: Dezembro 2024*

