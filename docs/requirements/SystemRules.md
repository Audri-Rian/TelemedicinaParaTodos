# 📜 Regras do Sistema

## 🎯 Objetivo
Este documento estabelece as **regras de negócio fundamentais** que governam o funcionamento do sistema de telemedicina. Ele define as políticas, restrições e comportamentos obrigatórios que garantem a segurança, conformidade legal e qualidade dos serviços médicos prestados através da plataforma. As regras aqui descritas são aplicadas em todas as camadas do sistema e devem ser rigorosamente seguidas em todas as implementações.

# 🏥 Regras de Negócio 

## 📋 Módulo Usuários e Informações

### 👥 USERS (Usuários Base)
- **Tabela central** de autenticação (polimórfica: médico OU paciente)
- **Email único** e obrigatório, verificação obrigatória
- **Senha segura** (mínimo 8 caracteres, maiúsculas, números)
- **Status**: ativo, inativo, suspenso, bloqueado
- **Soft delete** para auditoria completa
- **Autenticação**: Login exclusivamente por email (não username)
- **Rate limiting**: Máximo 5 tentativas de login por IP
- **Verificação de email**: Obrigatória antes do primeiro acesso

### 👨‍⚕️ DOCTORS (Médicos)
- **Extensão de USERS** com relacionamento 1:1
- **CRM obrigatório** e único por estado/região
- **Especialidade principal** obrigatória (mínimo 1, máximo ilimitado)
- **Controle de agenda** e disponibilidade para consultas
- **Apenas ativos** podem receber agendamentos
- **Validação de CRM**: Formato obrigatório (números + UF)
- **Especializações**: Vinculação N:N com tabela specializations
- **Biografia**: Campo opcional para descrição profissional
- **Licença médica**: Número e data de validade obrigatórios
- **Taxa de consulta**: Valor definido pelo médico (opcional)

### 👤 PATIENTS (Pacientes)
- **Extensão de USERS** com relacionamento 1:1
- **Data de nascimento** obrigatória para cálculos médicos
- **Gênero** obrigatório (male, female, other)
- **Telefone** obrigatório para contato
- **Contato de emergência** obrigatório após primeira etapa de autenticação
- **Consentimento explícito** para telemedicina (não obrigatório no registro inicial)
- **Histórico médico** para diagnósticos precisos (não obrigatório no registro inicial)
- **Alergias**: Campo opcional mas altamente recomendado
- **Medicamentos atuais**: Campo opcional
- **Tipo sanguíneo**: Campo opcional
- **Dados antropométricos**: Altura e peso opcionais
- **Seguro saúde**: Informações opcionais

## 🔐 Módulo de Autenticação

### 🎫 Gerenciamento de Tokens
- **Token obrigatório**: Sistema deve gerar e salvar token de autenticação no login
- **Laravel Sanctum**: Utilização obrigatória para autenticação stateless
- **Persistência**: Tokens armazenados na tabela `personal_access_tokens`
- **Validação**: Token deve ser validado em todas as requisições protegidas
- **Expiração**: Tokens expiram após 24 horas de inatividade
- **Renovação**: Tokens podem ser renovados automaticamente se usuário estiver ativo

### 📝 Fluxo de Registro com Autenticação Automática
- **Cadastro direto**: Usuário se cadastra e é autenticado automaticamente
- **Geração de token**: Token criado imediatamente após registro bem-sucedido
- **Redirecionamento específico**: Usuário vai diretamente para páginas do seu tipo
  - **Médicos**: Redirecionamento para `/doctor/dashboard`
  - **Pacientes**: Redirecionamento para `/patient/dashboard`
- **Sem verificação de email**: Login automático não requer verificação prévia
- **Validação posterior**: Email deve ser verificado em até 48 horas

### 🔑 Regras de Token
- **Único por sessão**: Um token ativo por usuário por dispositivo
- **Revogação automática**: Token anterior é revogado ao criar novo
- **Logout**: Token deve ser revogado ao fazer logout
- **Segurança**: Tokens são criptografados e armazenados de forma segura
- **Auditoria**: Log de criação, uso e revogação de tokens

### 🚪 Fluxo de Login
1. **Validação de credenciais**: Email e senha validados
2. **Verificação de status**: Usuário deve estar ativo
3. **Geração de token**: Token Sanctum criado automaticamente
4. **Armazenamento**: Token salvo na base de dados
5. **Resposta**: Token retornado para o frontend
6. **Redirecionamento**: Baseado no tipo de usuário (doctor/patient)

### 🔄 Renovação de Token
- **Automática**: Token renovado a cada requisição bem-sucedida
- **Manual**: Endpoint para renovação explícita disponível
- **Timeout**: 24 horas de inatividade para expiração
- **Notificação**: Usuário notificado antes da expiração (2 horas antes)

## 📅 Módulo de Agendamentos (Appointments)

### 🕐 Ciclo de Vida das Consultas
- **SCHEDULED**: Consulta agendada e confirmada
- **IN_PROGRESS**: Consulta em andamento (vídeo ativo)
- **COMPLETED**: Consulta finalizada com sucesso
- **CANCELLED**: Consulta cancelada
- **NO_SHOW**: Paciente não compareceu
- **RESCHEDULED**: Consulta reagendada

