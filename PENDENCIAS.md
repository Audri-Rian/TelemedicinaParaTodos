# 📋 Pendências do Projeto - Telemedicina para Todos

**Data de Análise:** Janeiro 2025  
**Versão do Documento:** 1.0

---

## 📑 Sumário

1. [Requisitos Funcionais Pendentes](#requisitos-funcionais-pendentes)
2. [Melhorias de UX/UI](#melhorias-de-uxui)
3. [Melhorias Técnicas](#melhorias-técnicas)
4. [Testes e Qualidade](#testes-e-qualidade)
5. [Segurança e Compliance](#segurança-e-compliance)
6. [Integrações e APIs](#integrações-e-apis)
7. [Documentação](#documentação)
8. [Infraestrutura e DevOps](#infraestrutura-e-devops)

---

## 🎯 Requisitos Funcionais Pendentes

### RF006 - Pagamentos Online
**Status:** 📋 Planejado  
**Prioridade:** Desejável  
**Descrição:** Sistema completo de pagamentos online para consultas.

**Pendências:**
- [ ] Integração com gateway de pagamento (Stripe, PagSeguro, Mercado Pago)
- [ ] Modelo de dados para transações
- [ ] Interface de pagamento para pacientes
- [ ] Painel de recebimentos para médicos
- [ ] Histórico de pagamentos
- [ ] Reembolsos e cancelamentos
- [ ] Notificações de pagamento
- [ ] Relatórios financeiros

**Referências:**
- [SystemRules.md](docs/requirements/SystemRules.md)
- [FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md#rf006)

---

### RF008 - Notificações de Consultas
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Desejável  
**Descrição:** Sistema completo de notificações sobre consultas.

**Pendências:**
- [ ] Sistema de notificações em tempo real (push notifications)
- [ ] Notificações por email
- [ ] Notificações no painel da plataforma
- [ ] Lembretes automáticos de consultas
- [ ] Notificações de cancelamento/reagendamento
- [ ] Notificações de prescrições emitidas
- [ ] Notificações de exames solicitados
- [ ] Notificações de atestados emitidos
- [ ] Preferências de notificação por usuário
- [ ] Histórico de notificações

**Referências:**
- [SystemRules.md](docs/requirements/SystemRules.md)
- [FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md#rf008)
- [MatrizRequisitos.md](docs/index/MatrizRequisitos.md#rf008)

---

### Sistema de Chat
**Status:** 📋 Planejado  
**Prioridade:** Importante  
**Descrição:** Sistema de mensagens entre médicos e pacientes.

**Pendências:**
- [ ] Modelo de dados para mensagens
- [ ] Interface de chat em tempo real
- [ ] Histórico de conversas
- [ ] Notificações de novas mensagens
- [ ] Integração com consultas
- [ ] Suporte a anexos
- [ ] Mensagens automáticas do sistema

**Referências:**
- [Problems.md](Problems.md)

---

## 🎨 Melhorias de UX/UI

### Ajustes de UX para Videoconferência
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Ajustar UX para caso alguém recuse acidentalmente a chamada
- [ ] Botão de reenvio de solicitação de chamada
- [ ] Feedback visual melhorado para estados da chamada
- [ ] Indicadores de conexão (qualidade de rede)
- [ ] Modal de confirmação antes de recusar chamada

**Referências:**
- [Problems.md](Problems.md)
- [VideoCallTasks.md](docs/modules/videocall/VideoCallTasks.md)

---

### Melhorias na Página de Consultas
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Botão para envio de mensagens/comunicação
- [ ] Melhorias na visualização de prontuário durante consulta
- [ ] Interface mais intuitiva para registro de dados
- [ ] Auto-save mais frequente e feedback visual

**Referências:**
- [Problems.md](Problems.md)
- [UX_ARCHITECTURE.md](docs/UX_ARCHITECTURE.md)

---

### JSON-LD para SEO
**Status:** 📋 Planejado  
**Prioridade:** Desejável

**Pendências:**
- [ ] Implementar JSON-LD para páginas principais
- [ ] Schema.org para organização médica
- [ ] Schema.org para profissionais de saúde
- [ ] Schema.org para serviços médicos
- [ ] Schema.org para avaliações (quando implementado)

**Referências:**
- [Problems.md](Problems.md)

---

## 🔧 Melhorias Técnicas

### Melhorias no Sistema de Videoconferência
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Essencial

**Pendências Conforme Checklist:**
- [ ] Amarração de chamada ao agendamento (appointment_id obrigatório)
- [ ] Campos de lifecycle no appointments (started_at, ended_at)
- [ ] Metadados e auditoria completos
- [ ] AppointmentPolicy implementada e aplicada
- [ ] Rate limiting e anti-spam
- [ ] Locks de concorrência (Redis)
- [ ] Canais de broadcast por consulta
- [ ] Eventos padronizados com broadcastWith()
- [ ] Endpoints REST completos
- [ ] Regras de janela e timezone
- [ ] Cancelamento e timeout
- [ ] Máquina de estados no frontend
- [ ] Listeners únicos e contexto
- [ ] Timeouts e cancel
- [ ] Integração completa com Echo
- [ ] Captura e permissões melhoradas
- [ ] Dispositivos e preferências
- [ ] Conectividade e TURN configurado
- [ ] Eventos de chamada tratados
- [ ] Logs estruturados
- [ ] Métricas e KPIs
- [ ] Testes completos
- [ ] Jobs/Cron para no_show
- [ ] Degradação elegante

**Referências:**
- [VideoCallTasks.md](docs/modules/videocall/VideoCallTasks.md)
- [VideoCallImplementation.md](docs/modules/videocall/VideoCallImplementation.md)

---

### Sistema de Gravação de Vídeo
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Implementar gravação de consultas (MediaRecorder API)
- [ ] Upload de gravações para storage
- [ ] Controle de acesso às gravações
- [ ] Consentimento do paciente para gravação
- [ ] Política de retenção de gravações
- [ ] Player de vídeo para visualização
- [ ] Download de gravações (com permissão)

**Referências:**
- [AppointmentsLogica.md](docs/modules/appointments/AppointmentsLogica.md)
- [VideoCallTasks.md](docs/modules/videocall/VideoCallTasks.md)

---

### Melhorias no Prontuário Médico
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Essencial

**Pendências:**
- [ ] Retirar campo "Anamnese" (conforme SOAP na medicina)
- [ ] Implementar lista completa de CID-10
- [ ] Retirar Sinais Vitais (conforme Problems.md)
- [ ] Busca avançada em prontuários
- [ ] Filtros por data, tipo, médico
- [ ] Exportação melhorada de PDFs
- [ ] Templates de consulta
- [ ] Auto-complete para CID-10
- [ ] Auto-complete para medicamentos
- [ ] Catálogo de exames

**Referências:**
- [Problems.md](Problems.md)
- [MedicalRecordsDoctor.md](docs/modules/MedicalRecords/MedicalRecordsDoctor.md)

---

### Implementações de TODOs no Código
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Importante

**Pendências:**
- [ ] Implementar chamada real da API em `usePatientProfileUpdate.ts` (linha 110)
- [ ] Implementar chamada real da API em `useDoctorProfileUpdate.ts` (linha 108)
- [ ] Completar validações pendentes
- [ ] Remover simulações e mocks

**Arquivos Afetados:**
- `resources/js/composables/Patient/usePatientProfileUpdate.ts`
- `resources/js/composables/Doctor/useDoctorProfileUpdate.ts`

---

## 🧪 Testes e Qualidade

### Testes Unitários Pendentes
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Importante

**Pendências:**
- [ ] Testes completos para AppointmentService
- [ ] Testes completos para AvailabilityService
- [ ] Testes completos para MedicalRecordService
- [ ] Testes completos para ScheduleService
- [ ] Testes completos para TimelineEventService
- [ ] Testes completos para VideoCallController
- [ ] Testes para Policies (AppointmentPolicy, MedicalRecordPolicy, etc.)
- [ ] Testes para Observers (AppointmentsObserver)

**Referências:**
- [MatrizRequisitos.md](docs/index/MatrizRequisitos.md)

---

### Testes de Integração Pendentes
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Testes de fluxo completo de agendamento
- [ ] Testes de fluxo completo de consulta
- [ ] Testes de videoconferência end-to-end
- [ ] Testes de prontuário médico completo
- [ ] Testes de agenda e disponibilidade
- [ ] Testes de autenticação e autorização

---

### Testes de Performance
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Testes de carga (500 usuários simultâneos)
- [ ] Testes de stress
- [ ] Otimização de queries N+1
- [ ] Cache de consultas frequentes
- [ ] Otimização de assets frontend
- [ ] Lazy loading de componentes

**Referências:**
- [FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md#nf004)

---

## 🔒 Segurança e Compliance

### Melhorias de Segurança
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Essencial

**Pendências:**
- [ ] Criptografia de dados sensíveis em repouso
- [ ] Implementação completa de consent management (LGPD)
- [ ] Auditoria completa de acessos
- [ ] Rate limiting em todas as rotas críticas
- [ ] Validação de CSRF em todas as requisições
- [ ] Sanitização de inputs
- [ ] Proteção contra SQL injection (já implementado via Eloquent, mas revisar)
- [ ] Proteção contra XSS
- [ ] Headers de segurança (CSP, HSTS, etc.)

**Referências:**
- [SystemRules.md](docs/requirements/SystemRules.md#segurança-e-compliance)
- [MatrizRequisitos.md](docs/index/MatrizRequisitos.md#nf007)

---

### Compliance LGPD
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Essencial

**Pendências:**
- [ ] Política de privacidade completa
- [ ] Termos de serviço completos
- [ ] Consentimento explícito para telemedicina
- [ ] Consentimento para gravação de vídeo
- [ ] Direito ao esquecimento (exclusão de dados)
- [ ] Portabilidade de dados
- [ ] Relatórios de acesso a dados pessoais
- [ ] DPO (Data Protection Officer) designado

**Referências:**
- [SystemRules.md](docs/requirements/SystemRules.md#segurança-e-compliance)

---

## 🔌 Integrações e APIs

### Integração com Laboratórios
**Status:** 📋 Planejado  
**Prioridade:** Desejável

**Pendências:**
- [ ] API para integração com laboratórios
- [ ] Recebimento automático de resultados de exames
- [ ] Status automático de exames
- [ ] Notificações de resultados disponíveis
- [ ] Visualização de laudos integrados

**Referências:**
- [CONSULTATION_FLOW.md](docs/CONSULTATION_FLOW.md)
- [UX_ARCHITECTURE.md](docs/UX_ARCHITECTURE.md)

---

### Validação Automática de CRM
**Status:** 📋 Planejado  
**Prioridade:** Desejável

**Pendências:**
- [ ] Integração com webservice de validação de CRM
- [ ] Validação automática no cadastro
- [ ] Verificação periódica de validade
- [ ] Notificações de expiração de licença

**Referências:**
- [FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md#110-exclusões-do-escopo)

---

### Integração com Notificações Push
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Integração com OneSignal ou Firebase
- [ ] Notificações push para mobile
- [ ] Notificações push para web
- [ ] Gerenciamento de tokens
- [ ] Segmentação de notificações

---

## 📚 Documentação

### Documentação Pendente
**Status:** 🔄 Em Desenvolvimento  
**Prioridade:** Importante

**Pendências:**
- [ ] Documentação de API completa (Swagger/OpenAPI)
- [ ] Guia de deployment para produção
- [ ] Guia de troubleshooting
- [ ] Documentação de integrações
- [ ] Atualizar diagramas conforme implementações
- [ ] Documentação de testes
- [ ] Changelog mantido

**Referências:**
- [README.md](README.md)
- [docs/](docs/)

---

## 🚀 Infraestrutura e DevOps

### Configuração de Produção
**Status:** 📋 Planejado  
**Prioridade:** Essencial

**Pendências:**
- [ ] Configuração de servidor de produção
- [ ] Configuração de banco de dados em produção
- [ ] Configuração de Redis em produção
- [ ] Configuração de Laravel Reverb em produção
- [ ] Configuração de TURN server para WebRTC
- [ ] Configuração de storage (S3 ou similar)
- [ ] Configuração de CDN
- [ ] SSL/TLS configurado
- [ ] Monitoramento (Laravel Telescope, APM)
- [ ] Logs centralizados
- [ ] Backup automatizado
- [ ] Estratégia de rollback

**Referências:**
- [FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md#5272-hospedagem-planejada-para-produção)

---

### CI/CD
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Pipeline de CI/CD
- [ ] Testes automatizados no pipeline
- [ ] Deploy automatizado
- [ ] Ambientes de staging
- [ ] Versionamento semântico

---

### Monitoramento e Observabilidade
**Status:** 📋 Planejado  
**Prioridade:** Importante

**Pendências:**
- [ ] Laravel Telescope configurado
- [ ] APM (New Relic, Datadog ou similar)
- [ ] Métricas de performance
- [ ] Alertas configurados
- [ ] Dashboard de métricas
- [ ] Logs estruturados
- [ ] Rastreamento de erros (Sentry ou similar)

**Referências:**
- [VideoCallTasks.md](docs/modules/videocall/VideoCallTasks.md#71-logs-estruturados)

---

## 📊 Dashboard de Métricas

### Métricas para Médicos
**Status:** 📋 Planejado  
**Prioridade:** Desejável

**Pendências:**
- [ ] Dashboard com estatísticas de consultas
- [ ] Taxa de no-show
- [ ] Duração média de consultas
- [ ] Número de pacientes atendidos
- [ ] Receita total (quando pagamentos implementados)
- [ ] Gráficos e visualizações

**Referências:**
- [CONSULTATION_FLOW.md](docs/CONSULTATION_FLOW.md)
- [UX_ARCHITECTURE.md](docs/UX_ARCHITECTURE.md)

---

### Métricas para Administradores
**Status:** 📋 Planejado  
**Prioridade:** Desejável

**Pendências:**
- [ ] Dashboard administrativo
- [ ] Métricas globais do sistema
- [ ] Relatórios de uso
- [ ] Relatórios financeiros (quando pagamentos implementados)
- [ ] Análise de comportamento de usuários

---

## 🔄 Melhorias Futuras

### Funcionalidades Adicionais
**Status:** 📋 Planejado  
**Prioridade:** Desejável

**Pendências:**
- [ ] Sistema de avaliações e comentários
- [ ] Sistema de favoritos (pacientes favoritar médicos)
- [ ] Histórico de preços de consultas
- [ ] Sistema de cupons e descontos
- [ ] Integração com calendários externos (Google Calendar, Outlook)
- [ ] App mobile (React Native ou Flutter)
- [ ] Modo offline para visualização de dados
- [ ] Sincronização offline

---

## 📈 Priorização Sugerida

### Fase 1 - Essencial (Próximas 4 semanas)
1. ✅ Completar melhorias de videoconferência (conforme checklist)
2. ✅ Implementar sistema de notificações básico
3. ✅ Completar TODOs no código
4. ✅ Melhorias no prontuário (retirar anamnese, lista CID-10, retirar sinais vitais)
5. ✅ Testes unitários críticos

### Fase 2 - Importante (Próximas 8 semanas)
1. ✅ Sistema de chat
2. ✅ Melhorias de UX/UI
3. ✅ Testes de integração
4. ✅ Segurança e compliance LGPD
5. ✅ Configuração de produção

### Fase 3 - Desejável (Futuro)
1. ✅ Sistema de pagamentos
2. ✅ Integração com laboratórios
3. ✅ Dashboard de métricas
4. ✅ App mobile
5. ✅ Funcionalidades adicionais

---

## 📝 Notas Finais

- Este documento deve ser atualizado regularmente conforme itens são concluídos
- Prioridades podem mudar conforme necessidades do negócio
- Alguns itens podem ser despriorizados ou removidos após análise mais detalhada
- Consulte a [Matriz de Rastreabilidade](docs/index/MatrizRequisitos.md) para status detalhado de cada requisito

---

**Última atualização:** Janeiro 2025  
**Próxima revisão:** Fevereiro 2025

