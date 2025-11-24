# 📚 TodoList de Estudos - Implementação da Tabela Appointments

## 🎯 Objetivo
Este documento serve como guia de estudos para entender completamente a implementação da funcionalidade de appointments (consultas) no sistema de telemedicina.

---

## 📋 Checklist de Estudos

### 1. 🗄️ **Estrutura do Banco de Dados**
- [V] **Migration Analysis**
  - [V] Entender a estrutura da tabela `appointments`
  - [V] Analisar os tipos de dados utilizados (UUID, timestamps, enums)
  - [V] Compreender as foreign keys e relacionamentos
  - [V] Estudar os índices criados para performance
  - [V] Verificar constraints e validações no banco

- [V] **Campos Implementados**
  - [V] `id` (UUID primary key)
  - [V] `doctor_id` (FK para doctors)
  - [V] `patient_id` (FK para patients)
  - [V] `scheduled_at` (timestamp)
  - [V] `access_code` (string única)
  - [V] `started_at` (timestamp nullable)
  - [V] `ended_at` (timestamp nullable)
  - [V] `video_recording_url` (string nullable)
  - [V] `status` (enum com 6 estados)
  - [V] `notes` (text nullable)
  - [V] `metadata` (JSON nullable)
  - [V] `timestamps` (created_at, updated_at)
  - [V] `soft_deletes` (deleted_at)

### 2. 🏗️ **Modelo Eloquent (Appointments.php)**
- [V] **Constantes e Enums**
  - [V] Entender os status possíveis: `SCHEDULED`, `IN_PROGRESS`, `COMPLETED`, `NO_SHOW`, `CANCELLED`, `RESCHEDULED`
  - [V] Analisar quando cada status é utilizado
  - [V] Compreender as regras de transição entre status

- [V] **Relacionamentos**
  - [V] `doctor()` - belongsTo Doctor
  - [V] `patient()` - belongsTo Patient
  - [V] `logs()` - hasMany AppointmentLog
  - [V] `prescriptions()` - hasMany Prescription
  - [V] `diagnoses()` - hasMany Diagnosis
  - [V] `examinations()` - hasMany Examination
  - [V] `clinicalNotes()` - hasMany ClinicalNote
  - [V] `medicalCertificates()` - hasMany MedicalCertificate
  - [V] `vitalSigns()` - hasMany VitalSign
  - [V] `medicalDocuments()` - hasMany MedicalDocument
  - [V] Entender como os relacionamentos funcionam no contexto do sistema

- [V] **Scopes (Filtros)**
  - [V] `scheduled()` - consultas agendadas
  - [V] `inProgress()` - consultas em andamento
  - [V] `completed()` - consultas finalizadas
  - [V] `cancelled()` - consultas canceladas
  - [V] `byDoctor($doctorId)` - consultas de um médico específico
  - [V] `byPatient($patientId)` - consultas de um paciente específico
  - [V] `today()` - consultas do dia atual
  - [V] `thisWeek()` - consultas da semana atual
  - [V] `upcoming()` - consultas futuras
  - [V] `past()` - consultas passadas
  - [V] `byDateRange($start, $end)` - consultas em período específico

- [V] **Accessors (Getters)**
  - [V] `duration` - duração em minutos
  - [V] `formatted_duration` - duração formatada (ex: "1h 30min")
  - [V] `is_upcoming` - se é uma consulta futura
  - [V] `is_past` - se é uma consulta passada
  - [V] `is_active` - se está em andamento
  - [V] `can_be_started` - se pode ser iniciada
  - [V] `can_be_cancelled` - se pode ser cancelada

- [ ] **Mutators (Setters)**
  - [ ] `setScheduledAtAttribute()` - conversão automática para Carbon
  - [ ] `setStartedAtAttribute()` - conversão automática para Carbon
  - [ ] `setEndedAtAttribute()` - conversão automática para Carbon

- [V] **Métodos de Negócio**
  - [V] `start()` - iniciar consulta (com validações)
  - [V] `end()` - finalizar consulta
  - [V] `cancel($reason)` - cancelar consulta (com validações)
  - [V] `markAsNoShow()` - marcar como não compareceu
  - [V] `reschedule($newDateTime)` - reagendar consulta
  - [V] `generateAccessCode()` - gerar código único de acesso

- [V] **Boot Method**
  - [V] Entender a configuração automática na criação
  - [V] Geração automática de access_code
  - [V] Definição automática de status padrão

### 3. 🧪 **Testes Unitários**
- [V] **Estrutura dos Testes**
  - [V] Entender o uso de `RefreshDatabase`
  - [V] Setup de dados de teste com factories
  - [V] Padrões de nomenclatura dos testes

