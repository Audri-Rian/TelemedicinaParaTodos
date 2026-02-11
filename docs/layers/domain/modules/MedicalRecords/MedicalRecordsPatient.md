# 📋 Módulo de Prontuários Médicos (Medical Records)

## 📑 Sumário Navegável

- [🎯 Objetivo](#-objetivo)
- [📊 Requisitos](#-requisitos)
- [⚖️ Regras de Negócio](#️-regras-de-negócio)
- [🔧 Funcionalidades](#-funcionalidades)
- [🎨 UX Detalhado](#-ux-detalhado)
- [🔄 Fluxo de Interação](#-fluxo-de-interação)
- [🔗 Integrações com Outros Módulos](#-integrações-com-outros-módulos)
- [❌ O Que Falta Implementar](#-o-que-falta-implementar)
- [💡 Recomendações de Melhoria](#-recomendações-de-melhoria)

---

## 🎯 Objetivo

O módulo de **Prontuários Médicos** (Medical Records) tem como objetivo centralizar e organizar todas as informações clínicas do paciente em um único local, permitindo que tanto pacientes quanto médicos acessem, visualizem e gerenciem o histórico médico de forma segura, organizada e conforme as regulamentações de proteção de dados (LGPD).

### Principais Objetivos:

1. **Centralização**: Reunir informações clínicas dispersas em um único prontuário digital
2. **Acessibilidade**: Permitir acesso fácil e rápido às informações médicas históricas
3. **Segurança**: Garantir que apenas pessoas autorizadas acessem os dados sensíveis
4. **Rastreabilidade**: Registrar todas as ações realizadas no prontuário (auditoria)
5. **Compliance**: Atender regulamentações médicas e de proteção de dados
6. **Exportação**: Permitir que pacientes exportem seus prontuários em formato PDF
7. **Integração**: Conectar-se com outros módulos (consultas, prescrições, exames)

---

## 📊 Requisitos

### Requisitos Funcionais

#### RF001 - Visualização do Prontuário pelo Paciente
- **Descrição**: O paciente deve poder visualizar seu próprio prontuário médico completo
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Detalhes**:
  - Acesso através da rota `/patient/medical-records`
  - Visualização de informações pessoais (idade, gênero, data de nascimento, ID)
  - Visualização de histórico de consultas completadas
  - Visualização de dados básicos do paciente (alergias, medicações, histórico médico)
  
#### RF002 - Visualização do Prontuário pelo Médico
- **Descrição**: Médicos devem poder visualizar prontuários de pacientes atendidos
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Detalhes**:
  - Acesso através de `/doctor/patients/{id}/medical-record`
  - Visualização completa do histórico clínico com mesmas abas do paciente
  - Validação automática de relacionamento médico-paciente
  - Suporte para visualização durante consulta em andamento e registro de auditoria

#### RF003 - Organização por Abas/Seções
- **Descrição**: O prontuário deve ser organizado em seções temáticas
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Seções Disponíveis**:
  1. **Histórico** - Timeline interativa de consultas concluídas
  2. **Consultas** - Grade detalhada com filtros aplicados
  3. **Prescrições** - Lista de prescrições digitais armazenadas
  4. **Exames** - Histórico de exames solicitados e concluídos
  5. **Documentos** - Biblioteca com upload seguro e download
  6. **Evolução** - Últimos sinais vitais registrados
  7. **Consultas Futuras** - Próximos agendamentos confirmados

#### RF004 - Detalhamento de Consultas
- **Descrição**: Cada consulta deve exibir informações detalhadas
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Informações Incluídas**:
  - Data e horário da consulta
  - Médico responsável e especialidade
  - Diagnóstico (CID-10)
  - Sintomas relatados
  - Exames solicitados
  - Orientações médicas
  - Anexos e prescrições vinculadas

#### RF005 - Exportação em PDF
- **Descrição**: Pacientes devem poder exportar seu prontuário completo em PDF
- **Prioridade**: Média
- **Status**: ✅ Implementado
- **Detalhes**:
  - Geração assíncrona via queue com `GenerateMedicalRecordPDF`
  - Template profissional consolidando todas as seções
  - Logs de auditoria e rate limiting (1/hora)
  - Arquivo armazenado no storage público e listado como documento

#### RF006 - Filtros e Busca
- **Descrição**: Sistema deve permitir filtrar e buscar informações no prontuário
- **Prioridade**: Média
- **Status**: 🔄 Parcialmente Implementado
- **Funcionalidades**:
  - Busca textual por diagnóstico, sintomas, notas e médico ✅
  - Filtro por período (data início/fim) ✅
  - Filtros avançados por especialidade/médico (backlog)
  - Persistência dos filtros entre abas (backlog)

#### RF007 - Timeline Visual
- **Descrição**: Exibir histórico médico em formato de timeline cronológica
- **Prioridade**: Alta
- **Status**: ✅ Parcialmente Implementado
- **Detalhes**:
  - Ordenação cronológica (mais recente primeiro)
  - Expansão/recolhimento de itens
  - Indicadores visuais de tipo de evento
  - Agrupamento por período (mês/ano)

#### RF008 - Upload de Documentos
- **Descrição**: Permitir anexar documentos ao prontuário
- **Prioridade**: Média
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Upload autenticado por pacientes e médicos com validação
  - Armazenamento seguro no disco público e registro em `medical_documents`
  - Categorização por tipo/visibilidade e associação com consultas
  - Registro automático na aba Documentos do prontuário

#### RF009 - Prescrições Digitais
- **Descrição**: Exibir e gerenciar prescrições médicas digitais
- **Prioridade**: Alta
- **Status**: 🔄 Parcialmente Implementado
- **Funcionalidades**:
  - Visualização de prescrições emitidas com status e validade ✅
  - Histórico consolidado por paciente ✅
  - Alertas automáticos e emissão pelo médico (pendente)

#### RF010 - Dados Vitais e Sinais
- **Descrição**: Registrar e visualizar evolução de sinais vitais
- **Prioridade**: Baixa
- **Status**: ❌ Não Implementado
- **Informações**:
  - Pressão arterial
  - Temperatura
  - Frequência cardíaca
  - Peso e altura (histórico)
  - IMC e evolução

### Requisitos Não Funcionais

#### NF001 - Segurança e Privacidade
- **Descrição**: Dados médicos devem ser protegidos com segurança máxima
- **Prioridade**: Crítica
- **Status**: 🔄 Parcialmente Implementado
- **Medidas Necessárias**:
  - Criptografia de dados sensíveis em repouso
  - Controle de acesso baseado em roles e relacionamentos
  - Logs de auditoria para todos os acessos
  - Políticas de retenção de dados
  - Compliance com LGPD

#### NF002 - Performance
- **Descrição**: Página deve carregar rapidamente mesmo com grande volume de dados
- **Prioridade**: Alta
- **Status**: 🔄 Parcialmente Implementado
- **Medidas**:
  - Paginação de consultas
  - Lazy loading de seções
  - Cache de dados frequentemente acessados
  - Indexação adequada no banco de dados
  - Otimização de queries

#### NF003 - Disponibilidade
- **Descrição**: Prontuário deve estar sempre disponível para acesso
- **Prioridade**: Alta
- **Status**: ✅ Implementado
- **Detalhes**:
  - Backup diário dos dados
  - Redundância de sistemas
  - Monitoramento de disponibilidade

#### NF004 - Usabilidade
- **Descrição**: Interface deve ser intuitiva e acessível
- **Prioridade**: Alta
- **Status**: 🔄 Em Desenvolvimento
- **Aspectos**:
  - Design responsivo (mobile-friendly)
  - Acessibilidade (WCAG)
  - Navegação clara entre seções
  - Feedback visual para ações

#### NF005 - Escalabilidade
- **Descrição**: Sistema deve suportar crescimento de dados ao longo do tempo
- **Prioridade**: Média
- **Status**: ✅ Implementado
- **Detalhes**:
  - Arquitetura preparada para escala
  - Estratégias de arquivamento de dados antigos
  - Otimização de armazenamento

---

## ⚖️ Regras de Negócio

### RB001 - Acesso ao Prontuário
- **Regra**: Pacientes podem acessar apenas seu próprio prontuário
- **Validação**: Middleware verifica se `patient_id` do usuário autenticado corresponde ao prontuário acessado
- **Exceções**: Nenhuma

### RB002 - Acesso de Médicos
- **Regra**: Médicos podem acessar prontuários apenas de pacientes que tiveram consultas com eles
- **Validação**: Verificar existência de `Appointment` com `doctor_id` e `patient_id` correspondentes
- **Exceções**: Em consulta em andamento, acesso é permitido mesmo se não houver histórico prévio

### RB003 - Dados Sensíveis
- **Regra**: Informações médicas sensíveis devem ser protegidas
- **Campos Sensíveis**:
  - Histórico médico (`medical_history`)
  - Alergias (`allergies`)
  - Medicações atuais (`current_medications`)
  - Diagnósticos e CID-10
  - Resultados de exames

### RB004 - Retenção de Dados
- **Regra**: Prontuários médicos devem ser mantidos por período mínimo de 20 anos
- **Implementação**: Soft delete, não permite exclusão permanente
- **Exceções**: Apenas administradores podem excluir (com auditoria completa)

### RB005 - Auditoria
- **Regra**: Todas as ações no prontuário devem ser registradas
- **Ações Auditadas**:
  - Visualização do prontuário
  - Exportação em PDF
  - Upload de documentos
  - Alterações em dados do paciente
- **Registro**: Incluir `user_id`, `timestamp`, `action`, `ip_address`

### RB006 - Exportação de PDF
- **Regra**: Exportações devem ser geradas de forma assíncrona
- **Validação**: 
  - Limite de 1 exportação por hora por paciente
  - Arquivo disponível por 24 horas
  - Log de auditoria obrigatório
- **Exceções**: Nenhuma

### RB007 - Dados das Consultas
- **Regra**: Apenas consultas com status `completed` aparecem no prontuário
- **Validação**: Query filtra `status = 'completed'`
- **Exceções**: Durante consulta em andamento, médico pode ver consulta atual mesmo que não esteja completada

### RB008 - Atualização de Dados do Paciente
- **Regra**: Pacientes podem atualizar apenas campos permitidos
- **Campos Editáveis pelo Paciente**:
  - Contato de emergência
  - Alergias (com validação médica recomendada)
  - Histórico médico (para complemento)
- **Campos Restritos**:
  - Dados de consultas (apenas médicos podem editar)
  - Diagnósticos
  - Prescrições

### RB009 - Visibilidade de Consultas
- **Regra**: Pacientes veem todas as suas consultas completadas, independente do médico
- **Regra para Médicos**: Médicos veem apenas consultas que realizaram, exceto durante consulta em andamento
- **Validação**: Queries filtradas por relacionamento

### RB010 - Anonimização para Estatísticas
- **Regra**: Dados podem ser anonimizados para fins estatísticos e pesquisa
- **Implementação**: Função de anonimização remove identificadores pessoais
- **Acesso**: Apenas administradores com permissão especial

---

## 🔧 Funcionalidades

### Funcionalidades Implementadas ✅

#### 1. Visualização Básica do Prontuário
- **Arquivo**: `resources/js/pages/Patient/MedicalRecord.vue`
- **Controller**: `app/Http/Controllers/Patient/PatientMedicalRecordController.php`
- **Status**: ✅ Funcional
- **Funcionalidades**:
  - Exibição de dados pessoais do paciente (nome, idade, gênero, ID)
  - Header com informações principais
  - Botão de exportação PDF (placeholder)

#### 2. Interface de Tabs/Seções
- **Status**: ✅ Implementado (interface)
- **Seções Criadas**:
  - Histórico ✅
  - Consultas 📋 (interface criada, conteúdo pendente)
  - Prescrições 📋 (interface criada, conteúdo pendente)
  - Exames 📋 (interface criada, conteúdo pendente)
  - Documentos 📋 (interface criada, conteúdo pendente)
  - Evolução 📋 (interface criada, conteúdo pendente)
  - Consultas Futuras 📋 (interface criada, conteúdo pendente)

#### 3. Timeline de Consultas
- **Status**: ✅ Parcialmente Implementado
- **Funcionalidades**:
  - Exibição de consultas completadas em formato de timeline
  - Expansão/recolhimento de detalhes de cada consulta
  - Visualização de informações básicas (data, médico, especialidade)
  - Exibição de detalhes expandidos (diagnóstico, CID-10, sintomas, exames, orientações)
  - Suporte para anexos e prescrições (quando existirem)

#### 4. Integração com Appointments
- **Status**: ✅ Implementado
- **Funcionalidades**:
  - Busca de consultas completadas do paciente
  - Carregamento de dados do médico e especialidades
  - Extração de metadados das consultas (diagnóstico, CID-10, etc.)
  - Ordenação cronológica (mais recente primeiro)

#### 5. Camada de Serviço e Policy
- **Status**: ✅ Implementado
- **Componentes**:
  - `MedicalRecordService` agrega consultas, prescrições, exames, documentos e sinais vitais
  - `MedicalRecordPolicy` garante permissões de `view`, `export`, `uploadDocument` e `updatePersonalData`
  - Controllers (paciente e médico) passaram a consumir o service e registrar auditoria

#### 6. Exportação PDF Assíncrona
- **Status**: ✅ Implementado
- **Fluxo**:
  - Botão "Exportar PDF" dispara `GenerateMedicalRecordPDF`
  - Job renderiza `resources/views/pdf/medical-record.blade.php` via `barryvdh/laravel-dompdf`
  - Arquivo é salvo em `storage/medical-records/exports/{patient}` e registrado como `MedicalDocument`
  - Rate limiting (1/h) e logs de auditoria por usuário

#### 7. Upload e Gestão de Documentos
- **Status**: ✅ Implementado
- **Detalhes**:
  - Controller `MedicalRecordDocumentController` para pacientes e médicos
  - Validação de tipo/tamanho, categorização e visibilidade
  - Integração direta com a aba Documentos no frontend

#### 8. Visualização Completa para Médicos
- **Status**: ✅ Implementado
- **Destaques**:
  - Página `Doctor/PatientMedicalRecord.vue` reaproveita as mesmas abas do paciente
  - Rotas dedicadas `/doctor/patients/{patient}/medical-record` e `/doctor/.../export`
  - Validação automática do vínculo (consulta prévia ou em andamento)

### Funcionalidades Parcialmente Implementadas 🔄

#### 1. Evolução Clínica com Visualizações
- **Status**: 🔄 Dados de sinais vitais já são exibidos, porém apenas em formato de lista
- **Próximos passos**:
  - Implementar gráficos e comparativos (peso, IMC, pressão média)
  - Agrupar registros por período e destacar outliers

#### 2. Busca e Filtros Avançados
- **Status**: 🔄 Busca textual e filtro por período entregues
- **Pendências**:
  - Filtros combinados por especialidade/tipo de evento
  - Histórico de filtros salvos por usuário

#### 3. Alertas Inteligentes
- **Status**: 🔄 Estrutura básica criada (audit log e prescrições)
- **Pendências**:
  - Alertas para prescrições expirando
  - Avisos de interações medicamentosas
  - Notificações sobre novos documentos/exames

### Funcionalidades Não Implementadas ❌

#### 1. Notas Clínicas e Comentários Colaborativos
- Permitir que médicos adicionem evoluções textuais e anotações privadas
- Histórico versionado e possibilidade de anexar multimídia

#### 2. Alertas Proativos e Notificações
- Integração com módulo de notificações para avisar paciente/médico sobre:
  - Exportação concluída
  - Novo documento/exame disponível
  - Prescrições próximas do vencimento

#### 3. Automação e IA
- Sugestões de exames baseadas no histórico do paciente
- Detecção de padrões de risco (ex.: hipertensão persistente)
- Anonimização automática para relatórios populacionais

#### 4. Compartilhamento Seguro Externo
- Geração de links temporários com token para especialistas externos
- Registro de auditoria específico por compartilhamento

#### 5. Dashboard de Auditoria
- Visualização centralizada dos logs de acesso/exportação/upload
- Filtros por período, usuário e tipo de ação

---

## 🎨 UX Detalhado

### Fluxo Inicial Proposto pelo Usuário

**Descrição**: O usuário descreveu um fluxo inicial de UX, mas não forneceu detalhes específicos. Baseado na análise do código existente e melhores práticas de UX para prontuários médicos, apresentamos abaixo uma proposta de UX otimizada.

### Proposta de UX Melhorada

#### 1. Página de Entrada (Medical Records)
- **Header Fixo**:
  - Foto e nome do paciente
  - Informações básicas (idade, gênero, data de nascimento)
  - ID do paciente
  - Botão de exportação PDF (com loading state)
  
- **Barra de Tabs Horizontal**:
  - Design claro com indicador visual da aba ativa
  - Scroll horizontal em dispositivos móveis
  - Contadores de itens (quando aplicável)
  - Ícones para melhor identificação

- **Seção Principal**:
  - Conteúdo específico da aba selecionada
  - Scroll independente
  - Empty states informativos
  - Loading states durante carregamento

#### 2. Aba Histórico
- **Timeline Vertical**:
  - Eventos ordenados cronologicamente (mais recente no topo)
  - Conector visual entre eventos
  - Ícones por tipo de evento (consulta, exame, prescrição)
  - Cards expansíveis com animação suave
  
- **Card de Consulta**:
  - Header com data formatada (ex: "15 de Julho, 2024")
  - Badge de status (Finalizada, Em andamento)
  - Informações principais (médico, especialidade)
  - Botão "Ver Detalhes" para expansão
  
- **Detalhes Expandidos**:
  - Diagnóstico e CID-10
  - Sintomas relatados
  - Exames solicitados
  - Orientações médicas
  - Links para anexos e prescrições
  - Gráficos de sinais vitais (quando disponível)

#### 3. Aba Consultas
- **Lista de Consultas**:
  - Cards menores com informações resumidas
  - Ordenação e filtros no topo
  - Busca textual
  - Filtros por período, médico, especialidade
  
- **Modal/Detalhes**:
  - Modal full-screen em mobile
  - Sidebar em desktop
  - Visualização completa de todos os dados
  - Possibilidade de download de documentos

#### 4. Aba Prescrições
- **Lista de Prescrições**:
  - Cards com data de emissão
  - Status de validade (ativo, expirado)
  - Médico responsável
  - Quantidade de medicamentos
  
- **Detalhes da Prescrição**:
  - Lista de medicamentos
  - Posologia detalhada
  - Instruções especiais
  - Validade
  - Download PDF da receita

#### 5. Aba Exames
- **Lista de Exames**:
  - Agrupamento por status (solicitados, em andamento, concluídos)
  - Filtro por tipo (laboratorial, imagem, outros)
  - Indicadores visuais de status
  
- **Detalhes do Exame**:
  - Informações do exame
  - Resultados (quando disponível)
  - Download do laudo/anexo
  - Data de solicitação e conclusão

#### 6. Aba Documentos
- **Galeria de Documentos**:
  - Grid ou lista de documentos
  - Thumbnails para imagens
  - Ícones por tipo de arquivo
  - Filtro por categoria
  
- **Upload de Documentos**:
  - Botão de upload proeminente
  - Drag & drop
  - Preview antes de confirmar
  - Categorização obrigatória

#### 7. Aba Evolução
- **Gráficos e Métricas**:
  - Evolução de peso/altura/IMC
  - Pressão arterial ao longo do tempo
  - Outros sinais vitais
  - Marcadores de eventos importantes (consultas, exames)

#### 8. Aba Consultas Futuras
- **Lista de Agendamentos**:
  - Cards com data/hora
  - Médico e especialidade
  - Status (agendada, confirmada)
  - Ações rápidas (cancelar, reagendar)

### Melhorias de UX Sugeridas

#### 1. Empty States Informativos
- **Problema**: Quando não há dados, a página fica vazia
- **Solução**: Criar empty states atrativos com:
  - Mensagem amigável
  - Ilustração ou ícone
  - Call-to-action quando aplicável
  - Exemplo: "Você ainda não tem consultas registradas. Agende sua primeira consulta!"

#### 2. Loading States
- **Problema**: Durante carregamento, não há feedback visual
- **Solução**: 
  - Skeleton screens para cada seção
  - Spinner durante busca de dados
  - Progress bar para exportação PDF

#### 3. Feedback de Ações
- **Problema**: Ações como exportar PDF não têm feedback
- **Solução**:
  - Toast notifications para sucesso/erro
  - Confirmação para ações destrutivas
  - Feedback visual em tempo real

#### 4. Responsividade
- **Problema**: Interface pode não funcionar bem em mobile
- **Solução**:
  - Testar em diferentes tamanhos de tela
  - Adaptar layout para mobile
  - Navegação touch-friendly

#### 5. Acessibilidade
- **Problema**: Pode não ser acessível para pessoas com deficiência
- **Solução**:
  - Contraste adequado de cores
  - Navegação por teclado
  - Screen reader friendly
  - ARIA labels apropriados

#### 6. Performance
- **Problema**: Muitas consultas podem tornar a página lenta
- **Solução**:
  - Paginação ou scroll infinito
  - Lazy loading de imagens
  - Cache de dados
  - Virtualização de listas longas

#### 7. Busca e Filtros
- **Problema**: Sem busca, difícil encontrar informações específicas
- **Solução**:
  - Barra de busca sempre visível
  - Filtros avançados (sidebar ou modal)
  - Histórico de buscas recentes
  - Sugestões de busca

---

## 🔄 Fluxo de Interação

### Fluxo 1: Paciente Visualiza Seu Prontuário

```
1. Paciente faz login
   ↓
2. Navega para "Prontuário" no menu lateral
   ↓
3. Sistema valida acesso (middleware: auth, verified, patient)
   ↓
4. Controller busca dados:
   - Dados do paciente (Patient model)
   - Consultas completadas (Appointments com status='completed')
   - Dados do médico de cada consulta
   ↓
5. Dados são formatados e enviados para o frontend via Inertia
   ↓
6. Frontend renderiza página MedicalRecord.vue
   ↓
7. Por padrão, mostra aba "Histórico" com timeline de consultas
   ↓
8. Usuário pode:
   - Expandir/recolher detalhes de cada consulta
   - Navegar entre abas
   - Clicar em "Exportar PDF" (placeholder atual)
```

### Fluxo 2: Médico Visualiza Prontuário do Paciente (Não Implementado)

```
1. Médico faz login
   ↓
2. Acessa lista de pacientes ou inicia consulta
   ↓
3. Clica em "Ver Prontuário" do paciente específico
   ↓
4. Sistema valida:
   - Médico autenticado
   - Relacionamento com paciente (tem consulta com ele OU consulta em andamento)
   ↓
5. Se válido:
   - Controller busca dados completos do prontuário
   - Retorna para frontend
   ↓
6. Se inválido:
   - Retorna erro 403 (Forbidden)
   ↓
7. Frontend exibe prontuário (similar ao do paciente, mas com informações adicionais)
   ↓
8. Médico pode:
   - Visualizar histórico completo
   - Adicionar notas/anotações (futuro)
   - Ver contexto durante consulta em andamento
```

### Fluxo 3: Exportação de PDF (Não Implementado)

```
1. Paciente clica em "Exportar Prontuário (PDF)"
   ↓
2. Frontend envia requisição para API
   ↓
3. Backend valida:
   - Limite de exportações (1 por hora)
   - Paciente autenticado
   ↓
4. Cria job assíncrono para gerar PDF
   ↓
5. Retorna resposta imediata: "PDF sendo gerado..."
   ↓
6. Job processa:
   - Busca todos os dados do prontuário
   - Gera PDF usando template
   - Salva temporariamente (storage)
   - Registra log de auditoria
   ↓
7. Envia notificação (email/push) quando PDF estiver pronto
   ↓
8. Usuário recebe link para download (válido por 24h)
   ↓
9. Após 24h, arquivo é removido automaticamente
```

### Fluxo 4: Upload de Documento (Não Implementado)

```
1. Paciente acessa aba "Documentos"
   ↓
2. Clica em "Adicionar Documento"
   ↓
3. Seleciona arquivo (drag & drop ou botão)
   ↓
4. Preenche metadados:
   - Categoria (exame, receita, laudo, etc.)
   - Descrição (opcional)
   - Data do documento
   - Associação com consulta (opcional)
   ↓
5. Frontend valida:
   - Tipo de arquivo permitido
   - Tamanho máximo
   ↓
6. Envia para backend
   ↓
7. Backend valida:
   - Autenticação
   - Permissão para upload
   - Validações de segurança (antivírus scan, se disponível)
   ↓
8. Faz upload para storage (S3 ou local)
   ↓
9. Cria registro em `medical_documents`
   ↓
10. Registra log de auditoria
   ↓
11. Retorna sucesso ao frontend
   ↓
12. Frontend atualiza lista de documentos
```

---

## 🔗 Integrações com Outros Módulos

### 1. Módulo de Consultas (Appointments)

#### Relacionamento
- **Tipo**: Prontuário consome dados de Appointments
- **Direção**: Appointments → Medical Records (um para muitos)

#### Dados Utilizados
- Consultas completadas (`status = 'completed'`)
- Metadados das consultas (`metadata` JSON):
  - `diagnosis` - Diagnóstico
  - `cid10` - Código CID-10
  - `symptoms` - Sintomas relatados
  - `requested_exams` - Exames solicitados
  - `instructions` - Orientações médicas
  - `attachments` - Anexos (array de URLs)
  - `prescriptions` - Prescrições (array de URLs)
- Timestamps: `scheduled_at`, `started_at`, `ended_at`
- Relacionamentos: `doctor`, `patient`

#### Impacto
- Quando uma consulta é finalizada, automaticamente aparece no prontuário
- Não há impacto reverso (prontuário não altera consultas)

#### Arquivos Relacionados
- `app/Models/Appointments.php`
- `app/Models/Patient.php` (relacionamento `appointments()`)
- `app/Http/Controllers/Patient/PatientMedicalRecordController.php`

### 2. Módulo de Pacientes (Patients)

#### Relacionamento
- **Tipo**: Um para um
- **Direção**: Patient → Medical Record (o prontuário pertence ao paciente)

#### Dados Utilizados
- Informações pessoais:
  - `date_of_birth` - Para calcular idade
  - `gender` - Exibição formatada
- Dados médicos básicos:
  - `medical_history` - Histórico médico geral
  - `allergies` - Alergias conhecidas
  - `current_medications` - Medicações em uso
  - `blood_type` - Tipo sanguíneo
  - `height`, `weight` - Para cálculo de IMC
  - `insurance_provider`, `insurance_number` - Dados do plano

#### Impacto
- Alterações nos dados do paciente refletem no prontuário
- Paciente pode editar alguns campos diretamente (quando implementado)

#### Arquivos Relacionados
- `app/Models/Patient.php`
- `app/Http/Controllers/Patient/PatientMedicalRecordController.php`

### 3. Módulo de Médicos (Doctors)

#### Relacionamento
- **Tipo**: Indireto através de Appointments
- **Direção**: Doctor → Appointments → Medical Records

#### Dados Utilizados
- Informações do médico em cada consulta:
  - Nome do médico (`doctor.user.name`)
  - Especialidades (`doctor.specializations`)
  - Avatar do médico (opcional)

#### Impacto
- Médicos podem visualizar prontuários de seus pacientes
- Informações do médico aparecem no histórico de consultas

#### Arquivos Relacionados
- `app/Models/Doctor.php`
- `app/Http/Controllers/Doctor/PatientDetailsController.php` (futuro)

### 4. Módulo de Autenticação (Auth)

#### Relacionamento
- **Tipo**: Dependência de segurança
- **Direção**: Auth → Medical Records (prontuário depende de autenticação)

#### Funcionalidades Utilizadas
- Middleware de autenticação (`auth`, `verified`)
- Middleware de role (`patient`, `doctor`)
- Políticas de acesso (quando implementado `MedicalRecordPolicy`)

#### Impacto
- Prontuário só é acessível para usuários autenticados
- Controle de acesso baseado em roles
- Logs de auditoria vinculados ao usuário autenticado

#### Arquivos Relacionados
- `app/Http/Middleware/`
- `routes/web.php` (middleware nas rotas)
- `app/Policies/MedicalRecordPolicy.php` (a ser criado)

### 5. Módulo de Prescrições (Futuro)

#### Relacionamento Previsto
- **Tipo**: Um para muitos (um prontuário tem muitas prescrições)
- **Direção**: Medical Records → Prescriptions

#### Dados Planejados
- Lista de prescrições por paciente
- Histórico de medicamentos prescritos
- Status de validade das prescrições
- Alertas de interações medicamentosas

#### Impacto Futuro
- Prescrições aparecem na aba específica do prontuário
- Histórico de medicamentos pode ser consultado

### 6. Módulo de Exames (Futuro)

#### Relacionamento Previsto
- **Tipo**: Um para muitos (um prontuário tem muitos exames)
- **Direção**: Medical Records → Examinations

#### Dados Planejados
- Lista de exames solicitados e realizados
- Resultados de exames
- Laudos e anexos
- Status de cada exame

#### Impacto Futuro
- Exames aparecem na aba específica do prontuário
- Links para consultas relacionadas

### 7. Módulo de Documentos (Futuro)

#### Relacionamento Previsto
- **Tipo**: Um para muitos (um prontuário tem muitos documentos)
- **Direção**: Medical Records → Medical Documents

#### Dados Planejados
- Lista de documentos anexados
- Categorização de documentos
- Metadados de upload
- Associação com consultas

#### Impacto Futuro
- Documentos aparecem na aba específica do prontuário
- Possibilidade de upload e download

### 8. Módulo de Notificações (Futuro)

#### Relacionamento Previsto
- **Tipo**: Integração para notificações
- **Direção**: Medical Records → Notifications

#### Funcionalidades Planejadas
- Notificação quando PDF é gerado
- Notificação de novos documentos disponíveis
- Notificação de prescrições expirando

---

## ✅ Status de Implementação

### Implementado ✅

#### 1. Service Layer para Medical Records
- **Arquivo**: `app/Services/MedicalRecordService.php` ✅
- **Métodos Implementados**:
  - `getPatientMedicalRecord(Patient $patient): array` ✅
  - `canDoctorViewPatientRecord(Doctor $doctor, Patient $patient): bool` ✅
  - `prepareDataForExport(Patient $patient): array` ✅
  - `getAppointmentsForRecord(Patient $patient, array $filters = []): Collection` ✅
  - `logAccess(User $user, Patient $patient, string $action): void` ✅

#### 2. Medical Record Policy
- **Arquivo**: `app/Policies/MedicalRecordPolicy.php` ✅
- **Métodos Implementados**:
  - `view(User $user, Patient $patient): bool` ✅
  - `viewAny(User $user): bool` ✅
  - `export(User $user, Patient $patient): bool` ✅
  - `uploadDocument(User $user, Patient $patient): bool` ✅

#### 3. Implementação das Abas
- **Aba Consultas**: ✅ Implementado
  - Lista completa de consultas
  - Filtros e busca
  - Ordenação
  
- **Aba Prescrições**: ✅ Implementado
  - Modelo `Prescription` ✅
  - Controller para listar prescrições ✅
  - Visualização de prescrições ativas e expiradas ✅
  
- **Aba Diagnósticos**: ✅ Implementado
  - Modelo `Diagnosis` ✅
  - Visualização com CID-10 ✅
  
- **Aba Exames**: ✅ Implementado
  - Modelo `Examination` ✅
  - Visualização de exames solicitados e resultados ✅
  
- **Aba Documentos**: ✅ Implementado
  - Modelo `MedicalDocument` ✅
  - Upload e download de documentos ✅
  
- **Aba Atestados**: ✅ Implementado
  - Modelo `MedicalCertificate` ✅
  - Visualização de atestados emitidos ✅
  
- **Aba Sinais Vitais**: ✅ Implementado
  - Modelo `VitalSign` ✅
  - Visualização de histórico de sinais vitais ✅
  
- **Aba Anotações Clínicas**: ✅ Implementado
  - Modelo `ClinicalNote` ✅
  - Visualização de anotações compartilhadas (não privadas) ✅

### 🔄 Melhorias Futuras

#### 1. Funcionalidades Adicionais
- Gráficos de evolução de sinais vitais
- Alertas automáticos de interações medicamentosas
- Integração com laboratórios para status automático de exames
- Notificações push em tempo real
- Dashboard de métricas de saúde para pacientes
  - Componente Vue para exibir prescrições
  
- **Aba Exames**:
  - Modelo `Examination` (criar migration)
  - Controller para listar exames
  - Componente Vue para exibir exames
  
- **Aba Documentos**:
  - Modelo `MedicalDocument` (criar migration)
  - Controller para upload/download
  - Componente Vue para galeria de documentos
  
- **Aba Evolução**:
  - Componente Vue com gráficos (Chart.js ou similar)
  - Endpoint API para dados de evolução
  - Cálculos de métricas
  
- **Aba Consultas Futuras**:
  - Integração com Appointments (status `scheduled`)
  - Lista de próximas consultas
  - Ações rápidas

#### 4. Exportação PDF
- **Biblioteca**: `composer require barryvdh/laravel-dompdf`
- **Arquivos Necessários**:
  - Job: `app/Jobs/GenerateMedicalRecordPDF.php`
  - Controller method: `exportPDF()`
  - Template Blade: `resources/views/pdf/medical-record.blade.php`
  - Rota: `POST /patient/medical-records/export`
  - Queue configuration

#### 5. Visualização para Médicos
- **Controller**: `app/Http/Controllers/Doctor/DoctorPatientMedicalRecordController.php`
- **Rota**: `/doctor/patient/{id}/medical-record`
- **Página Vue**: `resources/js/pages/Doctor/PatientMedicalRecord.vue`
- **Funcionalidades**:
  - Visualização completa do prontuário
  - Contexto durante consulta
  - Possibilidade de adicionar notas (futuro)

#### 6. Busca e Filtros
- **Frontend**: Componente de busca e filtros
- **Backend**: Endpoints API para filtros
- **Funcionalidades**:
  - Busca textual (full-text search)
  - Filtro por período
  - Filtro por médico
  - Filtro por especialidade
  - Filtro por tipo de evento

### Prioridade Média 🟡

#### 7. Sistema de Auditoria
- **Tabela**: `medical_record_audit_logs` (ou usar pacote como `spatie/laravel-activitylog`)
- **Campos**: `user_id`, `patient_id`, `action`, `resource_type`, `resource_id`, `ip_address`, `metadata`, `created_at`
- **Funcionalidades**:
  - Registrar todos os acessos
  - Registrar exportações
  - Registrar uploads
  - Dashboard de auditoria (futuro)

#### 8. Upload de Documentos
- **Storage**: Configurar storage (local ou S3)
- **Controller**: Método `uploadDocument()`
- **Validações**:
  - Tipos de arquivo permitidos
  - Tamanho máximo
  - Scan de vírus (futuro)
- **Frontend**: Componente de upload com drag & drop

#### 9. Dados Vitais e Sinais
- **Tabela**: `vital_signs`
- **Modelo**: `app/Models/VitalSign.php`
- **Campos**: `appointment_id`, `patient_id`, `blood_pressure`, `temperature`, `heart_rate`, `weight`, `height`, `notes`
- **Funcionalidades**:
  - Registro durante consulta
  - Histórico de evolução
  - Gráficos (futuro)

#### 10. Atualização de Dados do Paciente
- **Formulário**: Permissão para paciente editar campos permitidos
- **Validações**: Campos editáveis vs. restritos
- **Controller**: Método `updatePersonalData()`
- **Frontend**: Componente de edição inline ou modal

### Prioridade Baixa 🟢

#### 11. Gráficos e Visualizações
- **Biblioteca**: Chart.js ou Recharts
- **Gráficos Planejados**:
  - Evolução de peso/IMC
  - Pressão arterial ao longo do tempo
  - Frequência de consultas por período
  - Distribuição de diagnósticos

#### 12. Notificações
- **Integração**: Sistema de notificações existente
- **Notificações Planejadas**:
  - PDF pronto para download
  - Novo documento disponível
  - Prescrição expirando
  - Novo resultado de exame

#### 13. Anonimização de Dados
- **Funcionalidade**: Para fins de pesquisa e estatísticas
- **Implementação**: Função que remove identificadores pessoais
- **Acesso**: Apenas administradores com permissão especial

#### 14. Compartilhamento Seguro
- **Funcionalidade**: Paciente compartilha prontuário com outro médico
- **Implementação**: Link temporário com token
- **Validações**: Expiração, permissões, auditoria

---

## 💡 Recomendações de Melhoria

### 1. Arquitetura e Organização

#### Separar Dados de Apresentação
- **Problema Atual**: Controller prepara dados diretamente para o frontend
- **Solução**: Criar `MedicalRecordService` para lógica de negócio
- **Benefício**: Código mais testável e reutilizável

#### Criar Resource Classes
- **Problema Atual**: Formatação de dados no Controller
- **Solução**: Usar Laravel API Resources para formatação consistente
- **Benefício**: Padronização e reutilização

#### Implementar Repository Pattern (Opcional)
- **Problema Atual**: Queries diretas nos Controllers/Services
- **Solução**: Repositories para abstrair acesso a dados
- **Benefício**: Facilita testes e mudanças de estrutura

### 2. Segurança

#### Implementar Rate Limiting
- **Recomendação**: Limitar número de exportações PDF por hora
- **Implementação**: `RateLimiter` do Laravel
- **Benefício**: Previne abuso e sobrecarga do sistema

#### Criptografar Dados Sensíveis
- **Recomendação**: Criptografar campos sensíveis no banco
- **Implementação**: Laravel Encryption ou database encryption
- **Campos**: `medical_history`, `allergies`, `diagnosis`
- **Benefício**: Segurança adicional em caso de breach

#### Implementar Two-Factor Authentication para Acesso
- **Recomendação**: 2FA para acessar prontuário (opcional mas recomendado)
- **Implementação**: Laravel Two Factor Authentication
- **Benefício**: Camada extra de segurança

### 3. Performance

#### Implementar Cache
- **Recomendação**: Cache de dados frequentemente acessados
- **Implementação**: Redis ou Cache do Laravel
- **Dados para Cache**:
  - Lista de consultas (TTL: 5 minutos)
  - Dados básicos do paciente (TTL: 15 minutos)
- **Benefício**: Redução de carga no banco

#### Paginação Inteligente
- **Recomendação**: Paginar consultas e eventos
- **Implementação**: Laravel Pagination
- **Benefício**: Carregamento mais rápido com muitos registros

#### Lazy Loading e Virtualização
- **Recomendação**: Lazy loading de seções e virtualização de listas longas
- **Implementação**: Vue composables ou bibliotecas como `vue-virtual-scroller`
- **Benefício**: Melhor performance em frontend

#### Indexação do Banco de Dados
- **Recomendação**: Adicionar índices estratégicos
- **Campos para Indexar**:
  - `appointments.patient_id + appointments.status`
  - `appointments.scheduled_at`
  - `medical_documents.patient_id` (futuro)
- **Benefício**: Queries mais rápidas

### 4. UX/UI

#### Melhorar Empty States
- **Recomendação**: Criar empty states informativos e atrativos
- **Implementação**: Componentes Vue reutilizáveis
- **Benefício**: Melhor experiência quando não há dados

#### Implementar Loading States Consistentes
- **Recomendação**: Skeleton screens durante carregamento
- **Implementação**: Componentes Vue de skeleton
- **Benefício**: Percepção de performance melhor

#### Adicionar Feedback Visual
- **Recomendação**: Toast notifications para ações
- **Implementação**: Biblioteca de notificações (ex: `vue-toastification`)
- **Benefício**: Usuário sempre sabe o status de suas ações

#### Melhorar Responsividade
- **Recomendação**: Testar e otimizar para mobile
- **Implementação**: Mobile-first approach
- **Benefício**: Acesso de qualquer dispositivo

#### Implementar Busca com Sugestões
- **Recomendação**: Busca inteligente com autocomplete
- **Implementação**: Debounce + API de sugestões
- **Benefício**: Encontrar informações mais rápido

### 5. Funcionalidades Avançadas (Futuro)

#### Inteligência Artificial para Análise
- **Recomendação**: IA para identificar padrões e alertas
- **Funcionalidades**:
  - Detecção de interações medicamentosas
  - Alertas de possíveis condições
  - Sugestões de exames baseadas em histórico
- **Benefício**: Assistência médica proativa

#### Integração com Sistemas Externos
- **Recomendação**: Conectar com laboratórios e clínicas
- **Integrações Possíveis**:
  - Laboratórios: Importar resultados automaticamente
  - Seguradoras: Validar cobertura
  - Farmácias: Enviar prescrições digitalmente
- **Benefício**: Prontuário mais completo e integrado

#### Histórico Familiar
- **Recomendação**: Permitir registro de histórico familiar
- **Funcionalidades**:
  - Árvore genealógica de condições
  - Alertas de predisposições genéticas
- **Benefício**: Contexto clínico mais rico

#### Telemedicina Avançada
- **Recomendação**: Integrar dispositivos IoT
- **Funcionalidades**:
  - Conectar com smartwatches (sinais vitais)
  - Balanças inteligentes (peso automático)
  - Medidores de glicose (diabetes)
- **Benefício**: Dados em tempo real

### 6. Testes

#### Testes Unitários
- **Recomendação**: Testar `MedicalRecordService`
- **Cobertura**:
  - Métodos de busca e filtros
  - Validações de acesso
  - Preparação de dados para exportação
- **Arquivo**: `tests/Unit/MedicalRecordServiceTest.php`

#### Testes de Integração
- **Recomendação**: Testar fluxos completos
- **Cenários**:
  - Paciente visualiza prontuário
  - Médico visualiza prontuário de paciente
  - Exportação de PDF
  - Upload de documento
- **Arquivo**: `tests/Feature/MedicalRecordTest.php`

#### Testes de Frontend
- **Recomendação**: Testes E2E com Cypress ou Playwright
- **Cenários**:
  - Navegação entre abas
  - Expansão de consultas
  - Busca e filtros
  - Upload de documentos
- **Arquivo**: `tests/E2E/MedicalRecord.spec.ts`

### 7. Documentação

#### Documentação de API
- **Recomendação**: Documentar endpoints da API
- **Ferramenta**: Swagger/OpenAPI ou Laravel API Documentation
- **Benefício**: Facilita integrações e desenvolvimento

#### Guia de Integração
- **Recomendação**: Documentar como outros módulos podem integrar
- **Conteúdo**:
  - Como adicionar novos tipos de eventos ao prontuário
  - Como criar novas abas/seções
  - Padrões de dados esperados
- **Benefício**: Extensibilidade do módulo

#### Documentação de Regras de Negócio
- **Recomendação**: Documentar regras de negócio complexas
- **Conteúdo**:
  - Quando dados aparecem no prontuário
  - Regras de acesso e permissões
  - Retenção de dados
- **Benefício**: Alinhamento da equipe

### 8. Monitoramento e Observabilidade

#### Logs Estruturados
- **Recomendação**: Usar logs estruturados (JSON)
- **Ferramenta**: Laravel Log ou integração com sistema de log centralizado
- **Benefício**: Facilita análise e debugging

#### Métricas de Uso
- **Recomendação**: Coletar métricas de uso
- **Métricas Úteis**:
  - Número de visualizações de prontuário por dia
  - Tempo médio na página
  - Exportações de PDF por período
  - Abas mais acessadas
- **Benefício**: Insights para melhorias

#### Alertas de Segurança
- **Recomendação**: Alertas para acesso suspeito
- **Cenários**:
  - Muitas tentativas de acesso não autorizado
  - Acesso de múltiplos médicos ao mesmo prontuário em curto período
  - Exportações anômalas
- **Benefício**: Detecção precoce de problemas

---

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../index/VisaoGeral.md)** - Índice central da documentação
- **[📊 Matriz de Rastreabilidade](../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../index/Glossario.md)** - Definições de termos técnicos
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de negócio e compliance
- **[🏗️ Arquitetura](../Architecture/Arquitetura.md)** - Estrutura e padrões do sistema
- **[⚙️ Lógica de Consultas](./appointments/AppointmentsLogica.md)** - Como consultas funcionam
- **[🔐 Autenticação](./auth/AuthSystemOverview.md)** - Sistema de autenticação e permissões

### Implementações Relacionadas
- **[Patient Model](../../app/Models/Patient.php)** - Modelo de pacientes
- **[Appointments Model](../../app/Models/Appointments.php)** - Modelo de consultas
- **[MedicalRecord Controller](../../app/Http/Controllers/Patient/PatientMedicalRecordController.php)** - Controller atual
- **[MedicalRecord Vue](../../resources/js/pages/Patient/MedicalRecord.vue)** - Página Vue atual

### Rotas Relacionadas
- **Paciente**: 
  - `/patient/medical-records` (GET) - Visualizar prontuário ✅
  - `/patient/medical-records/export` (POST) - Exportar PDF ✅
  - `/patient/medical-records/documents` (POST) - Anexar documento ✅
- **Médico**: 
  - `/doctor/patients/{patient}/medical-record` (GET) - Visualizar prontuário de paciente ✅
  - `/doctor/patients/{patient}/medical-record/export` (POST) - Exportar prontuário ✅

---

## 📝 Resumo Executivo

### Estado Atual
O módulo de Prontuários Médicos está **completamente implementado**. Todas as funcionalidades principais foram desenvolvidas:
- ✅ Página Vue criada com interface de tabs completa
- ✅ Controller completo que busca todos os dados do prontuário
- ✅ Todas as abas implementadas com conteúdo real:
  - Histórico (Timeline de consultas) ✅
  - Consultas (Lista detalhada) ✅
  - Diagnósticos (com CID-10) ✅
  - Prescrições (ativas e expiradas) ✅
  - Exames (solicitados e resultados) ✅
  - Documentos (upload e download) ✅
  - Atestados (emitidos) ✅
  - Sinais Vitais (histórico) ✅
  - Anotações Clínicas (compartilhadas) ✅
- ✅ Service Layer completo (`MedicalRecordService`)
- ✅ Policy implementada (`MedicalRecordPolicy`)
- ✅ Exportação PDF funcional
- ✅ Visualização para médicos implementada
- ✅ Upload de documentos funcional
- ✅ Busca e filtros implementados
- ✅ Auditoria completa (`MedicalRecordAuditLog`)

### Funcionalidades Implementadas
1. **Service Layer** (`MedicalRecordService`) ✅
2. **Policy** (`MedicalRecordPolicy`) ✅
3. **Exportação PDF** ✅
4. **Visualização para médicos** ✅
5. **Todas as abas de conteúdo** ✅
6. **Busca e filtros** ✅
7. **Auditoria completa** ✅
8. **Integração com todos os módulos** ✅

### Melhorias Futuras
- Gráficos de evolução de sinais vitais
- Alertas automáticos de interações medicamentosas
- Integração com laboratórios para status automático de exames
- Notificações push em tempo real
- Dashboard de métricas de saúde para pacientes

---

*Última atualização: Janeiro 2025*
*Versão do documento: 2.0*
*Próxima revisão: Fevereiro 2025*

