# TASK 11 — Governança e Base do Backend

## Objetivo

Fechar pendências estruturais do backend para deixar o projeto **operável em produção**, com:

- Configuração centralizada
- Autorização consistente (Policies + Broadcast channels)
- Schema de banco alinhado ao roadmap
- Rotinas automáticas de manutenção

---

## Contexto

Existem múltiplas pendências **fundacionais**. Mesmo que as features principais estejam funcionando, sem essa base o sistema fica com:

| Problema | Impacto |
|----------|---------|
| Regras de telemedicina espalhadas no código | Difícil manutenção e auditoria |
| Onboarding/deploy frágil (env incompleto) | Ambiente não reproduzível |
| Controle de acesso inconsistente | Risco LGPD/CFM |
| Migrations atrasadas quebrando módulos | Schema desalinhado |
| Ausência de rotinas de manutenção | Estados incorretos, locks presos, chamadas zumbis |

Esta task é o **fechamento de governança** para evitar que o backend vire um conjunto de features sem sustentação.

---

## Checklist

### T 11.1 — Config centralizada
- [x] Completar `config/telemedicine.php` (timeouts, expiração, regras operacionais)
- [x] Substituir valores hardcoded por `config('telemedicine.*')` no código

### T 11.2 — Onboarding e deploy
- [x] Atualizar `.env.example` (Reverb, Redis, Queue, Storage, Mail)
- [x] Documentar no README: serviços necessários e comandos de setup
- [x] Incluir checklist "subiu local e está ok" no README

### T 11.3 — Policies + Broadcast (ALTA)
- [ ] Criar e registrar `AppointmentPolicy`
- [ ] Criar e registrar `ConversationPolicy`
- [ ] Criar e registrar `MedicalRecordPolicy`
- [ ] Proteger canais `appointments.{uuid}` e `users.{uuid}`
- [ ] Remover `ensureDoctorOwnsAppointment` e similares do `MedicalRecordService`
- [ ] Garantir `authorize()` em FormRequests/Controllers antes de chamar Services

### T 11.4 — Migrations (ALTA)
- [ ] Migration: `appointment_availabilities`
- [ ] Migration: `doctor_availability_exceptions`
- [ ] Migration: `patient_emergency_contacts`
- [ ] Adicionar índices em `status` e `scheduled_at`
- [ ] Adicionar colunas `metadata` JSON e flags de consentimento

### T 11.5 — Rotinas de manutenção
- [ ] Task: marcar no_show
- [ ] Task: encerrar chamadas zumbis
- [ ] Task: limpar locks vencidos (Redis)
- [ ] Task: enviar lembretes (pré-consulta)
- [ ] Configurar `schedule()` no `Kernel.php` com logs/telemetria

---

## T 11.1 — Criar `config/telemedicine.php` (parâmetros centralizados)

### O que fazer

- Criar/completar `config/telemedicine.php` com parâmetros como:
  - Janela permitida para iniciar/entrar na consulta (antes/depois)
  - Duração padrão e tolerâncias
  - Regras operacionais relevantes (timeouts, expiração, etc.)
- Substituir valores hardcoded espalhados no código por `config('telemedicine.*')`

### Por que existe

Regras de telemedicina mudam e precisam ser ajustáveis. Centralizar evita "números mágicos" e torna o sistema auditável e consistente.

### Saída

- Config central única
- Código referenciando `config()` em vez de constantes inline

### Status

> **Status (fev/2025):**  
> - `config/telemedicine.php` foi **completado** com seções para `appointment`, `doctor_defaults`, `availability`, `video_call`, `reminders`, `maintenance`, `medical_records`, `validation`, `notifications`, `auth`, `dashboard`, `messages`, `uploads`, `pagination`, `display`, `consultation_detail`, `patient_history` e `lgpd`.  
> - Todos os pontos mapeados em [TASK_11_MIGRACAO_CONFIG_TELEMEDICINE.md](./TASK_11_MIGRACAO_CONFIG_TELEMEDICINE.md) foram migrados para `config('telemedicine.*')`, incluindo ajustes de revisão (CodeRabbit): chaves mais específicas para janelas de timeline, histórico de paciente, relatórios LGPD, limites de dashboard e paginação de notificações.

---

## T 11.2 — Atualizar `.env.example` + README (onboarding e deploy)

### O que fazer

**`.env.example`**

- Incluir variáveis obrigatórias para rodar:
  - Reverb/WebSocket
  - Redis/cache
  - Queue (driver, conexão)
  - Storage (S3, buckets, URLs, region)
  - Mail (SMTP/provider)

**README**

- Documentar serviços necessários:
  - Redis
  - Queue worker
  - Reverb
  - Scheduler
- Comandos de setup:
  - `php artisan migrate`
  - `php artisan db:seed`
  - `php artisan queue:work`
  - `php artisan schedule:work`
  - `php artisan reverb:start`
- Checklist de "subiu local e está ok"

### Por que existe

Sem isso, o projeto "funciona na máquina de quem fez". Onboarding e homologação quebram por falta de serviços essenciais.

### Saída

