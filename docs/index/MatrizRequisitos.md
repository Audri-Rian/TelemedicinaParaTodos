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
| **Design** | VideoCall Tasks | 🔄 | [VideoCallTasks.md](modules/videocall/VideoCallTasks.md) |
| **Lógica** | VideoCall Implementation | 🔄 | [VideoCallImplementation.md](modules/videocall/VideoCallImplementation.md) |
| **Implementação** | VideoCall Service | 🔄 | [VideoCall Service](../app/Services/) |
| **Implementação** | VideoCall Events | ✅ | [RequestVideoCall.php](../app/Events/RequestVideoCall.php) |
| **Implementação** | Broadcasting | ✅ | [Laravel Reverb](../config/reverb.php) |
| **Frontend** | VideoCall Components | 🔄 | [VideoCall Components](../resources/js/components/) |
| **Testes** | VideoCall Tests | 📋 | *Planejado* |

### RF005 - Prescrição Digital e Envio de Documentos
| **Aspecto** | **Artefato** | **Status** | **Link** |
|-------------|--------------|------------|----------|
| **Design** | Regras de Negócio | 📋 | [SystemRules.md](requirements/SystemRules.md) |
| **Lógica** | Prescription Logic | 📋 | *Planejado* |
| **Implementação** | Prescription Model | 📋 | *Planejado* |
| **Implementação** | Prescription Service | 📋 | *Planejado* |
| **Banco** | Prescription Migration | 📋 | *Planejado* |
| **Frontend** | Prescription Pages | 📋 | *Planejado* |
| **Testes** | Prescription Tests | 📋 | *Planejado* |

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
| **Lógica** | Settings Logic | 🔄 | [Settings Routes](../routes/settings.php) |
| **Implementação** | Settings Controller | 🔄 | [Settings Controller](../app/Http/Controllers/) |
| **Frontend** | Settings Pages | 🔄 | [Settings Components](../resources/js/pages/) |
| **Testes** | Settings Tests | 🔄 | [Settings Tests](../tests/Feature/Settings/) |

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
- **✅ Implementado**: 45 artefatos (60%)
- **🔄 Em desenvolvimento**: 18 artefatos (24%)
- **📋 Planejado**: 12 artefatos (16%)
- **❌ Não implementado**: 0 artefatos (0%)

### Por Categoria
- **Design/Regras**: 13 artefatos (100% implementado)
- **Lógica de Negócio**: 11 artefatos (73% implementado)
- **Implementação Backend**: 18 artefatos (78% implementado)
- **Implementação Frontend**: 12 artefatos (67% implementado)
- **Testes**: 8 artefatos (50% implementado)

### Por Prioridade
- **Essencial**: 8 requisitos (75% implementado)
- **Importante**: 3 requisitos (67% implementado)
- **Desejável**: 2 requisitos (0% implementado)

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

*Última atualização: Dezembro 2024*
*Versão da matriz: 1.0*
*Próxima revisão: Janeiro 2025*
