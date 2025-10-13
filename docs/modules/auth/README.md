# 🔐 Módulo de Autenticação

Este módulo gerencia toda a lógica de autenticação, registro e controle de acesso do sistema.

## 📁 Arquivos

### Documentação Principal
- **[📋 Visão Geral do Sistema](AuthSystemOverview.md)** - ⭐ **Comece aqui!** Índice completo e mapa de toda a documentação
- **[📝 Lógica de Registro](RegistrationLogic.md)** - Fluxos de registro e login
- **[🔐 Sistema de Controle de Acesso (Backend)](RoleBasedAccess.md)** - Middlewares, controllers e proteção de rotas no backend
- **[🎯 Sistema de Roteamento (Frontend)](FrontendRouting.md)** - Composables, proteção de rotas e navegação no frontend
- **[🎨 Diagrama de Login](Diagrama%20Login%20Audri.drawio)** - Visualização do fluxo de autenticação

## 🎯 Funcionalidades

### Autenticação
- **Login** com email e senha
- **Verificação** de email obrigatória
- **Controle de sessão** seguro
- **Logout** e limpeza de sessão
- **Redirecionamento inteligente** baseado em role

### Registro
- **Cadastro de Pacientes** com dados clínicos básicos
- **Cadastro de Médicos** com CRM e especializações
- **Validação** de dados obrigatórios
- **Confirmação** por email
- **Criação automática** de perfis relacionados

### Controle de Acesso
- **Backend:**
  - Middlewares personalizados (`doctor`, `patient`)
  - Rotas protegidas por tipo de usuário
  - Validação em múltiplas camadas
  
- **Frontend:**
  - Composables de autenticação (`useAuth`, `useRoleRoutes`, `useRouteGuard`)
  - Navegação dinâmica baseada em role
  - Proteção e redirecionamento automático de rotas

### Segurança
- **Senhas seguras** (mínimo 8 caracteres)
- **Criptografia** com bcrypt
- **Proteção** contra força bruta
- **Verificação dupla** (backend + frontend)
- **Proteção contra acesso cruzado** entre roles

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
1. **Login** → Validação de credenciais (Backend)
2. **Verificação** → Confirmação de role (Backend)
3. **Redirecionamento** → `/doctor/dashboard` ou `/patient/dashboard` (Backend)
4. **Proteção** → Middleware verifica acesso (Backend)
5. **RouteGuard** → Verificação adicional e UX (Frontend)
6. **Navegação** → Menu adaptado ao role (Frontend)

### Fluxo de Registro
1. **Escolha** → Paciente ou Médico (Frontend)
2. **Dados** → Preenchimento de formulário (Frontend)
3. **Validação** → Verificação de dados (Backend + Frontend)
4. **Criação** → User + Perfil relacionado (Backend)
5. **Login** → Autenticação automática (Backend)
6. **Redirecionamento** → Dashboard específico do role (Backend)
7. **Proteção** → RouteGuard confirma acesso (Frontend)

### Estrutura de Proteção

**Backend (Primário):**
- Middlewares personalizados
- Validação de sessão
- Verificação de role
- Bloqueio com 403 Forbidden

**Frontend (Secundário):**
- Composables de verificação
- Redirecionamento automático
- Navegação dinâmica
- Melhoria de UX

## 📊 Requisitos Implementados

- **RF007** - Autenticação e Controle de Acesso ✅
- **RF010** - Cadastro de Médico com Especializações ✅
- **RF011** - Cadastro de Paciente com Dados Clínicos ✅
- **RF013** - Configurações de Perfil e Senha 🔄

## 📚 Guia de Leitura

Para entender completamente o sistema de autenticação, recomendamos ler nesta ordem:

1. **[Lógica de Registro](RegistrationLogic.md)** - Comece aqui para entender o fluxo básico
2. **[Sistema de Controle de Acesso Backend](RoleBasedAccess.md)** - Entenda como o backend protege as rotas
3. **[Sistema de Roteamento Frontend](FrontendRouting.md)** - Aprenda a usar os composables no frontend

---

*Última atualização: Outubro 2025*

