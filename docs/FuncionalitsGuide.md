# Autarquia de Ensino Superior de Arcoverde

**Curso:** Superior de Análise e Desenvolvimento de Sistemas

---

# Documento de Definição de Escopo (DDE)

**Projeto de Desenvolvimento de Sistemas Web**

**Aluno(a):** Audri Rian Cordeiro Carvalho Alves
**Período:** 4º Período
**Turma:** Noturna
**Data:** 28/08/2025

**Versão:** 1.5

---

## Histórico de Revisão

| Data       | Versão | Descrição                                                                                                                                 | Autor                              |
| ---------- | -----: | ----------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| 08/08/2025 |    1.0 | Elaboração dos primeiros conteúdos para implementação no documento.                                                                       | Audri Rian Cordeiro Carvalho Alves |
| 14/08/2025 |    1.1 | Detalhamento de todos os tópicos já existentes na documentação.                                                                           | Audri Rian Cordeiro Carvalho Alves |
| 21/08/2025 |    1.2 | Inclusão dos requisitos funcionais e não funcionais, regras de negócio e classes, assim como os diagramas de regras de negócio e classes. | Audri Rian Cordeiro Carvalho Alves |
| 28/08/2025 |    1.4 | Adição dos mockups de tela e o fluxo de navegação das mesmas, junto a isso, a explicação do layout.                                       | Audri Rian Cordeiro Carvalho Alves |
| 13/09/2025 |    1.5 | Alinhado com implementação atual: Especializações (CRUD + API), cadastro separado Paciente/Médico, videoconferência P2P (PeerJS + Reverb), e modelo de Consultas (Appointments). | Audri Rian Cordeiro Carvalho Alves |

---

## Sumário

