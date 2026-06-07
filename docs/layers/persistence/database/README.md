# 💾 Modelo de Dados

_Documento em: `docs/layers/persistence/database/` (Camada de Persistência)_

Esta pasta contém a documentação sobre a estrutura do banco de dados, relacionamentos e migrações.

## 📁 Arquivos

- **[🗄️ Diagrama do Banco de Dados](diagrama_banco_dados.md)** - Estrutura das tabelas e relacionamentos

## 🎯 Propósito

Esta documentação define **como** os dados são estruturados e relacionados:

- **Diagrama ERD** das entidades principais
- **Relacionamentos** entre tabelas
- **Estrutura** de cada tabela
- **Índices** e chaves estrangeiras

## 🔗 Navegação

- **DBA**: Foque no [Diagrama do Banco](diagrama_banco_dados.md)
- **Desenvolvedores**: Use para entender relacionamentos
- **Arquitetos**: Consulte para validação do modelo

## 🏗️ Estrutura do Banco

### Entidades Principais

- **Users** - Entidade base (polimórfica)
- **Doctors** - Médicos cadastrados
- **Patients** - Pacientes cadastrados
- **Appointments** - Consultas agendadas
- **Specializations** - Especialidades médicas

### Entidades de Prontuário

- **Prescriptions** - Prescrições médicas digitais
- **Diagnoses** - Diagnósticos com CID-10
- **Examinations** - Exames solicitados
- **ClinicalNotes** - Anotações clínicas
- **MedicalCertificates** - Atestados médicos
- **VitalSigns** - Sinais vitais
- **MedicalDocuments** - Documentos médicos
- **MedicalRecordAuditLogs** - Logs de auditoria

### Entidades de Agenda

- **ServiceLocations** - Locais de atendimento
- **AvailabilitySlots** - Slots de disponibilidade
- **BlockedDates** - Datas bloqueadas

### Entidades de Videoconferência

- **Calls** - Chamadas de vídeo de negócio
- **Rooms** - Salas de mídia no SFU

### Entidades de Timeline

- **TimelineEvents** - Eventos de timeline profissional

### Relacionamentos

- **1:1** User ↔ Doctor/Patient
- **1:N** Doctor ↔ Appointments, Prescriptions, Diagnoses, Examinations, ClinicalNotes, MedicalCertificates, VitalSigns, MedicalDocuments
- **1:N** Patient ↔ Appointments, Prescriptions, Diagnoses, Examinations, ClinicalNotes, MedicalCertificates, VitalSigns, MedicalDocuments, MedicalRecordAuditLogs
- **1:N** Appointment ↔ Prescriptions, Diagnoses, Examinations, ClinicalNotes, MedicalCertificates, VitalSigns, MedicalDocuments, AppointmentLogs
- **N:N** Doctor ↔ Specializations
- **1:N** Doctor ↔ ServiceLocations, AvailabilitySlots, BlockedDates
- **1:N** User ↔ TimelineEvents

## 📊 Implementação

- **Migrações**: [database/migrations/](../../../../database/migrations/)
- **Models**: [app/Models/](../../../../app/Models/)
- **Seeders**: [database/seeders/](../../../../database/seeders/)

---

_Última atualização: Janeiro 2025_
_Versão: 2.0_
