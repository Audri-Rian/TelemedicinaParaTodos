# Diagrama de Fluxo de Autenticação - Telemedicina Para Todos

## Fluxo de Autenticação e Registro

Este diagrama mostra os fluxos de autenticação, registro e redirecionamento de usuários.

```mermaid
sequenceDiagram
    participant U as Usuário
    participant F as Frontend Vue.js
    participant C as Controller
    participant S as Service
    participant M as Model User
    participant DB as Banco de Dados
    participant A as Auth Middleware

    Note over U,DB: Fluxo de Registro
    
    U->>F: Acessa /register/select
    F->>U: Exibe seleção (Doctor/Patient)
    U->>F: Seleciona tipo
    F->>C: GET /register/doctor ou /register/patient
    C->>F: Renderiza formulário Inertia
    F->>U: Exibe formulário de registro
    U->>F: Preenche e submete formulário
    F->>C: POST /register/doctor ou /register/patient
    C->>C: Valida dados (Form Request)
    C->>S: Cria usuário e perfil
    S->>M: Cria User
    M->>DB: INSERT users
    S->>M: Cria Doctor ou Patient
    M->>DB: INSERT doctors ou patients
    S->>C: Retorna usuário criado
    C->>C: Autentica usuário automaticamente
    C->>F: Redireciona para dashboard
    F->>U: Dashboard carregado
    
    Note over U,DB: Fluxo de Login
    
    U->>F: Acessa /login
    F->>U: Exibe formulário de login
    U->>F: Informa email/CPF e senha
    F->>C: POST /login
    C->>C: Valida credenciais
    C->>M: Busca usuário por email/CPF
    M->>DB: SELECT users WHERE email/CPF
    DB->>M: Retorna usuário
    M->>C: Usuário encontrado
    C->>C: Verifica senha (bcrypt)
    alt Senha correta
        C->>C: Cria sessão autenticada
        C->>F: Redireciona para /dashboard
        F->>C: GET /dashboard
        C->>A: Verifica autenticação
        A->>C: Usuário autenticado
        C->>C: Verifica tipo de usuário
        alt É Doctor
            C->>F: Redireciona /doctor/dashboard
        else É Patient
            C->>F: Redireciona /patient/dashboard
        end
        F->>U: Dashboard específico carregado
    else Senha incorreta
        C->>F: Retorna erro de autenticação
        F->>U: Exibe mensagem de erro
    end
    
    Note over U,DB: Proteção de Rotas
    
    U->>F: Tenta acessar rota protegida
    F->>C: GET /doctor/consultations
    C->>A: Middleware auth
    A->>A: Verifica sessão
    alt Não autenticado
        A->>F: Redireciona /login
        F->>U: Página de login
    else Autenticado
        A->>A: Middleware doctor/patient
        alt Tipo incorreto
            A->>F: Retorna 403 Forbidden
            F->>U: Erro de acesso negado
        else Tipo correto
            A->>C: Permite acesso
            C->>S: Busca dados
            S->>M: Consulta dados
            M->>DB: SELECT
            DB->>M: Retorna dados
            M->>S: Dados encontrados
            S->>C: Retorna dados
            C->>F: Retorna página Inertia
            F->>U: Página carregada
        end
    end
```

## Tipos de Usuário

### Doctor (Médico)
- Registro requer: Nome, Email, Senha, CRM, Especializações
- Após login: Redirecionado para `/doctor/dashboard`
- Rotas protegidas: `/doctor/*`

### Patient (Paciente)
- Registro requer: Nome, Email, Senha, Gênero, Data de nascimento, Telefone
- Após login: Redirecionado para `/patient/dashboard`
- Rotas protegidas: `/patient/*`

## Middleware de Autenticação

### auth
- Verifica se o usuário está autenticado
- Redireciona para `/login` se não autenticado

### doctor
- Verifica se o usuário é um médico
- Retorna 403 se não for médico

### patient
- Verifica se o usuário é um paciente
- Retorna 403 se não for paciente

## Segurança

- **Senhas**: Criptografadas com bcrypt
- **Sessões**: Gerenciadas pelo Laravel
- **CSRF**: Proteção automática em formulários
- **Sanctum**: Autenticação para API (quando necessário)

---

*Última atualização: Janeiro 2025*