1. [Documento de Definição de Escopo (DDE)](#documento-de-definição-de-escopo-dde)
   1.1 [Introdução](#11-introdução)
   1.2 [Visão Geral do Documento](#12-visão-geral-do-documento)
   1.3 [Identificação dos Requisitos](#13-identificação-dos-requisitos)

   * 1.3.1 [Prioridades dos Requisitos](#131-prioridades-dos-requisitos)
     1.4 [Identificação do Projeto](#14-identificação-do-projeto)
     1.5 [Objetivo do Projeto](#15-objetivo-do-projeto)
     1.6 [Justificativa](#16-justificativa)
     1.7 [Escopo do Produto e Entregáveis](#17-escopo-do-produto-e-entregáveis)
   * 1.7.1 [Funcionalidades Previstas](#171-funcionalidades-previstas)
   * 1.7.2 [Entregáveis](#172-entregáveis)
     1.8 [Premissas e Restrições](#18-premissas-e-restriçõess)
   * 1.8.1 [Premissas](#181-premissas)
   * 1.8.2 [Restrições](#182-restrições)
     1.9 [Critérios de Aceitação do Projeto](#19-critérios-de-aceitação-do-projeto)
     1.10 [Exclusões do Escopo](#110-exclusões-do-escopo)
     1.11 [Stakeholders Envolvidos](#111-stakeholders-envolvidos)
     1.12 [Riscos Iniciais](#112-riscos-iniciais)
2. [Documento de Especificação de Requisitos (ERS)](#2-documento-de-especificação-de-requisitos-ers)
   2.1 [Requisitos Funcionais](#21-requisitos-funcionais)
   2.2 [Requisitos Não Funcionais](#22-requisitos-não-funcionais)
   2.3 [Regras de Negócio](#23-regras-de-negócio)
3. [Diagramas UML / Deployment](#3-diagramas-uml--deployment)
   3.1 [Diagrama de Caso de Uso](#31-diagrama-de-caso-de-uso)
   3.2 [Diagrama de Classes e ERD](#32-diagrama-de-classes-e-erd)
   3.3 [Diagrama de Sequência – Fluxo de Agendamento](#33-diagrama-de-sequência--fluxo-de-agendamento)
   3.4 [Diagrama de Arquitetura (Componentes / Deployment)](#34-diagrama-de-arquitetura-componentes--deployment)
4. [Documento de Especificação de Interfaces (DEI)](#4-documento-de-especificação-de-interfaces-dei)
   4.1 [Mockups / Protótipos de Tela](#41-mockups--protótipos-de-tela)

---

# 1. DOCUMENTO DE DEFINIÇÃO DE ESCOPO (DDE)

## 1.1 Introdução

Esse projeto tem como objetivo criar uma plataforma de **Telemedicina Moderna, segura e acessível** desenvolvida com **Laravel (PHP)**. Ele conecta médicos e pacientes de forma remota, oferecendo consultas online, agendamento inteligente, prontuários digitais e comunicação segura — tudo em um único sistema integrado.

## 1.2 Visão Geral do Documento

O projeto propõe o desenvolvimento de uma plataforma web de teleatendimento que conecta pacientes a profissionais da saúde de diversas áreas (médicos, psicólogos, nutricionistas, fisioterapeutas, etc.). A solução busca oferecer um meio prático, acessível e seguro para agendamento e realização de consultas online, ampliando o alcance dos serviços de saúde e eliminando barreiras geográficas.

A plataforma funcionará como um ambiente digital completo para interação entre pacientes e profissionais. Entre suas principais funcionalidades estão:

* Cadastro e autenticação de usuários;
* Perfis personalizados para pacientes e profissionais;
* Agendamento de consultas online;
* Atendimentos por videoconferência em tempo real (WebRTC via PeerJS) e sinalização por WebSockets (Laravel Reverb);
* Gestão de documentos e prescrições digitais;
* Pagamentos integrados (feature futura);
* Segurança e conformidade com a LGPD.

## 1.3 Identificação dos Requisitos

Por convenção, os requisitos são referenciados pelo nome da subseção onde estão descritos, seguido do seu identificador. Exemplo:

* O requisito funcional **\[Cadastro de Usuários.RF-01]** está localizado na subseção “Requisitos Funcionais”, dentro do bloco identificado como **\[RF-01]**.
* O requisito não funcional **\[Disponibilidade.NF-04]** encontra-se na seção “Requisitos Não Funcionais de Confiabilidade”, no bloco identificado como **\[NF-04]**.

### 1.3.1 Prioridades dos Requisitos

Os requisitos do sistema são classificados em três níveis de prioridade:

* **Essencial:** indispensável para o funcionamento do sistema. Deve ser obrigatoriamente implementado.
* **Importante:** afeta a qualidade do funcionamento. O sistema pode operar sem ele, mas de forma insatisfatória.
* **Desejável:** não interfere nas funcionalidades básicas. Pode ser incluído em versões futuras.

## 1.4 Identificação do Projeto

**Nome do projeto:** Telemedicina para Todos
**Autor:** Audri Rian Cordeiro Carvalho Alves
**Matrícula:** 2024130042
**Período:** 4º

## 1.5 Objetivo do Projeto

Desenvolver uma plataforma web de teleatendimento voltada para a conexão entre profissionais da área da saúde e pacientes, permitindo o agendamento e a realização de consultas online de forma prática, segura e acessível. A plataforma abrangerá médicos, psicólogos, nutricionistas, fisioterapeutas e outros profissionais habilitados.

## 1.6 Justificativa

O projeto promove acesso facilitado e inclusivo aos serviços de saúde e bem-estar, permitindo consultas online com profissionais de diferentes especialidades de forma prática, ágil e segura. Elimina barreiras geográficas, amplia o alcance dos profissionais, reduz tempo de espera e melhora a qualidade dos cuidados.

## 1.7 Escopo do Produto e Entregáveis

### 1.7.1 Funcionalidades Previstas

* Cadastro e autenticação de usuários (pacientes e profissionais);
* Perfis personalizados para pacientes e profissionais;
* Agendamento de consultas;
* Atendimento por videoconferência em tempo real (WebRTC via PeerJS) e sinalização por WebSockets (Laravel Reverb);
* Prescrição digital e envio de documentos;
* Pagamentos online (futuro);
* Notificações e sinalização em tempo real via canais privados (Echo/Reverb);
* Gestão de permissões e autenticação segura.

### 1.7.2 Entregáveis

* Plataforma Web (frontend + backend);
* Banco de Dados MySQL estruturado;
* Módulo de autenticação seguro (bcrypt + controle de acesso);
* Módulo de consultas online com integração de videoconferência;
* Protótipos de interface (mockups das telas principais);
* Documentação completa (DDE, ERS, Diagramas, DEI).

## 1.8 Premissas e Restrições

### 1.8.1 Premissas

* Todos os usuários terão acesso à internet estável.
* Profissionais cadastrados terão registro válido em conselhos de classe.
* Dispositivos utilizados possuirão câmera, microfone e navegador atualizado.

### 1.8.2 Restrições

* Backend obrigatoriamente em **Laravel**.
* Banco de dados **MySQL**.
* Frontend em **HTML, CSS, JS**, podendo usar **Vue.js** ou **React**.
* Funcionalidades em conformidade com a **LGPD**.
* Recursos de videoconferência dependem de APIs externas.

## 1.9 Critérios de Aceitação do Projeto

* Plataforma funcionando em navegadores suportados;
* Consultas online com boa qualidade de áudio e vídeo;
* Autenticação segura com criptografia;
* Testes de desempenho suportando até **500 usuários simultâneos**;
* Interface responsiva e amigável.

## 1.10 Exclusões do Escopo

* Validação automática de registros profissionais (ex: CRM via webservice);
* Sistema de pagamentos completo já integrado (será considerado futuro).

## 1.11 Stakeholders Envolvidos

* **Pacientes** – usuários finais;
* **Profissionais da Saúde** – prestadores de serviço;
* **Equipe de Desenvolvimento** – responsável pelo sistema;
* **Professor Orientador** – alinhamento acadêmico;
* **Equipe de Design (opcional)** – experiência do usuário.

## 1.12 Riscos Iniciais

* Prazo curto (18 semanas);
* Dificuldades técnicas na videoconferência;
* Dependência de APIs externas;
* Equipe em aprendizado de novas tecnologias;
* Possíveis falhas de segurança se não implementadas corretamente.

---

# 2. DOCUMENTO DE ESPECIFICAÇÃO DE REQUISITOS (ERS)

## 2.1 Requisitos Funcionais

Requisitos funcionais descrevem as funções que usuários e clientes esperam do software. Abaixo, os principais requisitos identificados:

### \[RF001] Manter Cadastro de Pacientes

* **Descrição:** Cadastro, alteração, inativação, consulta e pesquisa de pacientes em banco de dados seguro.
* **Atores:** Paciente, Administrador
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Nome completo, CPF, Data de Nascimento, Telefone, E-mail, Endereço, Senha.
* **Saídas / Pós-Condições:** Dados salvos e disponíveis no perfil do paciente.

### \[RF002] Manter Cadastro de Profissionais da Saúde

* **Descrição:** Cadastro, alteração, inativação, consulta e pesquisa de perfis de profissionais.
* **Atores:** Profissional da Saúde, Administrador
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Nome, CPF, Registro profissional, Especialidade, Telefone, E-mail, Endereço, Senha.
* **Saídas / Pós-Condições:** Perfil disponível para visualização pelos pacientes com status "Ativo" após validação.

### \[RF003] Agendamento de Consultas

* **Descrição:** Pacientes podem agendar consultas com profissionais disponíveis.
* **Atores:** Paciente, Profissional da Saúde
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Seleção de Profissional, Especialidade, Data e Horário disponíveis.
* **Saídas / Pós-Condições:** Confirmação do agendamento e notificação das partes.

### \[RF004] Realizar Consultas Online (Videoconferência e Chat Interno)

* **Descrição:** Consultas por videoconferência integrada ou chat interno.
* **Atores:** Paciente, Profissional da Saúde
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Acesso no horário agendado com internet, câmera e microfone habilitados.
* **Saídas / Pós-Condições:** Consulta registrada no histórico do paciente e profissional.

### \[RF005] Prescrição Digital e Envio de Documentos

* **Descrição:** Emissão de prescrições digitais e envio de documentos.
* **Atores:** Profissional da Saúde
* **Prioridade:** Importante
* **Entradas / Pré-Condições:** Tipo de documento, Conteúdo, Assinatura digital (quando aplicável).
* **Saídas / Pós-Condições:** Paciente visualiza e baixa documentos em sua área restrita.

### \[RF006] Pagamentos Online

* **Descrição:** Pagamentos online por atendimentos, quando aplicável.
* **Atores:** Paciente, Profissional da Saúde, Administrador
* **Prioridade:** Desejável
* **Entradas / Pré-Condições:** Método de pagamento (cartão, PIX ou boleto), valor e confirmação.
* **Saídas / Pós-Condições:** Pagamento registrado e associado à consulta.

### \[RF007] Autenticação e Controle de Acesso

* **Descrição:** Autenticação de usuários e controle de permissões.
* **Atores:** Paciente, Profissional da Saúde, Administrador
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Login (E-mail ou CPF) e senha.
* **Saídas / Pós-Condições:** Acesso à área restrita conforme perfil.

### \[RF008] Notificações de Consultas

* **Descrição:** Notificações sobre confirmações, alterações e cancelamentos.
* **Atores:** Paciente, Profissional da Saúde
* **Prioridade:** Desejável
* **Entradas / Pré-Condições:** Consulta agendada ou alterada.
* **Saídas / Pós-Condições:** Envio de notificação via e-mail e/ou painel da plataforma.

### \[RF009] Gestão de Especializações (Médicas)

* **Descrição:** CRUD de especializações com listagem, exibição, edição e exclusão (com validação de integridade) e endpoints públicos de consulta.
* **Atores:** Administrador
* **Prioridade:** Importante
* **Entradas / Pré-Condições:** Nome da especialização (único, até 100 caracteres).
* **Saídas / Pós-Condições:** Especializações disponíveis para vinculação a médicos e consulta pública.
* **Observações:** API pública implementada: `GET /api/specializations/list` (filtros: `search`, `active_only`, `with_count`) e `GET /api/specializations/options`.

### \[RF010] Cadastro de Médico com Especializações

* **Descrição:** Fluxo de registro dedicado a médicos com CRM único, seleção de 1+ especializações e criação do perfil vinculado ao usuário.
* **Atores:** Médico, Administrador
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Nome, E-mail, Senha, CRM (único; formato alfanumérico), Especializações (UUID existentes).
* **Saídas / Pós-Condições:** Usuário autenticado com perfil médico ativo e especializações vinculadas.

### \[RF011] Cadastro de Paciente com Dados Clínicos Básicos

* **Descrição:** Fluxo de registro dedicado a pacientes com dados mínimos obrigatórios e campos clínicos opcionais.
* **Atores:** Paciente
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Nome, E-mail, Senha, Gênero, Data de nascimento, Telefone. Opcionais: contato de emergência, histórico médico, alergias etc.
* **Saídas / Pós-Condições:** Usuário autenticado com perfil de paciente ativo.

### \[RF012] Videoconferência de Consultas (Tempo Real)

* **Descrição:** Solicitação e aceite de chamada de vídeo entre usuários via canais privados e conexão P2P para mídia.
* **Atores:** Paciente, Médico
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Usuários autenticados, permissão de câmera/microfone, PeerID válido; eventos `RequestVideoCall` e `RequestVideoCallStatus` no canal privado `video-call.{id}`.
* **Saídas / Pós-Condições:** Estabelecimento de sessão de vídeo, término registra estado local da sessão (UI) e libera recursos.
* **Observações:** Endpoints: `POST /video-call/request/{user}` e `POST /video-call/request/status/{user}`. Página: `GET /consultations`.

### \[RF013] Configurações de Perfil e Senha

* **Descrição:** Edição de perfil, atualização de senha e exclusão de conta.
* **Atores:** Usuário autenticado
* **Prioridade:** Importante
* **Entradas / Pré-Condições:** Dados de perfil válidos e senha atual para update de senha.
* **Saídas / Pós-Condições:** Perfil atualizado e persistido.

## 2.2 Requisitos Não Funcionais

* **\[NF001] Acesso Web:** Acesso por navegadores modernos; suporte a dispositivos com câmera e microfone. *(Prioridade: Essencial)*
* **\[NF002] Interface Amigável:** Layout responsivo e intuitivo, mensagens de erro claras. *(Prioridade: Importante)*
* **\[NF003] Backup de Dados:** Backups automáticos diários; recuperação em até 24 horas. *(Prioridade: Essencial)*
* **\[NF004] Desempenho:** Resposta < 3s para operações críticas, mesmo com até 500 usuários simultâneos. *(Prioridade: Essencial)*
* **\[NF005] Autenticação Segura:** Senhas criptografadas (bcrypt); proteção contra força bruta (bloqueio após 5 tentativas). *(Prioridade: Essencial)*
* **\[NF006] Controle de Acesso:** Permissões por perfil; dados sensíveis restritos. *(Prioridade: Essencial)*
* **\[NF007] Conformidade com a LGPD:** Consentimento, direito de exclusão e criptografia em repouso e trânsito (HTTPS/TLS). *(Prioridade: Essencial)*
* **\[NF008] Disponibilidade:** Disponibilidade mínima de 99% (janelas de manutenção agendadas). *(Prioridade: Importante)*

## 2.3 Regras de Negócio

* Pacientes só podem agendar consultas com profissionais ativos.
* Profissionais só podem prescrever documentos se possuírem registro válido.
* Consultas devem ser registradas no histórico de ambos os perfis.
* Cancelamentos devem notificar todas as partes envolvidas.

---

# 3. ARQUITETURA DO SISTEMA E DIAGRAMAS

## 3.1 Arquitetura de Software

### Padrão Arquitetural
O sistema utiliza o padrão **Controller → Service → Repository** seguindo os princípios SOLID:

* **Presentation Layer (Controllers):** Controladores RESTful responsáveis apenas por receber requisições e retornar respostas
* **Application Layer (Services):** Regras de negócio isoladas em Services com injeção de dependência
* **Infrastructure Layer (Repositories):** Acesso aos dados via Repositories com Eloquent ORM
* **Domain Layer (Models):** Entidades de domínio com relacionamentos bem definidos

### Stack Tecnológica
* **Backend:** Laravel 11 (PHP 8.2+)
* **Frontend:** Vue.js 3 com Inertia.js para SPA
* **Banco de Dados:** MySQL 8.0+
* **Cache/Queue:** Redis
* **Autenticação:** Laravel Sanctum
* **WebSockets:** Laravel Reverb (Broadcasting + Echo)
* **Videoconferência:** WebRTC via PeerJS (P2P)

## 3.2 Modelo de Dados (ERD)

### Entidades Principais

**Users (Tabela Base):**
* id (uuid, primary key)
* name (string, 255)
* email (string, unique)
* email_verified_at (timestamp)
* password (hash bcrypt)
* status (enum: active, inactive, suspended, blocked)
* created_at, updated_at, deleted_at

**Doctors (Extensão de Users - 1:1):**
* id (uuid, primary key)
* user_id (foreign key → users.id, unique)
* crm (string, 20, unique, nullable)
* biography (text)
* license_number (string, 50, unique)
* license_expiry_date (date)
* status (enum: active, inactive, suspended)
* availability_schedule (json)
* consultation_fee (decimal 8,2)
* specializations (relacionamento N:N via pivot)

**Patients (Extensão de Users - 1:1):**
* id (uuid, primary key)
* user_id (foreign key → users.id, unique)
* gender (enum: male, female, other)
* date_of_birth (date, indexed)
* phone_number (string, 20)
* emergency_contact (string, 100)
* emergency_phone (string, 20)
* medical_history (text)
* allergies (text)
* current_medications (text)
* blood_type (string, 5)
* height, weight (decimal)
* insurance_provider, insurance_number (string)
* consent_telemedicine (boolean)
* last_consultation_at (timestamp)

**Specializations:**
* id (uuid, primary key)
* name (string, 100, unique, indexed)
* created_at, updated_at

**doctor_specialization (Pivot N:N):**
* doctor_id (uuid, fk → doctors.id)
* specialization_id (uuid, fk → specializations.id)
* created_at, updated_at
* primary key composta (doctor_id, specialization_id)

**Appointments (Consultas):**
* id (uuid, primary key)
* doctor_id (fk → doctors.id)
* patient_id (fk → patients.id)
* scheduled_at (timestamp, indexed)
* access_code (string, unique, indexed)
* started_at, ended_at (timestamp nullable)
* video_recording_url (string nullable)
* status (enum: scheduled, in_progress, completed, no_show, cancelled, rescheduled)
* notes (text nullable)
* metadata (json nullable)
* created_at, updated_at, deleted_at (soft delete)

**AppointmentLog:**
* id (pk)
* appointments_id (fk → appointments.id)
* event (string)
* metadata (json)
* created_at, updated_at

### Relacionamentos
* **Users** ↔ **Doctors/Patients** (1:1, polimórfico)
* **Doctors** ↔ **Consultations** (1:N)
* **Patients** ↔ **Consultations** (1:N)
* **Consultations** ↔ **Prescriptions** (1:N)
* **Users** ↔ **Notifications** (1:N)
* **Doctors** ↔ **Specializations** (N:N via pivot)
* **Doctors/Patients** ↔ **Appointments** (1:N)

## 3.3 Casos de Uso por Ator

**Paciente:**
* Registrar-se no sistema com dados pessoais e médicos
* Autenticar-se (login/logout com verificação de email)
* Atualizar perfil e informações médicas
* Buscar médicos por especialidade e disponibilidade
* Agendar consultas online
* Participar de consultas via videoconferência
* Receber prescrições e documentos digitais
* Visualizar histórico de consultas
* Iniciar/aceitar chamadas de vídeo em tempo real

**Médico:**
* Registrar-se com dados profissionais (CRM, especialidade)
* Validar registro profissional
* Gerenciar agenda e disponibilidade
* Atender consultas via videoconferência
* Emitir prescrições digitais
* Enviar documentos para pacientes
* Visualizar histórico de atendimentos
* Gerenciar especializações vinculadas ao perfil (via Admin)
* Iniciar/aceitar chamadas de vídeo em tempo real

**Administrador:**
* Validar cadastros de médicos
* Gerenciar usuários (ativar/suspender/bloquear)
* Monitorar consultas e transações
* Manter integridade e segurança do sistema
* Gerar relatórios e auditoria
* Manter catálogo de especializações (CRUD)

## 3.4 Arquitetura de Deployment

### Ambiente de Desenvolvimento
```
Frontend (Vue.js + Vite)
    ↓ (HTTP/HTTPS)
Backend (Laravel + Sanctum)
    ↓ (PDO/MySQL)
Database (MySQL)
    ↓ (Redis)
Cache/Queue (Redis)
```

### Ambiente de Produção
```
Load Balancer (Nginx)
    ↓
Application Servers (Laravel)
    ↓
Database Cluster (MySQL Master/Slave)
Cache Layer (Redis Cluster)
File Storage (S3/MinIO)
Monitoring (Laravel Telescope)
```

## 3.5 Rotas Principais Implementadas

• Navegação autenticada: `GET /dashboard`, `GET /appointments`, `GET /consultations`, `GET /healthRecords`
• Especializações (web): `Route::resource('specializations', ...)`
• Especializações (API pública): `GET /api/specializations/list`, `GET /api/specializations/options`
• Videoconferência: `POST /video-call/request/{user}`, `POST /video-call/request/status/{user}`
• Autenticação e registro: `GET/POST /login`, `GET/POST /register`, `GET/POST /register/patient`, `GET/POST /register/doctor`
• Configurações: `GET/PATCH /settings/profile`, `GET/PUT /settings/password`

---

# 4. DOCUMENTO DE ESPECIFICAÇÃO DE INTERFACES (DEI)

## 4.1 Mockups / Protótipos de Tela

(Aqui deverão constar os mockups das telas principais, fluxos de navegação e descrição do layout das interfaces.)

---

*Fim do documento.*
