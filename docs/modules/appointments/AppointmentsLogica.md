## Fluxo básico

### Criar agendamento
- Um paciente ou sistema administrativo cria um agendamento futuro, definindo doctor_id, patient_id, scheduled_at, possivelmente selecionando uma conference_platform_id ou definindo platform. O status inicial será scheduled. O sistema gera um access_code único.

### Notificação / confirmação
- Pode haver envio de notificações ou convites para paciente e médico, contendo informações como data/hora, plataforma, código de acesso, link de conferência (se aplicável). Armazenar metadata para guardar dados extras, por exemplo: duração estimada, tipo de consulta, etc.

### Monitoramento / início
- No horário do agendamento, o sistema espera que o médico/paciente entrem. Quando a consulta inicia, pode-se registrar started_at e mudar status para in_progress.

### Finalização ou interrupção
- Quando termina, registrar ended_at e mudar status para completed. Se não aparecerem (paciente ou médico), pode-se marcar status = no_show. Se cancelar ou reagendar antes da data, status = cancelled ou rescheduled, talvez agendando novo scheduled_at.

### Pós-processamento
- Se houve vídeo, salvar video_recording_url. Anotações médicas em notes. Talvez armazenar outros dados em metadata. Histórico de criação, modificações, e deleção lógica via softDeletes se precisar “excluir” sem perder registro.

### Consultas / relatórios etc.
- Consultas por médico, paciente, status, datas, extrair relatórios: quantos “no_show”, quantas concluídas, duração média (via started_at e ended_at), etc.

## Casos especiais

### Reschedule
- Reschedule: quando se reagenda, o sistema pode criar uma nova entrada ou simplesmente mudar status para rescheduled e alterar scheduled_at. Talvez manter um histórico dos reagendamentos (metadata ou tabela separada).

### Cancelamento antecipado
- Cancelamento antecipado: status = cancelled antes do horário. Talvez permitir cancelar apenas até X horas antes, regras de negócio.

### Não comparecimento
- Não comparecimento: se passar do horário previsto e não for iniciado, sistema poderia ter um job/conferir que automaticamente marque no_show depois de certo tempo de tolerância.

### Vídeo conferência
- Vídeo conferência: se vídeo for parte da lógica, o conference_platform_id indica qual serviço (ex: Zoom, Jitsi, etc.). platform poderia armazenar nome ou tipo. access_code talvez seja usado como senha/link para entrar.

### Segurança
- Segurança: o access_code único poderia servir para autenticação do paciente numa chamada de vídeo ou para validar se ele tem permissão de ver a gravação etc.

## Possíveis melhorias / coisas para checar

- Tempo de tolerância para “no show”: precisa de regras claras quando mudar para no_show. Deve haver job scheduled ou evento automático.
- Validação de conflitos: verificar se o médico já tem outro appointment no mesmo horário, ou sobreposição de horário, etc.
- Fuso horário: timestamp pode causar confusões se usuários estiverem em fusos diferentes; pode ser interessante usar timestampTz ou armazenar timezone ou normalizar para UTC.
- Enum extensível: se for possível que novos status sejam adicionados no futuro, enum no banco dificulta (precisa alterar migration). Talvez usar uma tabela de status ou constante em código.
- Auditoria mais rica: além de timestamps, quem fez alterações de status, reagendamento, cancelamento etc. Talvez registrar em logs ou tabela de histórico.
- Performance: se houver muitos registros, os índices compostos ajudarão, mas consultas pesadas (como metadata) devem ser usadas com cuidado. JSON é útil, mas pode penalizar buscas se usado para filtros frequentes.

## Como o PeerJS entra nessa história

- O PeerJS é um wrapper de WebRTC para facilitar conexões P2P (áudio/vídeo/dados). Ele exige um servidor de sinalização (PeerServer) e, na prática, STUN/TURN para atravessar NAT/firewall; sem TURN, muitos pares não conectam. O PeerJS não “grava” por si — gravação é outra camada sua.
- Em P2P puro, o tráfego flui entre os clientes. Para gravar, você precisa gravar em algum lugar: no cliente, num “bot” que participa da sala, ou num SFU.

