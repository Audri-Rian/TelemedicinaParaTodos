# 📜 Regras do Sistema

## 🎯 Objetivo
Esse projeto tem como objetivo de criar uma platarforma de Telemedicina Moderna, segura e acessível desenvolvida com Laravel(PHP). Ele conecta médicos e pacientes de forma remota, oferecendo consultas online, agendamento inteligente, prontuários digitais e comunicação segura tudo em um único sistema integrado.

# 🏥 Regras de Negócio 

### Módulo Usuários e Informações

#### 👥 USERS (Usuários Base)
- **Tabela central** de autenticação (polimórfica: médico OU paciente)
- **Email único** e obrigatório, verificação obrigatória
- **Senha segura** (mínimo 8 caracteres, maiúsculas, números)
- **Status**: ativo, inativo, suspenso, bloqueado
- **Soft delete** para auditoria completa

#### 👨‍⚕️ DOCTORS (Médicos)
- **Extensão de USERS** com relacionamento 1:1
- **CRM obrigatório** e único por estado/região
- **Especialidade principal** obrigatória
- **Controle de agenda** e disponibilidade para consultas
- **Apenas ativos** podem receber agendamentos

#### 👤 PATIENTS (Pacientes)
##### Alguns dados do patient não são obrigatorios no inicio
- **Extensão de USERS** com relacionamento 1:1
- **Data de nascimento** obrigatória para cálculos médicos
- **Contato de emergência**  Obrigatorio apos a primeira etapa de autenticação.
- **Consentimento explícito** para telemedicina, não precisa no register incial
- **Histórico médico** para diagnósticos precisos, não precisa no register incial

#### 🔗 Relacionamentos
- **USERS** é a entidade base obrigatória
- **DOCTORS/PATIENTS** dependem de USERS existentes
- **Exclusão em cascata** com soft delete para auditoria
- **Apenas entidades ativas** podem se relacionar

#### 🛡️ Segurança e Compliance
- **Criptografia** de dados sensíveis (histórico médico)
- **Logs de auditoria** para todas as ações médicas
- **Controle de acesso** baseado em roles
- **Compliance LGPD** e regulamentações médicas
- **Backup diário** com logs de auditoria

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