- Ambiente reproduzível
- Documentação mínima para subir o backend com confiança

---

## T 11.3 — AuthServiceProvider com Policies + canais de broadcast seguros (ALTA)

### O que fazer

1. **Implementar e registrar Policies no AuthServiceProvider**
   - `AppointmentPolicy`
   - `ConversationPolicy`
   - `MedicalRecordPolicy`

2. **Definir e proteger broadcasting channels**
   - `appointments.{uuid}`
   - `users.{uuid}`
   - Garantir que todos os broadcasts exijam:
     - Usuário autenticado
     - Vínculo com o recurso (anti-IDOR)

3. **Centralizar autorização nas Policies**
   - Evitar guards no Service
   - Integração com [REFACTORING_GUIDE § 1.4 — Segregação de Responsabilidades](../REFACTORING_GUIDE.md)

4. **Segurança de ação**
   - Autorização deve ser feita **antes** do Service (Policies + FormRequests)
   - Remover de Services: verificações como `ensureDoctorOwnsAppointment` do `MedicalRecordService`
   - O Service assume que, se a execução chegou lá, a ação já foi autorizada

5. **Exemplos de uso**
   - Antes de criar receita, exame ou diagnóstico: usar `AppointmentPolicy::createPrescription`, `AppointmentPolicy::requestExamination`, etc., verificando se o usuário é o médico da consulta
   - FormRequests: usar `authorize()` para validar vínculo com o recurso (ex.: `appointment_id` pertence ao médico logado)

### Padrão de execução

| Camada | Responsabilidade |
|--------|------------------|
| Controller | Recebe Request → chama `$this->authorize('...', $recurso)` ou usa FormRequest que já autoriza |
| Service | Não contém lógica de autorização; apenas regras de negócio |

### Por que existe

- Sem Policies e canais protegidos: risco de acesso indevido a consultas, prontuário e mensagens (LGPD/CFM)
- Guards espalhados no Service misturam autorização com regras de negócio, dificultam manutenção e testes
- Autorização centralizada em Policies/FormRequests torna o controle de acesso consistente e auditável

### Saída

- Policies registradas e aplicadas em todos os pontos críticos
- Canais de WebSocket com autorização adequada
- Remoção de `ensureDoctorOwnsAppointment` e similares do `MedicalRecordService`
- Services assumindo que a autorização já foi feita antes da chamada

---

## T 11.4 — Consolidar migrations pendentes (schema e performance) (ALTA)

### O que fazer

Consolidar e aplicar migrations pendentes:

**Tabelas**

- `appointment_availabilities`
- `doctor_availability_exceptions`
- `patient_emergency_contacts`

**Índices**

- `status`
- `scheduled_at`

**Colunas e evoluções**

- `metadata` JSON
- Flags de consentimento

Garantir que o schema final esteja alinhado com os models e casos de uso já existentes.

### Por que existe

Sem migrations consolidadas, o sistema fica "meio implementado": faltam tabelas para features, o banco não responde bem (queries lentas) e falta suporte formal a consentimento.

### Saída

- Banco alinhado ao roadmap
- Performance básica garantida (índices)
- Suporte a metadados/consentimento

---

## T 11.5 — Tasks de manutenção no `Kernel.php` (rotinas operacionais)

### O que fazer

Configurar tasks agendadas (scheduler) para:

| Task | Descrição |
|------|-----------|
| Marcar no_show | Consulta não ocorreu |
| Encerrar chamadas zumbis | Salas ativas sem término |
| Limpar locks vencidos | Redis |
| Enviar lembretes | Pré-consulta |

Garantir:

- Logs/telemetria básica dessas rotinas
- Rotinas idempotentes

### Por que existe

Telemedicina é operação diária. Sem rotinas, o sistema acumula lixo: estados errados, locks presos, chamadas penduradas e pior experiência para o usuário.

### Saída

- Scheduler rodando com rotinas essenciais de saúde do sistema
- `app/Console/Kernel.php` com `schedule()` configurado

---

## Resumo

Essa task existe para deixar o backend **pronto para a vida real**. Ela:

1. Organiza as regras de telemedicina em um lugar só
2. Garante que permissões e canais sejam seguros
3. Coloca o banco no estado correto
4. Cria rotinas automáticas para o sistema não se degradar com o uso diário

---

## Priorização sugerida

| Subtask | Prioridade | Dependências |
|---------|------------|--------------|
| T 11.1 | Média | Nenhuma |
| T 11.2 | Média | Nenhuma |
| T 11.3 | **Alta** | Concluir antes de features sensíveis |
| T 11.4 | **Alta** | Pode bloquear outras features |
| T 11.5 | Média | Após T 11.1 (config) e T 11.4 (schema) |

---

## Referências

- [REFACTORING_GUIDE.md](../REFACTORING_GUIDE.md) — § 1.4 Segregação de Responsabilidades
- [docs/requirements/SystemRules.md](./requirements/SystemRules.md)
- [docs/Pending Issues/CONFORMIDADE_CFM_LGPD.md](./Pending%20Issues/CONFORMIDADE_CFM_LGPD.md)
