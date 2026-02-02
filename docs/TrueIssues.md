# PENDÊNCIAS DO PROJETO - TELEMEDICINA PARA TODOS

**📅 Última Atualização:** 26 de Janeiro de 2026  
**🔍 Versão do Documento:** 1.0  
**📊 Status Geral:** ⚠️ **PARCIALMENTE CONFORME** (80% implementado)

---

## 📑 Índice

1. [Resumo Executivo](#-resumo-executivo)
2. [Pendências Críticas - Conformidade CFM](#-pendências-críticas---conformidade-cfm)
3. [Pendências Técnicas - Sistema](#-pendências-técnicas---sistema)
4. [Pendências Técnicas do Backend](#-7-pendências-técnicas-do-backend-roadmap-de-implementação)
5. [Pendências de Documentação Legal](#-pendências-de-documentação-legal)
6. [Pendências de Infraestrutura](#-pendências-de-infraestrutura)
7. [Revisões de Código Crítico](#-revisões-de-código-crítico)
8. [Tasks de Segurança](#-tasks-de-segurança)
9. [Plano de Ação Prioritizado](#-plano-de-ação-prioritizado)

---

## 🎯 Resumo Executivo

### Status por Categoria

| Categoria | Status | Percentual | Prioridade |
|-----------|--------|-----------|------------|
| **Consentimento do Paciente** | ✅ Conforme | 100% | - |
| **Identificação do Médico** | ✅ Conforme | 100% | - |
| **Prontuário Eletrônico** | ✅ Conforme | 100% | - |
| **Videoconferência Segura** | ⚠️ Parcial | 70% | 🔴 ALTA |
| **Prescrição Digital** | ❌ Pendente | 40% | 🔴 CRÍTICA |
| **Gravação de Sessão** | ⚠️ Parcial | 50% | 🟡 MÉDIA |
| **LGPD** | ✅ Conforme | 95% | - |
| **Documentos Legais (CFM)** | ⚠️ Parcial | 40-50% | 🔴 ALTA |
| **Backend - Implementação Completa** | ⚠️ Parcial | 30-40% | 🔴 ALTA |
| **Segurança - Revisões Críticas** | ⚠️ Parcial | 60-70% | 🔴 ALTA |
| **Segurança - Tasks** | ⚠️ Parcial | 40-50% | 🔴 ALTA |

### Gráfico de Conformidade

```
Sistema de Consentimento       ████████████████████ 100%
Identificação Médica          ████████████████████ 100%
Prontuário Eletrônico         ████████████████████ 100%
Videoconferência              ██████████████░░░░░░  70%
Prescrição Digital            ████████░░░░░░░░░░░░  40%
Gravação de Vídeo             ██████████░░░░░░░░░░  50%
LGPD                          ███████████████████░  95%
Documentos Legais (CFM)       ████████░░░░░░░░░░░░  40%
Backend - Implementação       ████████░░░░░░░░░░░░  35%
Segurança - Revisões          ████████████░░░░░░░░  65%
Segurança - Tasks             ████████░░░░░░░░░░░░  45%
```

---

## 🔴 Pendências Críticas - Conformidade CFM

### 1. Assinatura Digital ICP-Brasil ⚠️ **OBRIGATÓRIO PELO CFM**

**Status:** ❌ **NÃO IMPLEMENTADO**

**Impacto:** 
- ⚠️ **OBRIGATÓRIO PELO CFM** (Art. 8º, Resolução 2.314/2022)
- Sem isso, prescrições e atestados **NÃO TÊM VALIDADE LEGAL**
- **BLOQUEANTE** para emissão de documentos médicos

**Referência Legal:** Art. 8º – Resolução CFM 2.314/2022
> *"Os documentos médicos resultantes de atendimento por telemedicina deverão conter identificação e assinatura do médico."*

#### Status Atual

- ✅ Campos de `signature_hash` e `verification_code` existem em `MedicalCertificate`
- ✅ Geração de PDF implementada
- ❌ **Integração com ICP-Brasil NÃO implementada**
- ❌ **Assinatura digital válida NÃO implementada**
- ❌ Campos de assinatura faltando em `Prescription` model

#### Ações Necessárias

1. **Contratar provedor de certificação digital ICP-Brasil**
   - Opções: Soluti, Certisign, Safeweb, etc.
   - Validar certificado A1 ou A3

2. **Implementar `DigitalSignatureService.php`**
   - Integrar com API de certificação digital
   - Validar certificado antes de emissão de documentos
   - Gerar hash de assinatura
   - Gerar código de verificação único

3. **Atualizar Model `Prescription`**
   - Adicionar campos `signature_hash` e `verification_code`
   - Criar migration para atualizar tabela `prescriptions`

4. **Integrar fluxo de assinatura no frontend**
   - Interface para assinatura digital
   - Validação de certificado
   - Feedback visual de assinatura válida

5. **Validar certificado antes de emissão de documentos**
   - Middleware ou validação no controller
   - Bloquear emissão sem certificado válido

**Estimativa:** 2-3 semanas  
**Prioridade:** 🔴 **CRÍTICA** (Bloqueante)

---

### 2. Sistema de Videoconferência (Gaps Críticos)

**Status:** ⚠️ **70% IMPLEMENTADO**

**Referência Legal:** Art. 9º – Resolução CFM 2.314/2022

#### ✅ Implementações Realizadas

**Frontend:**
- ✅ Estados detalhados da chamada (idle, ringing_out, ringing_in, connecting, in_call, ending, ended, error)
- ✅ Monitoramento de qualidade de rede (latência, largura de banda, perda de pacotes)
- ✅ Indicadores visuais de qualidade com tooltip
- ✅ Timer de duração formatado (MM:SS)
- ✅ Modal de confirmação aprimorado
- ✅ Botão "Chamar Novamente" (disponível por 2 minutos após rejeição)
- ✅ Botão "Reenviar Solicitação" quando não atendida
- ✅ Feedback visual para cada estado (ícones animados, cores, mensagens)
- ✅ Tratamento de rejeições acidentais com callback

**Backend:**
- ✅ Model `VideoCallRoom` (estrutura básica)
- ✅ Model `VideoCallEvent` (estrutura básica)
- ✅ Migration básica
- ✅ Events: `VideoCallRoomCreated`, `VideoCallRoomExpired`, `VideoCallUserJoined`, `VideoCallUserLeft`, `RequestVideoCall`, `RequestVideoCallStatus`
- ✅ Jobs: `ExpireVideoCallRooms`, `CleanupOldVideoCallEvents`, `UpdateAppointmentFromRoom`

#### ❌ Pendências Críticas

| Item | Status | Prioridade | Impacto |
|------|--------|-----------|---------|
| **Amarração de chamada ao agendamento** (`appointment_id` obrigatório) | ❌ | 🔴 ALTA | Segurança e rastreabilidade |
| **AppointmentPolicy implementada e aplicada** | ❌ | 🔴 ALTA | Controle de acesso |
| **Locks de concorrência (Redis)** | ❌ | 🔴 ALTA | Evitar múltiplas chamadas simultâneas |
| **Configuração de TURN server** | ❌ | 🔴 ALTA | NAT traversal (conectividade) |
| **Campos de lifecycle no appointments** (`started_at`, `ended_at`) | ✅ | - | Já implementado |
| **Metadados e auditoria completos** | ⚠️ | 🟡 MÉDIA | Rastreabilidade |
| **Rate limiting e anti-spam** | ⚠️ | 🟡 MÉDIA | Segurança |
| **Canais de broadcast por consulta** | ⚠️ | 🟡 MÉDIA | Comunicação em tempo real |
| **Eventos padronizados com broadcastWith()** | ⚠️ | 🟡 MÉDIA | Consistência |
| **Endpoints REST completos** | ⚠️ | 🟡 MÉDIA | API completa |
| **Regras de janela e timezone** | ❌ | 🟡 MÉDIA | Validação de horários |
| **Cancelamento e timeout** | ⚠️ | 🔴 ALTA | UX e segurança |
| **Máquina de estados no frontend** | ✅ | - | Já implementado |
| **Listeners únicos e contexto** | ⚠️ | 🟡 MÉDIA | Performance |
| **Integração completa com Echo** | ⚠️ | 🟡 MÉDIA | Broadcasting |
| **Conectividade e TURN configurado** | ❌ | 🔴 ALTA | Funcionalidade crítica |
| **Logs estruturados** | ⚠️ | 🟢 BAIXA | Monitoramento |
| **Métricas e KPIs** | ❌ | 🟢 BAIXA | Analytics |
| **Testes completos end-to-end** | ❌ | 🔴 ALTA | Qualidade |
| **Jobs/Cron para no_show** | ❌ | 🟡 MÉDIA | Automação |
| **Degradação elegante** | ⚠️ | 🟡 MÉDIA | UX |

#### Ações Necessárias

1. **Amarração obrigatória com Appointment**
   - Adicionar `appointment_id` obrigatório em `VideoCallRoom`
   - Migration para atualizar tabela
   - Validação no controller

2. **Implementar AppointmentPolicy**
   - Criar `AppointmentPolicy.php`
   - Aplicar em todos os endpoints de videoconferência
   - Validar acesso médico/paciente

3. **Locks de Concorrência (Redis)**
   - Implementar locks para evitar múltiplas chamadas
   - Usar `Redis::lock()` ou `Cache::lock()`
   - Timeout apropriado

4. **Configurar TURN Server**
   - Configurar servidor TURN (ex: Coturn, Twilio)
   - Adicionar configuração no frontend WebRTC
   - Testar NAT traversal

5. **Testes End-to-End**
   - Testes de integração completos
   - Testes de conectividade
   - Testes de falhas e recuperação

**Estimativa:** 3-4 semanas  
**Prioridade:** 🔴 **ALTA**

---

### 3. Gravação de Sessão (Funcionalidade Completa)

**Status:** ⚠️ **50% IMPLEMENTADO**

**Referência Legal:** Art. 9º, § único – Resolução CFM 2.314/2022
> *"A gravação da teleconsulta somente será realizada com autorização prévia e expressa do paciente."*

#### ✅ Implementações Parciais

**Backend:**
- ✅ Campo `video_recording_url` no model `Appointments`
- ✅ Sistema de Consentimento implementado (`Consent::TYPE_VIDEO_RECORDING`)
- ✅ Registro de IP, user agent, data/hora

#### ❌ Não Implementado

| Item | Status | Prioridade |
|------|--------|-----------|
| **MediaRecorder API (gravação)** | ❌ | 🟢 BAIXA |
| **Upload para storage** | ❌ | 🟢 BAIXA |
| **Controle de acesso às gravações** | ❌ | 🟡 MÉDIA |
| **Consentimento específico UI** | ⚠️ | 🔴 ALTA (se implementar) |
| **Política de retenção automatizada** | ❌ | 🟡 MÉDIA |
| **Player de vídeo** | ❌ | 🟢 BAIXA |
| **Download com permissão** | ❌ | 🟢 BAIXA |

#### Ações Necessárias (Se Decidir Implementar)

1. **Implementar MediaRecorder API**
   - Captura de vídeo no frontend
   - Controle de início/fim de gravação

2. **Upload para Storage Seguro**
   - Upload para S3 ou storage criptografado
   - URLs temporárias e seguras

3. **Interface de Consentimento Específico**
   - Modal de consentimento para gravação
   - Explicação clara de finalidade e prazo de retenção
   - Direito de recusar gravação

4. **Política de Retenção Automatizada**
   - Job para excluir gravações após prazo
   - Configuração de prazo de retenção

5. **Player de Vídeo**
   - Interface para visualização de gravações
   - Controle de acesso (apenas médico e paciente da consulta)

**Estimativa:** 2 semanas  
**Prioridade:** 🟡 **MÉDIA** (Opcional - CFM permite mas não exige)

---

### 4. Versionamento de Prontuário

**Status:** ⚠️ **PARCIAL**

#### ✅ Implementações Parciais

- ✅ Soft delete obrigatório em todos os models clínicos
- ✅ Logs de auditoria (`MedicalRecordAuditLog`)
- ⚠️ Versionamento explícito de alterações clínicas (parcial)

#### ❌ Pendências

- ❌ Versionamento explícito de alterações clínicas
- ❌ Histórico de edições com diff
- ❌ Interface para visualizar histórico de alterações

#### Ações Necessárias

1. **Implementar Versionamento Explícito**
   - Tabela de versões de registros clínicos
   - Captura de alterações com diff
   - Timestamps e usuário responsável

2. **Histórico de Edições com Diff**
   - Visualização de alterações
   - Comparação entre versões
   - Interface de timeline

**Estimativa:** 1 semana  
**Prioridade:** 🟡 **MÉDIA**

---

## 🟡 Pendências Técnicas - Sistema

### 5. AppointmentPolicy

**Status:** ❌ **NÃO IMPLEMENTADA**

**Impacto:** Controle de acesso inadequado para appointments

#### Ações Necessárias

1. **Criar `AppointmentPolicy.php`**
   - Método `view()` - médico/paciente podem ver seus appointments
   - Método `update()` - apenas médico pode atualizar
   - Método `delete()` - apenas médico pode deletar
   - Método `start()` - apenas médico pode iniciar
   - Método `end()` - apenas médico pode finalizar

2. **Aplicar Policy nos Controllers**
   - `AppointmentController`
   - `VideoCallController` (quando implementado)
   - Middleware de autorização

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

### 6. Métricas e Monitoramento de Videoconferência

**Status:** ❌ **NÃO IMPLEMENTADO**

#### Pendências

- ❌ Logs estruturados completos
- ❌ Métricas de qualidade de chamada
- ❌ Dashboard de KPIs
- ❌ Alertas de problemas de conectividade

#### Ações Necessárias

1. **Logs Estruturados**
   - Formato JSON para logs
   - Campos: timestamp, user_id, appointment_id, event_type, metadata

2. **Métricas de Qualidade**
   - Latência média
   - Perda de pacotes
   - Largura de banda
   - Taxa de sucesso de chamadas

3. **Dashboard de KPIs**
   - Total de chamadas
   - Taxa de sucesso
   - Tempo médio de chamada
   - Problemas de conectividade

**Estimativa:** 1-2 semanas  
**Prioridade:** 🟢 **BAIXA**

---

### 7. Pendências Técnicas do Backend (Roadmap de Implementação)

**Status:** ❌ **MÚLTIPLAS PENDÊNCIAS**

**Fonte:** `back-end` (Roadmap de Implementação)

#### 7.1 Core, Infraestrutura e Governança

| Item | Status | Prioridade |
|------|--------|-----------|
| **Config `telemedicine.php`** | ❌ | 🟡 MÉDIA |
| **Atualizar `.env.example` e `README`** | ❌ | 🟡 MÉDIA |
| **AuthServiceProvider com Policies** | ❌ | 🔴 ALTA |
| **Migrations pendentes** | ❌ | 🔴 ALTA |
| **Tasks de manutenção (Kernel.php)** | ❌ | 🟡 MÉDIA |

**Ações Necessárias:**
1. Criar `config/telemedicine.php` com parâmetros de janela da consulta
2. Ajustar `.env.example` com variáveis obrigatórias (Reverb, Redis, fila, storage)
3. Implementar `AuthServiceProvider` registrando:
   - `AppointmentPolicy`
   - `ConversationPolicy`
   - `MedicalRecordPolicy`
   - Broadcasting channels `appointments.{uuid}` / `users.{uuid}`
4. Consolidar migrations pendentes:
   - Tabelas: `appointment_availabilities`, `doctor_availability_exceptions`, `patient_emergency_contacts`
   - Índices: `status`, `scheduled_at`
   - Colunas: `metadata` JSON, consent flags
5. Configurar tasks de manutenção:
   - Jobs para marcar `no_show`
   - Finalizar chamadas zumbis
   - Limpar locks
   - Enviar lembretes

**Estimativa:** 1-2 semanas  
**Prioridade:** 🔴 **ALTA**

---

#### 7.2 Usuários, Perfis Médicos e Catálogo

| Item | Status | Prioridade |
|------|--------|-----------|
| **Revisar Models (User, Doctor, Patient)** | ⚠️ | 🟡 MÉDIA |
| **CRUD de perfis (Doctors)** | ❌ | 🔴 ALTA |
| **CRUD de perfis (Patients)** | ❌ | 🔴 ALTA |
| **Segunda etapa de autenticação (2FA)** | ❌ | 🟡 MÉDIA |
| **API de busca de médicos** | ❌ | 🔴 ALTA |
| **Seeds e factories ampliadas** | ❌ | 🟢 BAIXA |

**Ações Necessárias:**
1. Revisar models para garantir casts/computed attributes alinhados às regras
2. Implementar endpoints CRUD para Doctors (biografia, CRM, especializações, agenda, fee)
3. Implementar endpoints CRUD para Patients (dados clínicos, consentimento, contatos de emergência)
4. Configurar 2FA para pacientes:
   - Tabela/colunas para método (OTP via email/app, token backup)
   - Endpoints para habilitar/desabilitar e verificar códigos
   - Frontend para fluxo de ativação
   - Middleware para exigir segundo fator em rotas sensíveis
5. API de busca de médicos: filtro por especialização, preço, avaliação, localização
6. Criar seeds e factories ampliadas

**Estimativa:** 2-3 semanas  
**Prioridade:** 🔴 **ALTA**

---

#### 7.3 Agenda e Consultas (Appointments)

| Item | Status | Prioridade |
|------|--------|-----------|
| **AppointmentsController completo** | ⚠️ | 🔴 ALTA |
| **AppointmentService ampliado** | ⚠️ | 🔴 ALTA |
| **AppointmentsObserver** | ❌ | 🔴 ALTA |
| **AppointmentPolicy** | ❌ | 🔴 ALTA |
| **Scheduling de disponibilidades** | ❌ | 🔴 ALTA |

**Ações Necessárias:**
1. Implementar `AppointmentsController` completo:
   - Listagens paginadas por tipo de usuário
   - Rotas POST/PUT/DELETE para criar, reagendar, cancelar, confirmar
   - Validação com `StoreAppointmentRequest`, `UpdateAppointmentRequest`
2. Ampliar `AppointmentService`:
   - Regras de conflito de horário
   - Bloqueio por status
   - Anotação de motivos
   - Geração de logs (`AppointmentLog`)
3. Registrar `AppointmentsObserver`:
   - Gerar `access_code`
   - Preencher `metadata` (callId, preferências de mídia)
   - Disparar eventos de domínio
4. Criar `AppointmentPolicy` (permissões request/accept/start/end/cancel)
5. Implementar scheduling de disponibilidades:
   - CRUD de blocos (`appointment_availabilities`)
   - Rotina para materializar slots livres
   - Respeitar bloqueios/feriados/exceções
   - Endpoints REST/JSON para auto-complete de horários

**Estimativa:** 3-4 semanas  
**Prioridade:** 🔴 **ALTA**

---

#### 7.4 Videoconferência (Reimplementação Total)

| Item | Status | Prioridade |
|------|--------|-----------|
| **VideoCallController dedicado** | ❌ | 🔴 ALTA |
| **Armazenar callId + peer IDs** | ❌ | 🔴 ALTA |
| **Eventos nomeados padronizados** | ⚠️ | 🔴 ALTA |
| **Locking com Redis** | ❌ | 🔴 ALTA |
| **Canais de broadcast** | ⚠️ | 🔴 ALTA |
| **Integração MediaRecorder (opcional)** | ❌ | 🟡 MÉDIA |

**Ações Necessárias:**
1. Criar `VideoCallController` com endpoints:
   - `POST /appointments/{appointment}/call/request`
   - `POST /appointments/{appointment}/call/accept`
   - `POST /appointments/{appointment}/call/start`
   - `POST /appointments/{appointment}/call/end`
   - (Opcional) `/cancel` e `/busy`
2. Armazenar `callId` + mapas de peer IDs em `appointments.metadata`
3. Criar tabela `appointment_call_events` para auditoria
4. Substituir eventos por eventos nomeados com `broadcastWith`
5. Implementar locking com Redis (`Cache::lock("appointment:{$id}:call")`)
6. Configurar `routes/channels.php` com canais privados
7. Ajustar frontend para consumir novo fluxo
8. Implementar integração MediaRecorder opcional (upload para S3/MinIO)
9. Criar testes feature cobrindo request/accept/start/end

**Estimativa:** 3-4 semanas  
**Prioridade:** 🔴 **ALTA**

---

#### 7.5 Mensageria em Tempo Real

| Item | Status | Prioridade |
|------|--------|-----------|
| **Modelos (Conversation, Message)** | ❌ | 🟡 MÉDIA |
| **MessagingService** | ❌ | 🟡 MÉDIA |
| **Endpoints REST** | ❌ | 🟡 MÉDIA |
| **ConversationPolicy** | ❌ | 🟡 MÉDIA |
| **Integração com WebSockets** | ❌ | 🟡 MÉDIA |
| **Anexos e validação** | ❌ | 🟡 MÉDIA |
| **Jobs para notificações** | ❌ | 🟡 MÉDIA |

**Ações Necessárias:**
1. Projetar modelos `Conversation`, `ConversationParticipant`, `Message`
2. Criar `MessagingService`:
   - Abrir conversas entre médico/paciente
   - Publicar mensagens via eventos e WebSockets
   - Marcar recebimento/leitura
3. Expor endpoints REST:
   - Listagem de conversas
   - Criação de conversa
   - Envio de mensagem
   - Atualização de leitura
4. Aplicar `ConversationPolicy`
5. Adaptar componentes Vue para consumir dados reais
6. Implementar migração para anexos
7. Adicionar jobs para notificar via email/push

**Estimativa:** 2-3 semanas  
**Prioridade:** 🟡 **MÉDIA**

---

#### 7.6 Prontuário, Documentos e Prescrições

| Item | Status | Prioridade |
|------|--------|-----------|
| **Módulo MedicalRecord** | ⚠️ | 🔴 ALTA |
| **Prescription completo** | ⚠️ | 🔴 ALTA |
| **Document (file, type, expiry)** | ❌ | 🟡 MÉDIA |
| **Exportação em PDF** | ❌ | 🟡 MÉDIA |
| **Consentimento LGPD antes de exibir** | ⚠️ | 🔴 ALTA |

**Ações Necessárias:**
1. Criar módulo `MedicalRecord`:
   - Tabela `medical_records`
   - API para pacientes lerem registros
   - API para médicos criarem/atualizarem
   - UI atualizada com dados reais
2. Implementar `Prescription` completo (medications, dosage, instructions, signature metadata)
3. Implementar `Document` (file, type, expiry)
4. Rotas para upload/download com política de segurança
5. Fornecer exportação em PDF (queues para gerar prontuário consolidado)
6. Garantir consentimento LGPD registrado antes de exibir dados

**Estimativa:** 2-3 semanas  
**Prioridade:** 🔴 **ALTA**

---

#### 7.7 Notificações, Comunicação e Observabilidade

| Item | Status | Prioridade |
|------|--------|-----------|
| **Notification classes** | ❌ | 🟡 MÉDIA |
| **Integração com broadcast** | ⚠️ | 🟡 MÉDIA |
| **Logs estruturados** | ⚠️ | 🟡 MÉDIA |
| **Métricas (Prometheus/Horizon)** | ❌ | 🟢 BAIXA |
| **Backups automatizados** | ❌ | 🟡 MÉDIA |

**Ações Necessárias:**
1. Configurar `Notification` classes:
   - Lembrete de consulta (>24h e >1h antes)
   - Cancelamento/reagendamento
   - Mensagens não lidas
   - Novas prescrições
2. Integrar com canal broadcast `users.{id}` para toasts em tempo real
3. Adicionar logs estruturados (Monolog channels dedicados)
4. Instrumentar métricas (Prometheus ou Laravel Horizon)
5. Configurar backups automatizados (mysqldump + storage)

**Estimativa:** 1-2 semanas  
**Prioridade:** 🟡 **MÉDIA**

---

#### 7.8 Qualidade, Segurança e Testes

| Item | Status | Prioridade |
|------|--------|-----------|
| **Expandir suíte de testes** | ❌ | 🔴 ALTA |
| **Validações rigorosas (FormRequest)** | ⚠️ | 🔴 ALTA |
| **Rate limiting** | ⚠️ | 🔴 ALTA |
| **Middleware customizados** | ❌ | 🔴 ALTA |
| **CI/CD (larastan, pint, GitHub Actions)** | ❌ | 🟡 MÉDIA |
| **Pentest básico (OWASP)** | ❌ | 🟡 MÉDIA |

**Ações Necessárias:**
1. Expandir suíte de testes:
   - Feature tests para roteamento por perfil, fluxo completo
   - Unit tests para services
   - Tests de policies
2. Implantar validações rigorosas (`FormRequest`) em todos endpoints
3. Aplicar rate limiting (`throttle`) em video call request e messaging
4. Revisar middleware:
   - Criar `EnsureDoctorActive`
   - Criar `EnsurePatientCompletedProfile`
   - Criar `EnsureConsentAccepted`
5. Configurar `larastan`/`phpstan` e `pint` no pipeline CI
6. Adicionar GitHub Actions para rodar testes e análise estática
7. Planejar pentest básico (OWASP top 10)

**Estimativa:** 2-3 semanas  
**Prioridade:** 🔴 **ALTA**

---

## 📄 Pendências de Documentação Legal

### 7. Elementos Faltando na Política de Privacidade (CFM)

**Status:** ⚠️ **PARCIAL** (100% LGPD, 40% CFM)

#### ❌ Elementos Obrigatórios CFM Faltando

| Elemento Obrigatório CFM | Status | Referência | Prioridade |
|--------------------------|--------|------------|------------|
| **Consentimento Informado para Telemedicina** | ❌ | Art. 4º e 5º | 🔴 ALTA |
| **Limitações da Telemedicina** | ⚠️ | Art. 3º | 🔴 ALTA |
| **Direitos e Deveres do Paciente em Telemedicina** | ❌ | Art. 6º | 🔴 ALTA |
| **Informações sobre Prontuário Eletrônico** | ❌ | Art. 7º | 🔴 ALTA |
| **Guarda e Retenção de Dados Clínicos** | ⚠️ | Art. 7º, §2º | 🔴 ALTA |
| **Informações sobre Gravação de Consultas** | ❌ | Art. 9º, parágrafo único | 🟡 MÉDIA |
| **Sigilo Médico e Confidencialidade** | ⚠️ | Art. 73 do CEM | 🔴 ALTA |
| **Situações de Emergência** | ⚠️ | Orientação geral | 🟡 MÉDIA |
| **Informações sobre Prescrição Digital** | ❌ | Art. 8º | 🔴 ALTA |
| **Responsabilidade Médica** | ⚠️ | Art. 6º, §1º | 🔴 ALTA |

#### Ações Necessárias

1. **Adicionar Seção "Consentimento para Telemedicina"**
   - Explicação sobre natureza remota do atendimento
   - Limitações técnicas
   - Direitos do paciente
   - Alternativas presenciais
   - Riscos e benefícios

2. **Adicionar Seção "Prontuário Eletrônico"**
   - Como é armazenado e protegido
   - Tempo de retenção (mínimo 20 anos)
   - Direitos de acesso
   - Imutabilidade e auditoria

3. **Adicionar Seção "Gravação de Consultas"**
   - Consentimento específico
   - Finalidade e prazo de retenção
   - Direito de recusa

4. **Adicionar Seção "Documentos Médicos Digitais"**
   - Validade legal
   - Assinatura digital ICP-Brasil
   - Como verificar autenticidade

5. **Expandir Seção "Protocolo de Emergências"**
   - SAMU 192, Bombeiros 193
   - Quando NÃO usar telemedicina
   - Redirecionamento urgente

6. **Adicionar Seção "Responsabilidades do Médico"**
   - Identificação obrigatória (CRM + UF)
   - Responsabilidade técnica
   - Sigilo profissional

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

### 8. Elementos Faltando nos Termos de Serviço (CFM)

**Status:** ⚠️ **PARCIAL** (95% LGPD, 50% CFM)

#### ❌ Elementos Obrigatórios CFM Faltando

| Elemento Obrigatório CFM | Status | Referência | Prioridade |
|--------------------------|--------|------------|------------|
| **Termo de Consentimento Livre e Esclarecido** | ❌ | Art. 4º e 5º, Res. 2.314/2022 | 🔴 ALTA |
| **Identificação do Médico** | ⚠️ | Art. 6º, §1º | 🔴 ALTA |
| **Limitações Técnicas da Telemedicina** | ⚠️ | Art. 3º | 🔴 ALTA |
| **Protocolo de Emergências** | ❌ | Orientação geral CFM | 🟡 MÉDIA |
| **Garantias de Sigilo Médico** | ⚠️ | Art. 73 do CEM | 🔴 ALTA |
| **Informações sobre Prontuário** | ❌ | Art. 7º | 🔴 ALTA |
| **Consentimento para Gravação** | ❌ | Art. 9º, parágrafo único | 🟡 MÉDIA |
| **Responsabilidade Médica** | ⚠️ | Art. 6º | 🔴 ALTA |
| **Validade de Documentos Digitais** | ❌ | Art. 8º | 🔴 ALTA |

#### Ações Necessárias

1. **Adicionar Seção "Documentos Médicos Digitais"**
   - Validade legal
   - Assinatura digital ICP-Brasil
   - Como verificar autenticidade

2. **Expandir Seção "Protocolo de Emergências"**
   - SAMU 192, Bombeiros 193
   - Quando NÃO usar telemedicina
   - Redirecionamento urgente

3. **Adicionar Seção "Responsabilidades do Profissional Médico"**
   - Identificação obrigatória (CRM + UF)
   - Responsabilidade técnica
   - Sigilo profissional

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

## 🔧 Pendências de Infraestrutura

### 9. Migração MySQL → PostgreSQL

**Status:** 📋 **GUIA DISPONÍVEL** (não é pendência crítica, mas há guia de transição)

**Arquivo:** `docs/Pending Issues/TransitionPostgreeSQL.md`

**Observação:** Esta é uma transição opcional. O sistema funciona com MySQL, mas há um guia completo caso decida migrar.

**Pendência Técnica Identificada:**
- ❌ Query com `DATE_ADD` em `AppointmentService.php` (linha 338) precisa ser corrigida para PostgreSQL

**Ação Necessária (se migrar):**
- Corrigir query `DATE_ADD` para sintaxe PostgreSQL ou usar cálculo no PHP

**Prioridade:** 🟢 **BAIXA** (Opcional)

---

### 10. Migração Database → Redis

**Status:** 📋 **GUIA DISPONÍVEL** (não é pendência crítica, mas há guia de transição)

**Arquivo:** `docs/Pending Issues/TransitionRedis.md`

**Observação:** Esta é uma transição recomendada para melhor performance. O sistema funciona com database, mas Redis é recomendado para produção.

**Benefícios:**
- Performance superior para cache, sessions e queue
- Escalabilidade
- Compatibilidade com AWS ElastiCache

**Prioridade:** 🟡 **MÉDIA** (Recomendado para produção)

---

## 🔍 Revisões de Código Crítico

### 1. SQL Injection - Query com DATE_ADD

**Status:** ⚠️ **VULNERABILIDADE POTENCIAL**

**Arquivo:** `app/Services/AppointmentService.php`  
**Linha:** 338

**Problema:**
- Query usa `whereRaw` com `DATE_ADD` que é específico do MySQL
- Se migrar para PostgreSQL, query falhará
- Embora use bindings (`?`), a sintaxe SQL é específica do banco

**Código Atual:**
```php
->whereRaw('DATE_ADD(scheduled_at, INTERVAL ? MINUTE) > ?', [
    $duration,
    $startTime->toDateTimeString()
]);
```

**Risco:** 
- 🔴 **ALTO** - Quebra de funcionalidade ao migrar para PostgreSQL
- 🟡 **MÉDIO** - Dependência de sintaxe específica do banco

**Ação Necessária:**
1. Refatorar para usar cálculo no PHP (solução portável)
2. Ou criar abstração que detecta o banco e usa sintaxe apropriada
3. Adicionar testes para ambos os bancos

**Solução Recomendada:**
```php
// Calcular no PHP usando Carbon (portável)
$appointmentEndTime = $appointment->scheduled_at->copy()->addMinutes($duration);
$q2->where('scheduled_at', '<=', $startTime)
   ->where('scheduled_at', '>', $startTime->copy()->subMinutes($duration));
```

**Estimativa:** 2-3 horas  
**Prioridade:** 🔴 **ALTA**

---

### 2. Validação de Entrada - Queries com LIKE

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Arquivos Afetados:**
- `app/MedicalRecord/Application/Services/MedicalRecordService.php` (linhas 67, 82, 372, 513)
- `app/Services/AppointmentService.php`
- Múltiplos controllers

**Problema:**
- Uso de `LIKE` com interpolação direta de strings em alguns casos
- Embora Laravel proteja contra SQL injection, pode haver problemas de performance
- Falta sanitização adequada para buscas

**Código Atual:**
```php
$builder->where('name', 'like', "%{$search}%")
    ->orWhere('email', 'like', "%{$search}%");
```

**Risco:**
- 🟡 **MÉDIO** - Performance degradada com buscas complexas
- 🟢 **BAIXO** - Laravel protege contra SQL injection, mas pode haver edge cases

**Ações Necessárias:**
1. Validar e sanitizar `$search` antes de usar em queries
2. Limitar tamanho máximo de busca
3. Escapar caracteres especiais do LIKE (`%`, `_`)
4. Considerar usar full-text search para melhor performance

**Solução Recomendada:**
```php
// Sanitizar busca
$search = trim($search);
$search = str_replace(['%', '_'], ['\%', '\_'], $search); // Escapar wildcards
$search = substr($search, 0, 100); // Limitar tamanho

$builder->where('name', 'like', "%{$search}%")
    ->orWhere('email', 'like', "%{$search}%");
```

**Estimativa:** 1 dia  
**Prioridade:** 🟡 **MÉDIA**

---

### 3. Autorização - Falta de Policies Aplicadas

**Status:** ❌ **CRÍTICO**

**Arquivos Afetados:**
- `app/Http/Controllers/AppointmentsController.php`
- `app/Http/Controllers/Doctor/DoctorPatientMedicalRecordController.php`
- `app/Http/Controllers/Patient/PatientMedicalRecordController.php`
- Múltiplos controllers de prontuário

**Problema:**
- Controllers não aplicam Policies consistentemente
- Validação de autorização feita manualmente em alguns lugares
- Falta `AppointmentPolicy` implementada
- Falta `MedicalRecordPolicy` implementada

**Risco:**
- 🔴 **CRÍTICO** - Acesso não autorizado a dados sensíveis
- 🔴 **CRÍTICO** - Violação de privacidade e LGPD

**Ações Necessárias:**
1. Implementar `AppointmentPolicy` com métodos:
   - `view()` - médico/paciente podem ver seus appointments
   - `update()` - apenas médico pode atualizar
   - `delete()` - apenas médico pode deletar
   - `start()` - apenas médico pode iniciar
   - `end()` - apenas médico pode finalizar
2. Implementar `MedicalRecordPolicy` com métodos:
   - `view()` - médico e paciente podem ver registros vinculados
   - `create()` - apenas médico pode criar
   - `update()` - apenas médico pode atualizar
   - `delete()` - apenas médico pode deletar (soft delete)
3. Aplicar `authorize()` em todos os métodos dos controllers
4. Remover validações manuais e substituir por Policies

**Exemplo de Implementação:**
```php
// AppointmentController
public function show(Appointments $appointment)
{
    $this->authorize('view', $appointment);
    // ... resto do código
}
```

**Estimativa:** 3-5 dias  
**Prioridade:** 🔴 **CRÍTICA**

---

### 4. Exposição de Dados Sensíveis - Logs e Debug

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Possível exposição de dados sensíveis em logs
- Debug mode pode expor informações em produção
- Erros podem vazar informações do sistema

**Arquivos a Revisar:**
- `config/logging.php`
- `.env` (APP_DEBUG)
- `app/Exceptions/Handler.php`
- Todos os pontos de logging

**Risco:**
- 🔴 **ALTO** - Exposição de dados pessoais em logs
- 🔴 **ALTO** - Violação de LGPD
- 🟡 **MÉDIO** - Informações de sistema expostas

**Ações Necessárias:**
1. Revisar todos os pontos de logging
2. Garantir que senhas, tokens e dados sensíveis nunca sejam logados
3. Configurar `APP_DEBUG=false` em produção
4. Implementar sanitização de dados em logs
5. Revisar `AuditAccess` middleware para não logar dados sensíveis
6. Implementar máscara de dados sensíveis (CPF, email parcial, etc.)

**Exemplo de Sanitização:**
```php
// Antes de logar
$sanitized = [
    'email' => $this->maskEmail($user->email),
    'cpf' => $this->maskCpf($user->cpf),
    // Nunca logar senha ou tokens
];
```

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

### 5. Rate Limiting - Endpoints Críticos

**Status:** ⚠️ **PARCIAL**

**Problema:**
- Rate limiting implementado apenas em login
- Endpoints críticos sem proteção:
  - Criação de appointments
  - Requisições de videoconferência
  - Upload de arquivos
  - Envio de mensagens
  - Exportação de dados

**Risco:**
- 🔴 **ALTO** - Ataques de força bruta
- 🔴 **ALTO** - DDoS em endpoints específicos
- 🟡 **MÉDIO** - Abuso de recursos

**Ações Necessárias:**
1. Adicionar rate limiting em todos os endpoints críticos:
   ```php
   Route::middleware(['throttle:10,1'])->group(function () {
       // Endpoints críticos
   });
   ```
2. Configurar limites específicos por endpoint:
   - Login: 5 tentativas/minuto (já implementado)
   - Criação de appointments: 10/minuto
   - Videoconferência: 5/minuto
   - Upload de arquivos: 20/hora
   - Exportação de dados: 3/hora
3. Implementar rate limiting por IP e por usuário
4. Adicionar logs de tentativas bloqueadas

**Estimativa:** 1-2 dias  
**Prioridade:** 🔴 **ALTA**

---

### 6. Validação de Upload de Arquivos

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Arquivos Afetados:**
- `app/Http/Controllers/MedicalRecordDocumentController.php`
- `app/Http/Requests/AvatarUploadRequest.php`
- Qualquer controller que aceita uploads

**Problema:**
- Validação de tipo MIME pode ser burlada
- Falta validação de conteúdo real do arquivo
- Tamanho máximo pode não estar configurado
- Falta sanitização de nomes de arquivo

**Risco:**
- 🔴 **ALTO** - Upload de arquivos maliciosos
- 🔴 **ALTO** - Execução de código remoto
- 🟡 **MÉDIO** - Armazenamento de arquivos não autorizados

**Ações Necessárias:**
1. Validar tipo MIME real do arquivo (não apenas extensão)
2. Validar conteúdo do arquivo (magic bytes)
3. Sanitizar nomes de arquivo (remover caracteres especiais)
4. Limitar tamanho máximo por tipo de arquivo
5. Escanear arquivos com antivírus (opcional, mas recomendado)
6. Armazenar arquivos fora do web root quando possível
7. Gerar nomes únicos para arquivos (UUID)

**Exemplo de Validação:**
```php
// Validar magic bytes
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file->getRealPath());
finfo_close($finfo);

// Validar contra whitelist
$allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
if (!in_array($mimeType, $allowedMimes)) {
    throw ValidationException::withMessages(['file' => 'Tipo de arquivo não permitido']);
}
```

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

### 7. CSRF Protection - Verificação de Rotas

**Status:** ✅ **IMPLEMENTADO** (mas revisar)

**Problema:**
- Verificar se todas as rotas POST/PUT/DELETE estão protegidas
- Verificar se rotas de API estão corretamente configuradas
- Verificar se WebSockets não precisam de proteção adicional

**Risco:**
- 🟡 **MÉDIO** - Ataques CSRF se alguma rota estiver desprotegida

**Ações Necessárias:**
1. Revisar `routes/web.php` e garantir que rotas POST/PUT/DELETE têm middleware `VerifyCsrfToken`
2. Verificar exceções em `app/Http/Middleware/VerifyCsrfToken.php`
3. Garantir que rotas de API usam tokens adequados
4. Testar proteção CSRF em todos os formulários

**Estimativa:** 1 dia  
**Prioridade:** 🟡 **MÉDIA**

---

### 8. XSS Protection - Sanitização de Input

**Status:** ⚠️ **PARCIAL**

**Problema:**
- Middleware `SanitizeInput` existe, mas precisa ser verificado
- Falta sanitização em campos JSON/JSONB
- Falta sanitização em campos de rich text (se houver)

**Arquivos:**
- `app/Http/Middleware/SanitizeInput.php`
- Campos que aceitam HTML (se houver)

**Risco:**
- 🟡 **MÉDIO** - XSS se sanitização não for completa
- 🟡 **MÉDIO** - Armazenamento de código malicioso

**Ações Necessárias:**
1. Revisar `SanitizeInput` middleware
2. Garantir que todos os campos de texto são sanitizados
3. Para campos que precisam de HTML (rich text), usar biblioteca de sanitização (ex: HTMLPurifier)
4. Validar sanitização em campos JSON
5. Testar contra payloads XSS conhecidos

**Estimativa:** 1-2 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 9. Validação de Relacionamentos - N+1 Queries

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Possíveis queries N+1 em vários lugares
- Falta eager loading em relacionamentos
- Performance degradada com muitos dados

**Arquivos a Revisar:**
- Todos os controllers que listam dados
- Services que fazem queries com relacionamentos

**Risco:**
- 🟡 **MÉDIO** - Performance degradada
- 🟢 **BAIXO** - Não é vulnerabilidade de segurança, mas afeta disponibilidade

**Ações Necessárias:**
1. Revisar todos os controllers e services
2. Adicionar eager loading onde necessário:
   ```php
   Appointments::with(['doctor.user', 'patient.user'])->get();
   ```
3. Usar `withCount()` quando necessário
4. Monitorar queries com Laravel Debugbar ou Telescope
5. Otimizar queries complexas

**Estimativa:** 2-3 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 10. Validação de UUIDs - Injeção de IDs Inválidos

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Validação de UUIDs pode não estar em todos os lugares
- IDs inválidos podem causar erros ou comportamentos inesperados

**Risco:**
- 🟡 **MÉDIO** - Erros não tratados
- 🟢 **BAIXO** - Não é vulnerabilidade crítica, mas afeta robustez

**Ações Necessárias:**
1. Criar FormRequest base para validação de UUIDs
2. Adicionar validação `uuid` em todos os parâmetros de rota
3. Usar route model binding com validação automática
4. Tratar erros de UUID inválido adequadamente

**Exemplo:**
```php
public function rules(): array
{
    return [
        'appointment_id' => ['required', 'uuid', 'exists:appointments,id'],
    ];
}
```

**Estimativa:** 1 dia  
**Prioridade:** 🟡 **MÉDIA**

---

## 🔒 Tasks de Segurança

### 1. Auditoria de Segurança Completa

**Status:** ❌ **NÃO REALIZADA**

**Objetivo:** Identificar todas as vulnerabilidades de segurança do sistema

**Tasks:**
- [ ] Revisar OWASP Top 10 (2021)
- [ ] Testar autenticação e autorização
- [ ] Testar validação de entrada
- [ ] Testar proteção contra XSS
- [ ] Testar proteção contra CSRF
- [ ] Testar proteção contra SQL Injection
- [ ] Testar upload de arquivos
- [ ] Testar rate limiting
- [ ] Testar exposição de dados sensíveis
- [ ] Testar configurações de segurança
- [ ] Revisar logs e debug
- [ ] Testar criptografia de dados sensíveis
- [ ] Revisar políticas de senha
- [ ] Testar sessões e tokens

**Estimativa:** 1-2 semanas  
**Prioridade:** 🔴 **ALTA**

---

### 2. Implementar Security Headers

**Status:** ⚠️ **PARCIAL**

**Problema:**
- Middleware `SecurityHeaders` existe, mas precisa ser verificado
- Falta configuração de headers específicos

**Headers Necessários:**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY` ou `SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Content-Security-Policy: ...`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: ...`

**Ações Necessárias:**
1. Revisar `app/Http/Middleware/SecurityHeaders.php`
2. Adicionar todos os headers de segurança recomendados
3. Configurar CSP adequadamente para o projeto
4. Testar headers com ferramentas online
5. Verificar compatibilidade com frontend

**Estimativa:** 1 dia  
**Prioridade:** 🔴 **ALTA**

---

### 3. Implementar Criptografia de Dados Sensíveis

**Status:** ⚠️ **PARCIAL**

**Problema:**
- Dados sensíveis podem não estar criptografados no banco
- Falta criptografia de campos específicos (ex: CPF, dados clínicos sensíveis)

**Dados que Devem Ser Criptografados:**
- CPF
- Dados de contato de emergência
- Histórico médico sensível
- Tokens de API
- Certificados digitais

**Ações Necessárias:**
1. Identificar todos os campos sensíveis
2. Usar `encrypted` cast nos models Laravel
3. Implementar criptografia adicional para dados muito sensíveis
4. Gerenciar chaves de criptografia adequadamente
5. Testar descriptografia em todos os pontos de acesso

**Exemplo:**
```php
protected $casts = [
    'cpf' => 'encrypted',
    'emergency_contact_phone' => 'encrypted',
];
```

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

### 4. Implementar Logs de Segurança

**Status:** ⚠️ **PARCIAL**

**Problema:**
- Logs de segurança podem não estar completos
- Falta monitoramento de tentativas de acesso não autorizado
- Falta alertas de segurança

**Ações Necessárias:**
1. Implementar logging de:
   - Tentativas de login falhadas
   - Acessos negados (403)
   - Tentativas de acesso não autorizado
   - Mudanças em dados sensíveis
   - Exportação de dados
   - Uploads de arquivos
   - Ações administrativas
2. Configurar alertas para eventos críticos
3. Implementar dashboard de segurança
4. Configurar retenção de logs adequada
5. Garantir que logs não contenham dados sensíveis

**Estimativa:** 2-3 dias  
**Prioridade:** 🔴 **ALTA**

---

### 5. Implementar 2FA (Autenticação de Dois Fatores)

**Status:** ❌ **NÃO IMPLEMENTADO**

**Problema:**
- Sistema não tem 2FA implementado
- Apenas mencionado no roadmap de backend

**Ações Necessárias:**
1. Implementar 2FA para médicos (obrigatório)
2. Implementar 2FA opcional para pacientes
3. Usar TOTP (Time-based One-Time Password)
4. Gerar códigos de backup
5. Implementar interface de ativação
6. Adicionar middleware para exigir 2FA em rotas sensíveis
7. Registrar logs de verificação 2FA

**Estimativa:** 1-2 semanas  
**Prioridade:** 🟡 **MÉDIA**

---

### 6. Implementar Política de Senhas Fortes

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Política de senhas pode não estar adequada
- Falta validação de senhas comuns/vazadas

**Ações Necessárias:**
1. Implementar validação de senha forte:
   - Mínimo 8 caracteres (recomendado 12+)
   - Letras maiúsculas e minúsculas
   - Números
   - Caracteres especiais
2. Verificar senha contra lista de senhas comuns
3. Implementar verificação de senhas vazadas (Have I Been Pwned API)
4. Forçar troca de senha após primeiro login (médicos)
5. Implementar expiração de senha (opcional)
6. Adicionar feedback visual de força da senha

**Estimativa:** 1-2 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 7. Implementar Proteção contra Enumeration

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Endpoints podem expor se email/usuário existe
- Falta proteção contra user enumeration

**Ações Necessárias:**
1. Garantir que mensagens de erro são genéricas:
   - "Credenciais inválidas" (não "Email não encontrado" ou "Senha incorreta")
2. Tempo de resposta consistente (não variar baseado em existência)
3. Rate limiting em endpoints de registro/login
4. Não expor IDs de usuários em URLs públicas
5. Usar UUIDs em vez de IDs sequenciais

**Estimativa:** 1 dia  
**Prioridade:** 🟡 **MÉDIA**

---

### 8. Implementar Validação de Sessão

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Sessões podem não estar adequadamente protegidas
- Falta rotação de sessão
- Falta invalidação de sessões antigas

**Ações Necessárias:**
1. Configurar rotação de sessão após login
2. Invalidar sessões antigas após mudança de senha
3. Implementar logout de todos os dispositivos
4. Configurar timeout de sessão adequado
5. Implementar "Lembrar-me" de forma segura
6. Validar sessão em cada requisição crítica

**Estimativa:** 1-2 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 9. Implementar Proteção de API

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- APIs podem não ter autenticação adequada
- Falta rate limiting em APIs
- Falta versionamento de API

**Ações Necessárias:**
1. Implementar autenticação por token (Laravel Sanctum)
2. Rate limiting específico para APIs
3. Versionamento de API (`/api/v1/...`)
4. Documentação de API (OpenAPI/Swagger)
5. Validação de origem (CORS adequado)
6. Logs de acesso à API

**Estimativa:** 2-3 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 10. Implementar Backup e Recuperação Segura

**Status:** ⚠️ **REVISÃO NECESSÁRIA**

**Problema:**
- Backups podem não estar configurados
- Falta criptografia de backups
- Falta teste de recuperação

**Ações Necessárias:**
1. Configurar backups automáticos
2. Criptografar backups
3. Armazenar backups em local seguro
4. Implementar retenção de backups adequada
5. Testar recuperação de backups regularmente
6. Documentar processo de recuperação
7. Implementar backup incremental

**Estimativa:** 2-3 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 11. Implementar Monitoramento de Segurança

**Status:** ❌ **NÃO IMPLEMENTADO**

**Problema:**
- Falta monitoramento de eventos de segurança
- Falta alertas de segurança
- Falta dashboard de segurança

**Ações Necessárias:**
1. Implementar monitoramento de:
   - Tentativas de login falhadas
   - Acessos não autorizados
   - Mudanças em dados sensíveis
   - Uploads de arquivos
   - Exportação de dados
2. Configurar alertas para eventos críticos
3. Implementar dashboard de segurança
4. Integrar com ferramentas de monitoramento (ex: Sentry)
5. Configurar notificações para equipe de segurança

**Estimativa:** 3-5 dias  
**Prioridade:** 🟡 **MÉDIA**

---

### 12. Implementar Testes de Segurança

**Status:** ❌ **NÃO IMPLEMENTADO**

**Problema:**
- Falta testes automatizados de segurança
- Falta testes de penetração
- Falta testes de vulnerabilidades

**Ações Necessárias:**
1. Implementar testes automatizados de segurança:
   - Testes de autenticação
   - Testes de autorização
   - Testes de validação de entrada
   - Testes de proteção CSRF
   - Testes de rate limiting
2. Integrar ferramentas de análise estática (PHPStan, Larastan)
3. Integrar ferramentas de análise de dependências (composer audit)
4. Planejar testes de penetração periódicos
5. Implementar testes de segurança no CI/CD

**Estimativa:** 1-2 semanas  
**Prioridade:** 🟡 **MÉDIA**

---

## 📋 Plano de Ação Prioritizado

### Fase 1 - Correções Críticas (4-6 semanas)

**Objetivo:** Atingir 100% de conformidade CFM

#### Semanas 1-2: Assinatura Digital ICP-Brasil

1. Contratar provedor de certificação digital ICP-Brasil
2. Implementar `DigitalSignatureService.php`
3. Atualizar models (`Prescription` e `MedicalCertificate`)
4. Criar migrations
5. Integrar fluxo de assinatura no frontend
6. Testes de integração

**Entregáveis:**
- ✅ Serviço de assinatura digital implementado
- ✅ Prescrições e atestados com assinatura válida
- ✅ Validação de certificado antes de emissão

---

#### Semanas 3-5: Videoconferência - Gaps Críticos

1. Implementar amarração com appointment (`appointment_id` obrigatório)
2. Criar e aplicar `AppointmentPolicy`
3. Implementar locks de concorrência (Redis)
4. Configurar TURN server
5. Implementar regras de janela e timezone
6. Melhorar cancelamento e timeout
7. Testes end-to-end completos

**Entregáveis:**
- ✅ Videoconferência totalmente integrada com appointments
- ✅ Controle de acesso robusto
- ✅ Prevenção de múltiplas chamadas simultâneas
- ✅ Conectividade garantida (NAT traversal)

---

#### Semana 6: Documentação Legal e Testes

1. Adicionar seções obrigatórias na Política de Privacidade
2. Adicionar seções obrigatórias nos Termos de Serviço
3. Testes de integração completos
4. Auditoria de segurança
5. Validação de conformidade

**Entregáveis:**
- ✅ Documentos legais 100% conformes com CFM
- ✅ Testes completos passando
- ✅ Auditoria de segurança concluída

---

### Fase 2 - Melhorias e Otimizações (3-4 semanas)

#### Semana 7: Versionamento de Prontuário

1. Implementar versionamento explícito
2. Histórico de edições com diff
3. Interface de visualização

**Entregáveis:**
- ✅ Versionamento completo de prontuário
- ✅ Interface de histórico

---

#### Semana 8: Gravação de Sessão (Opcional)

**Apenas se decidir implementar:**

1. Implementar MediaRecorder API
2. Upload para storage seguro
3. Interface de consentimento específico
4. Política de retenção automatizada
5. Player de vídeo

**Entregáveis:**
- ✅ Gravação de sessões funcional
- ✅ Consentimento específico implementado

---

#### Semanas 9-10: Métricas e Monitoramento

1. Logs estruturados completos
2. Métricas de qualidade de chamada
3. Dashboard de KPIs
4. Alertas de problemas

**Entregáveis:**
- ✅ Sistema de monitoramento completo
- ✅ Dashboard de métricas

---

### Fase 3 - Produção e Compliance (Contínuo)

1. **Designar DPO** (Data Protection Officer)
2. **Documentação de Processos LGPD**
3. **Treinamento de Equipe**
4. **Auditorias Periódicas**
5. **Migração para Redis** (recomendado)
6. **Migração para PostgreSQL** (opcional)

---

## 📊 Resumo de Prioridades

### 🔴 Prioridade CRÍTICA (Bloqueantes)

1. **Assinatura Digital ICP-Brasil** - 2-3 semanas
2. **Videoconferência - Gaps Críticos** - 3-4 semanas
3. **Documentação Legal (CFM)** - 2-3 dias
4. **AppointmentPolicy** - 2-3 dias
5. **Backend - Core e Infraestrutura** - 1-2 semanas
6. **Backend - Agenda e Consultas** - 3-4 semanas
7. **Backend - Videoconferência (Reimplementação)** - 3-4 semanas
8. **Backend - Prontuário e Prescrições** - 2-3 semanas
9. **Backend - Qualidade e Testes** - 2-3 semanas
10. **Segurança - Autorização (Policies)** - 3-5 dias
11. **Segurança - Rate Limiting** - 1-2 dias
12. **Segurança - Validação de Upload** - 2-3 dias
13. **Segurança - Exposição de Dados** - 2-3 dias
14. **Segurança - Security Headers** - 1 dia
15. **Segurança - Criptografia de Dados** - 2-3 dias
16. **Segurança - Logs de Segurança** - 2-3 dias

### 🟡 Prioridade ALTA (Importantes)

17. **Backend - Usuários e Perfis** - 2-3 semanas
18. **Versionamento de Prontuário** - 1 semana
19. **Gravação de Sessão** (se implementar) - 2 semanas
20. **Migração para Redis** - 1 semana
21. **Segurança - Auditoria Completa** - 1-2 semanas
22. **Segurança - SQL Injection (DATE_ADD)** - 2-3 horas
23. **Segurança - Validação de Entrada (LIKE)** - 1 dia
24. **Segurança - CSRF Protection** - 1 dia
25. **Segurança - XSS Protection** - 1-2 dias

### 🟡 Prioridade MÉDIA

26. **Backend - Mensageria** - 2-3 semanas
27. **Backend - Notificações e Observabilidade** - 1-2 semanas
28. **Métricas e Monitoramento** - 1-2 semanas
29. **Segurança - 2FA** - 1-2 semanas
30. **Segurança - Política de Senhas** - 1-2 dias
31. **Segurança - Proteção Enumeration** - 1 dia
32. **Segurança - Validação de Sessão** - 1-2 dias
33. **Segurança - Proteção de API** - 2-3 dias
34. **Segurança - Backup e Recuperação** - 2-3 dias
35. **Segurança - Monitoramento** - 3-5 dias
36. **Segurança - Testes de Segurança** - 1-2 semanas
37. **Segurança - N+1 Queries** - 2-3 dias
38. **Segurança - Validação de UUIDs** - 1 dia

### 🟢 Prioridade BAIXA (Melhorias)

17. **Migração para PostgreSQL** - 1-2 semanas (opcional)

---

## ✅ Checklist de Conformidade

### Conformidade CFM

- [ ] Assinatura Digital ICP-Brasil implementada
- [ ] Videoconferência 100% funcional e segura
- [ ] Política de Privacidade com elementos CFM
- [ ] Termos de Serviço com elementos CFM
- [ ] AppointmentPolicy implementada
- [ ] Gravação de sessão (se implementar) com consentimento específico
- [ ] Versionamento de prontuário completo

### Conformidade LGPD

- [x] Sistema de Consentimento implementado
- [x] Política de Privacidade completa
- [x] Termos de Serviço completos
- [x] Direitos do titular implementados
- [x] Portabilidade de dados implementada
- [x] Direito ao esquecimento implementado
- [x] Relatórios de acesso implementados
- [ ] DPO designado (configuração administrativa)

### Infraestrutura

- [ ] Redis configurado (recomendado)
- [ ] PostgreSQL configurado (opcional)
- [ ] TURN server configurado
- [ ] Monitoramento e alertas configurados

### Segurança - Revisões Críticas

- [ ] SQL Injection - Query DATE_ADD corrigida
- [ ] Validação de entrada - Queries LIKE revisadas
- [ ] Autorização - Policies implementadas e aplicadas
- [ ] Exposição de dados - Logs revisados
- [ ] Rate limiting - Endpoints críticos protegidos
- [ ] Upload de arquivos - Validação completa
- [ ] CSRF Protection - Todas as rotas verificadas
- [ ] XSS Protection - Sanitização completa
- [ ] N+1 Queries - Otimizações aplicadas
- [ ] Validação de UUIDs - Validação em todos os endpoints

### Segurança - Tasks

- [ ] Auditoria de segurança completa realizada
- [ ] Security Headers implementados
- [ ] Criptografia de dados sensíveis implementada
- [ ] Logs de segurança implementados
- [ ] 2FA implementado (médicos obrigatório)
- [ ] Política de senhas fortes implementada
- [ ] Proteção contra enumeration implementada
- [ ] Validação de sessão implementada
- [ ] Proteção de API implementada
- [ ] Backup e recuperação segura configurada
- [ ] Monitoramento de segurança implementado
- [ ] Testes de segurança implementados

---

## 📈 Status Atual vs. Meta

### Conformidade Atual

```
✅ Conformidade LGPD:          95% ████████████████████░
✅ Identificação do Médico:    100% ████████████████████
✅ Prontuário Eletrônico:      100% ████████████████████
✅ Sistema de Consentimento:   100% ████████████████████
⚠️ Videoconferência:            70% ██████████████░░░░░░
⚠️ Gravação de Vídeo:           50% ██████████░░░░░░░░░░
❌ Prescrição Digital:          40% ████████░░░░░░░░░░░░
⚠️ Documentos Legais (CFM):     40% ████████░░░░░░░░░░░░

MÉDIA GERAL:                    80% ████████████████░░░░
```

### Meta (100% Conforme)

```
✅ Conformidade LGPD:          100% ████████████████████
✅ Identificação do Médico:    100% ████████████████████
✅ Prontuário Eletrônico:      100% ████████████████████
✅ Sistema de Consentimento:   100% ████████████████████
✅ Videoconferência:           100% ████████████████████
✅ Gravação de Vídeo:          100% ████████████████████
✅ Prescrição Digital:         100% ████████████████████
✅ Documentos Legais (CFM):    100% ████████████████████

MÉDIA GERAL:                   100% ████████████████████
```

---

## 🎯 Recomendação Final

**Status Atual:** ⚠️ **SISTEMA PARCIALMENTE CONFORME**

**Ações Imediatas:**
1. ✅ Sistema pode ser usado para **consultas sem emissão de prescrição/atestado**
2. ❌ **NÃO emitir documentos médicos** (prescrição, atestado) até implementar ICP-Brasil
3. ⚠️ Finalizar gaps críticos de videoconferência antes de produção
4. ✅ LGPD está adequado e pode ser usado
5. ⚠️ Atualizar documentos legais com elementos CFM obrigatórios

**Prazo para Conformidade Completa:** 6-8 semanas

**Próximos Passos:**
1. Priorizar implementação de Assinatura Digital ICP-Brasil
2. Finalizar gaps críticos de videoconferência
3. Atualizar documentos legais
4. Implementar AppointmentPolicy
5. Testes completos e validação de conformidade

---

**📅 Última Atualização:** 26 de Janeiro de 2026  
**🔄 Próxima Revisão:** Após implementação de assinatura digital ICP-Brasil

**📝 Nota:** Este documento foi atualizado com seções de **Revisões de Código Crítico** e **Tasks de Segurança** para garantir a segurança e qualidade do código do sistema.

---

**Documento consolidado de:** 
- `pendencias.md`
- `docs/Pending Issues/CONFORMIDADE_CFM_LGPD.md`
- `back-end` (Roadmap de Implementação)
- `docs/Pending Issues/ROADMAP_MONETIZACAO_VISUAL.md` (mencionado como roadmap futuro)
- `docs/Pending Issues/TransitionPostgreeSQL.md` (mencionado como guia opcional)
- `docs/Pending Issues/TransitionRedis.md` (mencionado como guia recomendado)