### 1) Gravação no cliente (mais simples)

- Use a MediaRecorder API para gravar o MediaStream local (ou o remoto renderizado num video via captureStream()), gerar blobs (WebM/MP4) e fazer upload para seu storage; depois grave a URL em video_recording_url. Vantagens: simples, sem servidor de mídia. Desvantagens: você tem gravação por lado (cada cliente grava o que vê/produz), consumo de CPU/RAM do navegador, falha se a aba fechar. 
- MDN Web Docs
- +2
- MDN Web Docs
- +2

### Fluxo típico:

1. Paciente clica “Iniciar”; médico entra → status = in_progress, started_at setado.
2. Cada lado (ou apenas o “host”) chama new MediaRecorder(remoteOrLocalStream) e inicia recorder.start(…).
3. Ao finalizar, recorder.ondataavailable junta os chunks, faz upload e salva recording_asset_url na appointments.
4. ended_at e status = completed.

### Observação
- Observação: para gravar o remoto, dá para gravar diretamente o MediaStream recebido ou o video.captureStream().

Alguns requisitos essenciais:
[V] **Relacionamentos**
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

[V] **Scopes (Filtros)**
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

[V] **Accessors (Getters)**
  - [V] `duration` - duração em minutos
  - [V] `formatted_duration` - duração formatada (ex: "1h 30min")
  - [V] `is_upcoming` - se é uma consulta futura
  - [V] `is_past` - se é uma consulta passada
  - [V] `is_active` - se está em andamento
  - [V] `can_be_started` - se pode ser iniciada
  - [V] `can_be_cancelled` - se pode ser cancelada

[ ] **Mutators (Setters)**
  - [ ] `setScheduledAtAttribute()` - conversão automática para Carbon
  - [ ] `setStartedAtAttribute()` - conversão automática para Carbon
  - [ ] `setEndedAtAttribute()` - conversão automática para Carbon

[V] **Métodos de Negócio**
  - [V] `start()` - iniciar consulta (com validações)
  - [V] `end()` - finalizar consulta
  - [V] `cancel($reason)` - cancelar consulta (com validações)
  - [V] `markAsNoShow()` - marcar como não compareceu
  - [V] `reschedule($newDateTime)` - reagendar consulta
  - [V] `generateAccessCode()` - gerar código único de acesso

 [V] **Boot Method**
  - [V] Entender a configuração automática na criação
  - [V] Geração automática de access_code
  - [V] Definição automática de status padrão

## Integração com Prontuários Médicos

### Durante a Consulta
- **Acesso ao Prontuário**: Médico pode acessar e editar prontuário durante consulta em andamento
- **Registro em Tempo Real**: Diagnósticos, prescrições, exames, anotações e sinais vitais podem ser registrados durante a consulta
- **Rascunho**: Sistema permite salvar rascunho da consulta antes de finalizar

### Finalização da Consulta
- **Bloqueio de Edição**: Após finalização, dados críticos (diagnóstico, prescrições) são bloqueados
- **Complementos**: Médico pode adicionar complementos após finalização
- **Geração de PDF**: Sistema pode gerar PDF completo da consulta com todo o prontuário

### Integração com Agenda
- **Validação de Disponibilidade**: Consultas são validadas contra slots de disponibilidade do médico
- **Datas Bloqueadas**: Sistema verifica se data está bloqueada antes de permitir agendamento
- **Slots Recorrentes e Específicos**: Sistema considera ambos os tipos de slots na validação

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../index/VisaoGeral.md)** - Índice central da documentação
- **[📊 Matriz de Rastreabilidade](../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../index/Glossario.md)** - Definições de termos técnicos
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de negócio e compliance
- **[🏗️ Arquitetura](../architecture/Arquitetura.md)** - Estrutura e padrões do sistema
- **[🔧 Implementação de Consultas](AppointmentsImplementationStudy.md)** - Detalhes técnicos

