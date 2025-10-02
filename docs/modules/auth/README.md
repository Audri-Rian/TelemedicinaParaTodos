# 🔐 Módulo de Autenticação

Este módulo gerencia toda a lógica de autenticação, registro e controle de acesso do sistema.

## 📁 Arquivos

- **[📝 Lógica de Registro](RegistrationLogic.md)** - Fluxos de registro e login
- **[🎨 Diagrama de Login](Diagrama%20Login%20Audri.drawio)** - Visualização do fluxo de autenticação

## 🎯 Funcionalidades

### Autenticação
- **Login** com email e senha
- **Verificação** de email obrigatória
- **Controle de sessão** seguro
- **Logout** e limpeza de sessão

### Registro
- **Cadastro de Pacientes** com dados clínicos básicos
- **Cadastro de Médicos** com CRM e especializações
- **Validação** de dados obrigatórios
- **Confirmação** por email

### Segurança
- **Senhas seguras** (mínimo 8 caracteres)
- **Criptografia** com bcrypt
- **Proteção** contra força bruta
- **Controle de acesso** baseado em roles

## 🔗 Relacionamentos

### Dependências
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de autenticação
- **[🏗️ Arquitetura](../architecture/Arquitetura.md)** - Padrões de segurança
- **[📊 Matriz de Requisitos](../index/MatrizRequisitos.md)** - RF007, RF010, RF011

### Implementações
- **[User Model](../../../app/Models/User.php)** - Entidade base
- **[Auth Middleware](../../../app/Http/Middleware/)** - Controle de acesso
- **[Auth Controllers](../../../app/Http/Controllers/)** - Lógica de autenticação

## 🏗️ Arquitetura

### Fluxo de Autenticação
1. **Login** → Validação de credenciais
2. **Verificação** → Confirmação de email
3. **Sessão** → Criação de token seguro
4. **Acesso** → Redirecionamento por role

### Fluxo de Registro
1. **Escolha** → Paciente ou Médico
2. **Dados** → Preenchimento de formulário
3. **Validação** → Verificação de dados
4. **Confirmação** → Email de verificação
5. **Ativação** → Conta ativa no sistema

## 📊 Requisitos Implementados

- **RF007** - Autenticação e Controle de Acesso ✅
- **RF010** - Cadastro de Médico com Especializações ✅
- **RF011** - Cadastro de Paciente com Dados Clínicos ✅
- **RF013** - Configurações de Perfil e Senha 🔄

---

*Última atualização: Dezembro 2024*

