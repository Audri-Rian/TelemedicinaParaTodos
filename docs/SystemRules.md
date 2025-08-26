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
- **Extensão de USERS** com relacionamento 1:1
- **Data de nascimento** obrigatória para cálculos médicos
- **Contato de emergência** obrigatório
- **Consentimento explícito** para telemedicina
- **Histórico médico** para diagnósticos precisos

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