- [ ] **Cenários Testados**
  - [V] Criação de appointments
  - [V] Geração de códigos únicos
  - [V] Iniciar consultas (sucesso e falha)
  - [V] Finalizar consultas
  - [V] Cancelamento (sucesso e falha)
  - [V] Reagendamento
  - [V] Marcar como não compareceu
  - [V] Cálculo de duração
  - [V] Relacionamentos
  - [V] Scopes e filtros
  - [V] Accessors

- [ ] **Assertions Utilizadas**
  - [ ] `assertInstanceOf()` - verificar tipo
  - [ ] `assertEquals()` - verificar igualdade
  - [ ] `assertTrue()` / `assertFalse()` - verificar booleanos
  - [ ] `assertNotNull()` - verificar não nulo
  - [ ] `assertStringContainsString()` - verificar conteúdo de string
  - [ ] `assertNotEquals()` - verificar diferença

### 4. 🏭 **Factories**
- [V] **DoctorFactory**
  - [V] Estrutura dos dados gerados
  - [V] Relacionamento com User
  - [V] Geração de CRM único
  - [V] Criação de biografia fake

- [V] **PatientFactory**
  - [V] Dados completos do paciente
  - [V] Relacionamento com User
  - [V] Geração de dados médicos fake
  - [V] Validação de campos obrigatórios

### 5. 🔄 **Fluxo de Negócio**
- [V] **Ciclo de Vida de uma Consulta**
  1. [V] Criação (status: SCHEDULED)
  2. [V] Início (status: IN_PROGRESS)
  3. [V] Finalização (status: COMPLETED)
  4. [V] Alternativas: CANCEL, NO_SHOW, RESCHEDULED

- [V] **Regras de Negócio**
  - [V] Consulta só pode ser iniciada até 15 minutos antes do horário
  - [V] Consulta só pode ser cancelada até 2 horas antes do horário
  - [V] Código de acesso é único e gerado automaticamente
  - [V] Duração é calculada automaticamente

### 6. 🎨 **Padrões de Código**
- [V] **Laravel Conventions**
  - [V] Nomenclatura de classes e métodos
  - [V] Uso de traits (HasUuids, SoftDeletes, HasFactory)
  - [V] Estrutura de relacionamentos
  - [V] Padrões de scopes

- [V] **SOLID Principles**
  - [V] Single Responsibility: cada método tem uma responsabilidade
  - [V] Open/Closed: extensível sem modificação
  - [V] Liskov Substitution: herança adequada
  - [V] Interface Segregation: interfaces específicas
  - [V] Dependency Inversion: dependências injetadas

### 7. 🚀 **Próximos Passos**
- [ ] **Integração com Frontend**
  - [ ] Criar controllers para CRUD
  - [ ] Implementar validações de request
  - [ ] Criar rotas da API
  - [ ] Desenvolver componentes Vue.js

- [ ] **Funcionalidades Adicionais**
  - [ ] Sistema de notificações
  - [ ] Integração com plataformas de vídeo
  - [ ] Sistema de logs detalhado
  - [ ] Relatórios e analytics

---

## 📖 **Recursos para Estudo**

### Documentação Oficial
- [Laravel Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Laravel Migrations](https://laravel.com/docs/migrations)
- [Laravel Factories](https://laravel.com/docs/eloquent-factories)
- [Laravel Testing](https://laravel.com/docs/testing)

### Conceitos Importantes
- **UUID vs Auto-increment**: Vantagens do UUID para sistemas distribuídos
- **Soft Deletes**: Exclusão lógica vs física
- **Carbon**: Manipulação de datas e horários
- **JSON Columns**: Armazenamento de dados estruturados
- **Database Indexing**: Otimização de consultas

### Boas Práticas
- **Naming Conventions**: Padrões de nomenclatura
- **Code Organization**: Estrutura de arquivos
- **Error Handling**: Tratamento de erros
- **Performance**: Otimizações de banco de dados

---

## ✅ **Como Marcar como Concluído**

Para cada item estudado:
1. Leia o código correspondente
2. Execute os testes para entender o comportamento
3. Experimente criar/modificar dados
4. Marque o checkbox `[x]` quando compreender completamente

---

## 🎯 **Objetivos de Aprendizado**

Ao final deste estudo, você deve ser capaz de:
- [ ] Explicar a estrutura completa da tabela appointments
- [ ] Entender todos os relacionamentos e suas implicações
- [ ] Implementar novos métodos de negócio seguindo os padrões
- [ ] Criar testes unitários para novas funcionalidades
- [ ] Aplicar os mesmos padrões em outras partes do sistema
- [ ] Debugar problemas relacionados a appointments
- [ ] Otimizar consultas e performance

---

**Data de Criação:** 10 de Setembro de 2025  
**Última Atualização:** Janeiro 2025  
**Status:** ✅ Implementado com integração completa de prontuários
