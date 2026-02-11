# Índice de Diagramas - Telemedicina Para Todos

## 📊 Visão Geral

Este índice organiza todos os diagramas do projeto por categoria e funcionalidade.

## 🏗️ Diagramas de Arquitetura

### 1. [Arquitetura Geral](01_ArquiteturaGeral.md)
**Descrição**: Visão geral completa da arquitetura do sistema, mostrando todas as camadas, tecnologias e fluxo de dados.

**Conteúdo**:
- Camada de Apresentação (Frontend)
- Camada de Aplicação (Backend)
- Comunicação em Tempo Real
- Banco de Dados
- Serviços Externos

**Tecnologias**: Vue.js, Laravel, Inertia.js, Reverb, WebRTC

---

### 2. [Arquitetura em Camadas](07_ArquiteturaCamadas.md)
**Descrição**: Detalhamento das responsabilidades de cada camada do sistema.

**Conteúdo**:
- Camada de Apresentação
- Camada de Aplicação (Controllers e Services)
- Camada de Domínio (Models)
- Camada de Infraestrutura (Events, Jobs, Observers, Policies)
- Camada de Persistência
- Camada de Comunicação

**Foco**: Responsabilidades e princípios de design

---

## 🔄 Diagramas de Fluxo

### 3. [Fluxo de Consulta](02_FluxoConsulta.md)
**Descrição**: Fluxo completo de uma consulta médica, desde o agendamento até a finalização.

**Estados**:
- SCHEDULED (Agendada)
- IN_PROGRESS (Em andamento)
- COMPLETED (Finalizada)
- CANCELLED (Cancelada)
- NO_SHOW (Não compareceu)
- RESCHEDULED (Reagendada)

**Componentes do Prontuário**:
- Diagnóstico (CID-10)
- Prescrições
- Exames
- Sinais Vitais
- Anotações Clínicas
- Atestados
- Documentos

---

### 4. [Fluxo de Autenticação](03_FluxoAutenticacao.md)
**Descrição**: Processo de autenticação, registro e redirecionamento de usuários.

**Fluxos**:
- Registro de Médico
- Registro de Paciente
- Login
- Proteção de Rotas
- Middleware de Autenticação

**Segurança**:
- Bcrypt para senhas
- Sessões Laravel
- CSRF Protection
- Sanctum para API

---

### 5. [Fluxo de Videoconferência](04_FluxoVideoconferencia.md)
**Descrição**: Como funciona a videoconferência em tempo real usando WebRTC e Laravel Reverb.

**Componentes**:
- PeerJS (WebRTC)
- Laravel Echo (WebSocket Client)
- Laravel Reverb (WebSocket Server)
- VideoCallController
- Events de Videoconferência

**Fluxo**:
1. Início da consulta
2. Configuração WebRTC
3. Solicitação de chamada
4. Conexão P2P
5. Streaming de vídeo/áudio
6. Finalização

---

### 6. [Fluxo de Agendamento](06_FluxoAgendamento.md)
**Descrição**: Processo completo de busca, seleção e agendamento de consultas pelo paciente.

**Etapas**:
1. Buscar médicos disponíveis
2. Aplicar filtros (especialidade, nome, data)
3. Visualizar perfil do médico
4. Selecionar data e horário
5. Verificar disponibilidade
6. Validar cadastro
7. Criar agendamento
8. Gerar código de acesso
9. Enviar notificações

**Validações**:
- Contato de emergência completo
- Disponibilidade de slots
- Datas bloqueadas
- Conflitos de horário

---

## 🧩 Diagramas de Componentes

### 7. [Componentes Frontend](05_ComponentesFrontend.md)
**Descrição**: Estrutura e hierarquia dos componentes Vue.js do frontend.

**Categorias**:
- **Layouts**: AppLayout, AuthLayout, SettingsLayout
- **App Shell**: AppShell, AppHeader, AppSidebar, AppContent
- **UI Components**: Reka UI (Button, Input, Card, Dialog, etc.)
- **Pages - Doctor**: Dashboard, Consultations, Patients, Schedule
- **Pages - Patient**: Dashboard, Search, Appointments, MedicalRecord
- **Pages - Auth**: Login, RegisterDoctor, RegisterPatient
- **Pages - Settings**: Profile, Password, Appearance
- **Composables**: useAuth, useAuthGuard, useDoctorRegistration, etc.

**Tecnologias**: Vue.js 3, TypeScript, Inertia.js, Tailwind CSS, Reka UI

---

## 📚 Diagramas Relacionados

### Banco de Dados
- **[Diagrama ERD](../../persistence/database/diagrama_banco_dados.md)** - Modelo entidade-relacionamento completo

### Documentação
- **[Arquitetura](../Architecture/Arquitetura.md)** - Documentação detalhada da arquitetura
- **[Manual do Usuário](../ManualDoUsuario.md)** - Guia completo para usuários

---

## 🎯 Como Usar os Diagramas

### Para Desenvolvedores
- Use os diagramas de arquitetura para entender a estrutura do sistema
- Consulte os fluxos para implementar novas funcionalidades
- Referencie os componentes para manter consistência

### Para Stakeholders
- Use os fluxos para entender processos de negócio
- Consulte a arquitetura para entender a tecnologia
- Use os diagramas em apresentações e documentação

### Para Novos Membros da Equipe
- Comece pela Arquitetura Geral
- Entenda os fluxos principais (Consulta, Autenticação)
- Explore os componentes frontend

---

## 🔄 Manutenção

Os diagramas devem ser atualizados quando:
- Nova funcionalidade é adicionada
- Arquitetura é modificada
- Fluxos de processo mudam
- Novos componentes são criados

**Última atualização**: Janeiro 2025

---

*Todos os diagramas são criados em formato Mermaid e podem ser visualizados em qualquer visualizador Markdown compatível.*