### ⏰ Regras Temporais
- **Início antecipado**: Consulta pode ser iniciada até 15 minutos antes do horário
- **Cancelamento**: Permitido até 2 horas antes do horário agendado
- **No-show automático**: Sistema marca como no-show após 30 minutos de tolerância
- **Duração mínima**: 15 minutos por consulta
- **Duração máxima**: 120 minutos por consulta

### 🔐 Segurança de Agendamentos
- **Código de acesso**: Gerado automaticamente e único por consulta
- **Validação de conflitos**: Médico não pode ter duas consultas simultâneas
- **Fuso horário**: Todas as datas armazenadas em UTC
- **Auditoria**: Log completo de todas as alterações de status

## 📹 Módulo de Videoconferência

### 🎥 Regras de Chamadas
- **Autenticação obrigatória**: Ambos os usuários devem estar logados
- **Canais privados**: Cada usuário possui canal único `video-call.{user_id}`
- **Conexão P2P**: Utiliza PeerJS para eficiência de banda
- **Permissões de mídia**: Câmera e microfone obrigatórios
- **Gravação**: Opcional, apenas com consentimento explícito
- **Qualidade**: Adaptativa baseada na conexão

### 🔒 Segurança de Vídeo
- **Criptografia**: Todas as comunicações criptografadas
- **Timeout**: Conexão expira após 2 horas inativas
- **Validação de permissões**: Verificação em cada requisição
- **Logs de acesso**: Registro de todas as tentativas de conexão

## 🏥 Módulo de Especializações

### 📚 Gestão de Especializações
- **Nome único**: Não pode haver especializações duplicadas
- **Máximo 100 caracteres**: Limitação de tamanho do nome
- **Status ativo/inativo**: Controle de disponibilidade
- **API pública**: Endpoints para consulta externa
- **Relacionamento**: Médicos podem ter múltiplas especializações

### 🔍 Consultas Públicas
- **Filtros disponíveis**: search, active_only, with_count
- **Paginação**: Máximo 50 registros por página
- **Cache**: Resultados em cache por 1 hora
- **Rate limiting**: Máximo 100 requisições por minuto por IP

## 🔐 Segurança e Compliance

### 🛡️ Proteção de Dados
- **Criptografia**: Dados sensíveis criptografados em repouso e trânsito
- **LGPD compliance**: Consentimento explícito para telemedicina
- **Backup diário**: Rotinas automáticas com retenção de 30 dias
- **Logs de auditoria**: Todas as ações médicas registradas
- **Soft delete**: Exclusão lógica para preservar histórico

### 🔑 Controle de Acesso
- **Roles**: Baseado em tipo de usuário (doctor/patient)
- **Middleware**: Validação em todas as rotas protegidas
- **Rate limiting**: Proteção contra força bruta
- **Session management**: Timeout automático de inatividade
- **Two-factor authentication**: Opcional para contas médicas

### 📋 Conformidade Médica
- **Resolução CFM nº 2.314/2022**: Conformidade com regulamentação de telemedicina
- **Consentimento informado**: Obrigatório para cada consulta
- **Prontuário digital**: Manutenção de registros médicos
- **Prescrição digital**: Assinatura eletrônica obrigatória
- **Retenção de dados**: 20 anos para registros médicos

## 🔗 Relacionamentos e Integridade

### 📊 Regras de Relacionamento
- **USERS** é a entidade base obrigatória
- **DOCTORS/PATIENTS** dependem de USERS existentes
- **Exclusão em cascata** com soft delete para auditoria
- **Apenas entidades ativas** podem se relacionar
- **Integridade referencial**: Constraints no banco de dados

### 🚫 Validações de Negócio
- **Email único**: Não pode haver emails duplicados
- **CRM único**: Não pode haver CRMs duplicados por estado
- **Data de nascimento**: Paciente deve ser maior de idade para consentimento
- **Horários**: Agendamentos apenas em horários comerciais
- **Capacidade**: Máximo 500 usuários simultâneos

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../index/VisaoGeral.md)** - Índice central da documentação
- **[📊 Matriz de Rastreabilidade](../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../index/Glossario.md)** - Definições de termos técnicos
- **[🏗️ Arquitetura](../architecture/Arquitetura.md)** - Estrutura e padrões do sistema
- **[⚙️ Lógica de Consultas](../modules/appointments/AppointmentsLogica.md)** - Regras de agendamento
- **[🔐 Autenticação](../modules/auth/RegistrationLogic.md)** - Fluxos de registro e login

### Implementações Relacionadas
- **[User Model](../../app/Models/User.php)** - Entidade base de usuários
- **[Doctor Model](../../app/Models/Doctor.php)** - Entidade de médicos
- **[Patient Model](../../app/Models/Patient.php)** - Entidade de pacientes
- **[Auth Middleware](../../app/Http/Middleware/)** - Controle de acesso
- **[Database Migrations](../../database/migrations/)** - Estrutura do banco

### Termos do Glossário
- **[User](../index/Glossario.md#u)** - Entidade base do sistema
- **[Doctor](../index/Glossario.md#d)** - Entidade que representa um médico
- **[Patient](../index/Glossario.md#p)** - Entidade que representa um paciente
- **[LGPD](../index/Glossario.md#l)** - Lei Geral de Proteção de Dados
- **[Soft Delete](../index/Glossario.md#s)** - Exclusão lógica para auditoria