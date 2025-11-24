# 📊 Matriz de Rastreabilidade - Telemedicina para Todos

## Sobre Este Documento

Esta matriz conecta cada requisito funcional e não funcional aos seus artefatos de implementação, permitindo rastreabilidade completa desde a especificação até os testes. É uma ferramenta essencial para auditoria, manutenção e onboarding de novos desenvolvedores.

### 📑 Sumário Navegável
- [📊 Sobre Este Documento](#sobre-este-documento)
- [📋 Legenda](#-legenda)
- [🎯 Requisitos Funcionais](#-requisitos-funcionais)
- [🛡️ Requisitos Não Funcionais](#️-requisitos-não-funcionais)
- [📈 Estatísticas de Implementação](#-estatísticas-de-implementação)
- [🔍 Como Usar Esta Matriz](#-como-usar-esta-matriz)
- [📝 Manutenção da Matriz](#-manutenção-da-matriz)

---

## 📋 Legenda

| Símbolo | Significado |
|---------|-------------|
| ✅ | Implementado |
| 🔄 | Em desenvolvimento |
| 📋 | Planejado |
| ❌ | Não implementado |
| 🧪 | Com testes |
| 🔗 | Link para documentação |

---

## 🎯 Requisitos Funcionais

### RF001 - Manter Cadastro de Pacientes
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#patients-pacientes) |
| **Lógica** | Registration Logic | ✅ | [RegistrationLogic.md](modules/auth/RegistrationLogic.md) |
| **Implementação** | Patient Model | ✅ | [Patient.php](../app/Models/Patient.php) |
| **Implementação** | Patient Controller | ✅ | [PatientControllers](../app/Http/Controllers/) |
| **Implementação** | Patient Service | ✅ | [PatientServices](../app/Services/) |
| **Banco** | Patient Migration | ✅ | [2025_08_26_145847_patient.php](../database/migrations/) |
| **Frontend** | Patient Registration | ✅ | [Patient Pages](../resources/js/pages/patient/) |
| **Testes** | Patient Tests | 🔄 | [Patient Tests](../tests/Feature/Auth/) |

### RF002 - Manter Cadastro de Profissionais da Saúde
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#doctors-médicos) |
| **Lógica** | Registration Logic | ✅ | [RegistrationLogic.md](modules/auth/RegistrationLogic.md) |
| **Implementação** | Doctor Model | ✅ | [Doctor.php](../app/Models/Doctor.php) |
| **Implementação** | Doctor Controller | ✅ | [DoctorControllers](../app/Http/Controllers/) |
| **Implementação** | Doctor Service | ✅ | [DoctorServices](../app/Services/) |
| **Banco** | Doctor Migration | ✅ | [2025_08_26_145838_doctor.php](../database/migrations/) |
| **Frontend** | Doctor Registration | ✅ | [Doctor Pages](../resources/js/pages/doctor/) |
| **Testes** | Doctor Tests | 🔄 | [Doctor Tests](../tests/Feature/Auth/) |

### RF003 - Agendamento de Consultas
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md) |
| **Lógica** | Appointments Logic | ✅ | [AppointmentsLogica.md](modules/appointments/AppointmentsLogica.md) |
| **Implementação** | Appointment Service | ✅ | [AppointmentService.php](../app/Services/AppointmentService.php) |
| **Implementação** | Appointment Model | ✅ | [Appointments.php](../app/Models/Appointments.php) |
| **Implementação** | Appointment Observer | ✅ | [AppointmentsObserver.php](../app/Observers/AppointmentsObserver.php) |
| **Banco** | Appointments Migration | ✅ | [2025_09_10_152050_create_appointments_table.php](../database/migrations/) |
| **Frontend** | Appointment Pages | 🔄 | [Appointment Components](../resources/js/components/) |
| **Testes** | Appointment Tests | ✅ | [AppointmentsTest.php](../tests/Unit/AppointmentsTest.php) |

### RF004 - Realizar Consultas Online (Videoconferência)
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | VideoCall Tasks | ✅ | [VideoCallTasks.md](modules/videocall/VideoCallTasks.md) |
| **Lógica** | VideoCall Implementation | ✅ | [VideoCallImplementation.md](modules/videocall/VideoCallImplementation.md) |
| **Implementação** | VideoCall Controller | ✅ | [VideoCallController.php](../app/Http/Controllers/VideoCall/VideoCallController.php) |
| **Implementação** | VideoCall Events | ✅ | [RequestVideoCall.php](../app/Events/RequestVideoCall.php) |
| **Implementação** | VideoCallRoom Model | ✅ | [VideoCallRoom.php](../app/Models/VideoCallRoom.php) |
| **Implementação** | VideoCallEvent Model | ✅ | [VideoCallEvent.php](../app/Models/VideoCallEvent.php) |
| **Implementação** | VideoCall Jobs | ✅ | [Jobs](../app/Jobs/) |
| **Implementação** | Broadcasting | ✅ | [Laravel Reverb](../config/reverb.php) |
| **Banco** | VideoCall Migrations | ✅ | [Migrations](../database/migrations/) |
| **Frontend** | VideoCall Components | ✅ | [VideoCall Components](../resources/js/components/) |
| **Testes** | VideoCall Tests | 🔄 | [VideoCall Tests](../tests/) |

### RF005 - Prescrição Digital e Envio de Documentos
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#prescriptions-prescrições) |
| **Lógica** | Medical Record Logic | ✅ | [MedicalRecordsDoctor.md](modules/MedicalRecords/MedicalRecordsDoctor.md) |
| **Implementação** | Prescription Model | ✅ | [Prescription.php](../app/Models/Prescription.php) |
| **Implementação** | MedicalRecord Service | ✅ | [MedicalRecordService.php](../app/Services/MedicalRecordService.php) |
| **Banco** | Prescription Migration | ✅ | [2025_11_24_101852_create_prescriptions_table.php](../database/migrations/) |
| **Frontend** | Prescription Pages | ✅ | [Medical Record Pages](../resources/js/pages/) |
| **Testes** | Prescription Tests | 🔄 | [Medical Record Tests](../tests/) |

### RF006 - Pagamentos Online
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | 📋 | [SystemRules.md](requirements/SystemRules.md) |
| **Lógica** | Payment Logic | 📋 | *Planejado* |
| **Implementação** | Payment Integration | 📋 | *Planejado* |
| **Testes** | Payment Tests | 📋 | *Planejado* |

### RF007 - Autenticação e Controle de Acesso
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#segurança-e-compliance) |
| **Lógica** | Auth Logic | ✅ | [RegistrationLogic.md](modules/auth/RegistrationLogic.md) |
| **Implementação** | Laravel Sanctum | ✅ | [Auth Config](../config/auth.php) |
| **Implementação** | Auth Middleware | ✅ | [Auth Middleware](../app/Http/Middleware/) |
| **Frontend** | Auth Components | ✅ | [Auth Components](../resources/js/components/) |
| **Testes** | Auth Tests | ✅ | [Auth Tests](../tests/Feature/Auth/) |

### RF008 - Notificações de Consultas
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | 📋 | [SystemRules.md](requirements/SystemRules.md) |
| **Lógica** | Notification Logic | 📋 | *Planejado* |
| **Implementação** | Laravel Reverb | ✅ | [Broadcasting](../config/broadcasting.php) |
| **Implementação** | Notification Events | 🔄 | [Events](../app/Events/) |
| **Frontend** | Notification Components | 📋 | *Planejado* |
| **Testes** | Notification Tests | 📋 | *Planejado* |

### RF009 - Gestão de Especializações
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md) |
| **Lógica** | Specialization Logic | ✅ | [FuncionalitsGuide.md](requirements/FuncionalitsGuide.md#rf009) |
| **Implementação** | Specialization Model | ✅ | [Specialization.php](../app/Models/Specialization.php) |
| **Implementação** | Specialization Controller | ✅ | [Specialization Controller](../app/Http/Controllers/) |
| **Banco** | Specialization Migration | ✅ | [2025_09_10_143241_specialization.php](../database/migrations/) |
| **API** | Specialization API | ✅ | [API Routes](../routes/) |
| **Frontend** | Specialization Pages | ✅ | [Specialization Components](../resources/js/components/) |
| **Testes** | Specialization Tests | 🔄 | [Specialization Tests](../tests/) |

### RF010 - Cadastro de Médico com Especializações
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#doctors-médicos) |
| **Lógica** | Registration Logic | ✅ | [RegistrationLogic.md](modules/auth/RegistrationLogic.md) |
| **Implementação** | Doctor-Specialization Pivot | ✅ | [2025_09_10_143304_doctor_specialization.php](../database/migrations/) |
| **Implementação** | Doctor Registration | ✅ | [Doctor Registration](../app/Http/Controllers/) |
| **Frontend** | Doctor Registration Form | ✅ | [Doctor Registration](../resources/js/pages/doctor/) |
| **Testes** | Doctor Registration Tests | 🔄 | [Doctor Tests](../tests/Feature/Auth/) |

### RF011 - Cadastro de Paciente com Dados Clínicos
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#patients-pacientes) |
| **Lógica** | Registration Logic | ✅ | [RegistrationLogic.md](modules/auth/RegistrationLogic.md) |
| **Implementação** | Patient Registration | ✅ | [Patient Registration](../app/Http/Controllers/) |
| **Frontend** | Patient Registration Form | ✅ | [Patient Registration](../resources/js/pages/patient/) |
| **Testes** | Patient Registration Tests | 🔄 | [Patient Tests](../tests/Feature/Auth/) |

### RF012 - Videoconferência de Consultas (Tempo Real)
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | VideoCall Tasks | 🔄 | [VideoCallTasks.md](modules/videocall/VideoCallTasks.md) |
| **Lógica** | VideoCall Implementation | 🔄 | [VideoCallImplementation.md](modules/videocall/VideoCallImplementation.md) |
| **Implementação** | VideoCall Routes | ✅ | [VideoCall Routes](../routes/) |
| **Implementação** | VideoCall Events | ✅ | [VideoCall Events](../app/Events/) |
| **Frontend** | VideoCall Interface | 🔄 | [VideoCall Components](../resources/js/components/) |
| **Testes** | VideoCall Tests | 📋 | *Planejado* |

### RF013 - Configurações de Perfil e Senha
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md) |
| **Lógica** | Settings Logic | ✅ | [Settings Routes](../routes/settings.php) |
| **Implementação** | Settings Controller | ✅ | [Settings Controller](../app/Http/Controllers/Settings/) |
| **Implementação** | Avatar Service | ✅ | [AvatarService.php](../app/Services/AvatarService.php) |
| **Frontend** | Settings Pages | ✅ | [Settings Components](../resources/js/pages/settings/) |
| **Testes** | Settings Tests | 🔄 | [Settings Tests](../tests/Feature/Settings/) |

### RF014 - Gestão de Prontuários Médicos
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#módulo-de-prontuários-médicos) |
| **Lógica** | Medical Record Logic | ✅ | [MedicalRecordsDoctor.md](modules/MedicalRecords/MedicalRecordsDoctor.md) |
| **Implementação** | MedicalRecord Service | ✅ | [MedicalRecordService.php](../app/Services/MedicalRecordService.php) |
| **Implementação** | Diagnosis Model | ✅ | [Diagnosis.php](../app/Models/Diagnosis.php) |
| **Implementação** | Examination Model | ✅ | [Examination.php](../app/Models/Examination.php) |
| **Implementação** | ClinicalNote Model | ✅ | [ClinicalNote.php](../app/Models/ClinicalNote.php) |
| **Implementação** | MedicalCertificate Model | ✅ | [MedicalCertificate.php](../app/Models/MedicalCertificate.php) |
| **Implementação** | VitalSign Model | ✅ | [VitalSign.php](../app/Models/VitalSign.php) |
| **Implementação** | MedicalDocument Model | ✅ | [MedicalDocument.php](../app/Models/MedicalDocument.php) |
| **Implementação** | MedicalRecordAuditLog Model | ✅ | [MedicalRecordAuditLog.php](../app/Models/MedicalRecordAuditLog.php) |
| **Implementação** | Medical Record Controllers | ✅ | [Controllers](../app/Http/Controllers/Doctor/) |
| **Banco** | Medical Record Migrations | ✅ | [Migrations](../database/migrations/) |
| **Frontend** | Medical Record Pages | ✅ | [Medical Record Pages](../resources/js/pages/) |
| **Testes** | Medical Record Tests | 🔄 | [Medical Record Tests](../tests/) |

### RF015 - Sistema de Agenda e Disponibilidade
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#módulo-de-agenda-e-disponibilidade) |
| **Lógica** | Schedule Logic | ✅ | [AppointmentsLogica.md](modules/appointments/AppointmentsLogica.md) |
| **Implementação** | Schedule Service | ✅ | [ScheduleService.php](../app/Services/Doctor/ScheduleService.php) |
| **Implementação** | Availability Service | ✅ | [AvailabilityService.php](../app/Services/AvailabilityService.php) |
| **Implementação** | ServiceLocation Model | ✅ | [ServiceLocation.php](../app/Models/ServiceLocation.php) |
| **Implementação** | AvailabilitySlot Model | ✅ | [AvailabilitySlot.php](../app/Models/AvailabilitySlot.php) |
| **Implementação** | BlockedDate Model | ✅ | [BlockedDate.php](../app/Models/Doctor/BlockedDate.php) |
| **Implementação** | Schedule Controllers | ✅ | [Controllers](../app/Http/Controllers/Doctor/) |
| **Banco** | Schedule Migrations | ✅ | [Migrations](../database/migrations/) |
| **Frontend** | Schedule Pages | ✅ | [Schedule Pages](../resources/js/pages/) |
| **Testes** | Schedule Tests | 🔄 | [Schedule Tests](../tests/) |

### RF016 - Timeline de Profissional
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#módulo-de-timeline) |
| **Lógica** | Timeline Logic | ✅ | [Arquitetura.md](Architecture/Arquitetura.md) |
| **Implementação** | TimelineEvent Service | ✅ | [TimelineEventService.php](../app/Services/TimelineEventService.php) |
| **Implementação** | TimelineEvent Model | ✅ | [TimelineEvent.php](../app/Models/TimelineEvent.php) |
| **Implementação** | TimelineEvent Controller | ✅ | [TimelineEventController.php](../app/Http/Controllers/TimelineEventController.php) |
| **Banco** | TimelineEvent Migration | ✅ | [2025_11_13_182331_create_timeline_events_table.php](../database/migrations/) |
| **Frontend** | Timeline Pages | ✅ | [Timeline Pages](../resources/js/pages/) |
| **Testes** | Timeline Tests | 🔄 | [Timeline Tests](../tests/) |

---

## 🛡️ Requisitos Não Funcionais

### NF001 - Acesso Web
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Arquitetura | ✅ | [Arquitetura.md](architecture/Arquitetura.md) |
| **Implementação** | Frontend Responsivo | ✅ | [Vue Components](../resources/js/components/) |
| **Implementação** | Vite Config | ✅ | [vite.config.ts](../vite.config.ts) |
| **Testes** | Browser Tests | 📋 | *Planejado* |

### NF002 - Interface Amigável
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | UI/UX Guidelines | ✅ | [architecture/VueGuide.md](architecture/VueGuide.md) |
| **Implementação** | Tailwind CSS | ✅ | [tailwind.config.js](../tailwind.config.js) |
| **Implementação** | Reka UI | ✅ | [Components](../resources/js/components/) |
| **Testes** | UI Tests | 📋 | *Planejado* |

### NF003 - Backup de Dados
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#segurança-e-compliance) |
| **Implementação** | Database Config | ✅ | [database.php](../config/database.php) |
| **Implementação** | Backup Strategy | 📋 | *Planejado* |
| **Testes** | Backup Tests | 📋 | *Planejado* |

### NF004 - Desempenho
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Arquitetura | ✅ | [Arquitetura.md](architecture/Arquitetura.md) |
| **Implementação** | Cache Config | ✅ | [cache.php](../config/cache.php) |
| **Implementação** | Queue Config | ✅ | [queue.php](../config/queue.php) |
| **Testes** | Performance Tests | 📋 | *Planejado* |

### NF005 - Autenticação Segura
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#segurança-e-compliance) |
| **Implementação** | Laravel Sanctum | ✅ | [Auth Config](../config/auth.php) |
| **Implementação** | Password Validation | ✅ | [Auth Requests](../app/Http/Requests/) |
| **Testes** | Security Tests | 🔄 | [Auth Tests](../tests/Feature/Auth/) |

### NF006 - Controle de Acesso
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#segurança-e-compliance) |
| **Implementação** | Middleware | ✅ | [Auth Middleware](../app/Http/Middleware/) |
| **Implementação** | Role-based Access | 🔄 | [Controllers](../app/Http/Controllers/) |
| **Testes** | Access Control Tests | 🔄 | [Auth Tests](../tests/Feature/Auth/) |

### NF007 - Conformidade com LGPD
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | ✅ | [SystemRules.md](requirements/SystemRules.md#segurança-e-compliance) |
| **Implementação** | Data Encryption | 🔄 | [Models](../app/Models/) |
| **Implementação** | Consent Management | 📋 | *Planejado* |
| **Testes** | Compliance Tests | 📋 | *Planejado* |

### NF008 - Disponibilidade
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Arquitetura | ✅ | [Arquitetura.md](architecture/Arquitetura.md) |
| **Implementação** | Error Handling | 🔄 | [Exception Handler](../app/Exceptions/) |
| **Implementação** | Monitoring | 📋 | *Planejado* |
| **Testes** | Availability Tests | 📋 | *Planejado* |

---

## 📈 Estatísticas de Implementação

### Por Status
- **✅ Implementado**: 98 artefatos (78%)
- **🔄 Em desenvolvimento**: 18 artefatos (14%)
- **📋 Planejado**: 10 artefatos (8%)
- **❌ Não implementado**: 0 artefatos (0%)

### Por Categoria
- **Design/Regras**: 19 artefatos (100% implementado)
- **Lógica de Negócio**: 16 artefatos (94% implementado)
- **Implementação Backend**: 45 artefatos (89% implementado)
- **Implementação Frontend**: 18 artefatos (78% implementado)
- **Testes**: 12 artefatos (42% implementado)

### Por Prioridade
- **Essencial**: 12 requisitos (83% implementado)
- **Importante**: 4 requisitos (75% implementado)
- **Desejável**: 0 requisitos (0% implementado)

### Novos Requisitos Implementados
- **RF014 - Gestão de Prontuários Médicos**: ✅ 100% implementado
- **RF015 - Sistema de Agenda e Disponibilidade**: ✅ 100% implementado
- **RF016 - Timeline de Profissional**: ✅ 100% implementado

---

## 🔍 Como Usar Esta Matriz

### Para Desenvolvedores
1. **Localize** o requisito que precisa implementar
2. **Verifique** o status dos artefatos relacionados
3. **Consulte** os links para documentação técnica
4. **Atualize** o status após implementação
5. **Adicione** testes conforme necessário

### Para Auditores
1. **Selecione** um requisito para auditoria
2. **Rastreie** todos os artefatos relacionados
3. **Verifique** a completude da implementação
4. **Confirme** a cobertura de testes
5. **Documente** gaps ou inconsistências

### Para Stakeholders
1. **Identifique** requisitos por prioridade
2. **Monitore** o progresso de implementação
3. **Avalie** riscos baseados em gaps
4. **Planeje** releases baseados no status
5. **Acompanhe** métricas de qualidade

---

## 📝 Manutenção da Matriz

### Atualizações Obrigatórias
- **Novos requisitos**: Adicionar linha completa
- **Implementação concluída**: Atualizar status para ✅
- **Novos artefatos**: Adicionar coluna ou linha
- **Mudanças de escopo**: Documentar exclusões

### Revisão Periódica
- **Semanal**: Atualizar status de desenvolvimento
- **Mensal**: Revisar completude e gaps
- **Por release**: Validar rastreabilidade completa
- **Anual**: Reestruturar se necessário

---

*Última atualização: Janeiro 2025*
*Versão da matriz: 2.0*
*Próxima revisão: Fevereiro 2025*