### Implementações Relacionadas
- **[Appointment Model](../../app/Models/Appointments.php)** - Entidade de consultas
- **[Appointment Service](../../app/Services/AppointmentService.php)** - Lógica de negócio
- **[Availability Service](../../app/Services/AvailabilityService.php)** - Gestão de disponibilidade
- **[Schedule Service](../../app/Services/Doctor/ScheduleService.php)** - Configuração de agenda
- **[Medical Record Service](../../app/MedicalRecord/Application/Services/MedicalRecordService.php)** - Gestão de prontuários
- **[Appointment Observer](../../app/Observers/AppointmentsObserver.php)** - Eventos automáticos
- **[Appointment Migration](../../database/migrations/2025_09_10_152050_create_appointments_table.php)** - Estrutura do banco
- **[Appointment Tests](../../tests/Unit/AppointmentsTest.php)** - Testes unitários

### Termos do Glossário
- **[Appointment](../index/Glossario.md#a)** - Entidade que representa uma consulta médica
- **[Consulta](../index/Glossario.md#c)** - Sessão médica entre médico e paciente
- **[No-Show](../index/Glossario.md#n)** - Status quando paciente não comparece
- **[Service](../index/Glossario.md#s)** - Camada de lógica de negócio

---

[#] Lógicas faltantes para completar os requisitos de Appointments

1) Atributos computados e regras de negócio removidos da Model

- `getIsUpcomingAttribute()`, `getIsPastAttribute()`, `getIsActiveAttribute()`
  - Descrição: verificações de estado e tempo (futuro, passado, ativo)
  - Realocação: mover como métodos utilitários no `App\Services\AppointmentService` (ex.: `isUpcoming`, `isPast`, `isActive`) OU mantê-los como consultas/uso direto de status nas camadas superiores. Não são responsabilidades centrais da Model.

- `getCanBeStartedAttribute()`, `getCanBeCancelledAttribute()`
  - Descrição: validações de regras de negócio baseadas em horário atual e status
  - Realocação: `App\Services\AppointmentService` como `canBeStarted()` e `canBeCancelled()` (implementados)

- Métodos de negócio `start()`, `end()`, `cancel($reason)`, `markAsNoShow()`, `reschedule($newDateTime)`
  - Descrição: transições de estado com efeitos em dados (timestamps e notas)
  - Realocação: `App\Services\AppointmentService` (implementados como `start`, `end`, `cancel`, `markAsNoShow`, `reschedule`)

- `generateAccessCode()` e lógica de geração automática no boot
  - Descrição: geração de `access_code` único e definição de `status` padrão no evento `creating`
  - Realocação: `App\Observers\AppointmentsObserver` (implementado) e registrado em `App\Providers\AppServiceProvider`

2) O que permanece na Model `Appointments`

- Atributos `$fillable`, `$casts`, constantes de status
- Relacionamentos: `doctor()`, `patient()`, `logs()`
- Scopes de consulta: `scheduled`, `inProgress`, `completed`, `cancelled`, `byDoctor`, `byPatient`, `today`, `thisWeek`, `upcoming`, `past`, `byDateRange`
- Accessors essenciais de apresentação: `duration`, `formatted_duration`
- Mutators de datas: `setScheduledAtAttribute`, `setStartedAtAttribute`, `setEndedAtAttribute`

3) Como utilizar após a refatoração

- Para iniciar/finalizar/cancelar/no-show/reagendar: use `App\Services\AppointmentService`
- Para criação: `AppointmentsObserver` garante `access_code` e `status` padrão
- Para validações de início/cancelamento: use `AppointmentService::canBeStarted()` e `AppointmentService::canBeCancelled()`

[ ] **Assertions Utilizadas**
  - [ ] `assertInstanceOf()` - verificar tipo
  - [ ] `assertEquals()` - verificar igualdade
  - [ ] `assertTrue()` / `assertFalse()` - verificar booleanos
  - [ ] `assertNotNull()` - verificar não nulo
  - [ ] `assertStringContainsString()` - verificar conteúdo de string
  - [ ] `assertNotEquals()` - verificar diferença

🔄 **Fluxo de Negócio**
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