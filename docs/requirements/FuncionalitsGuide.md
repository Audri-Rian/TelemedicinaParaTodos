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
5. [Documentação Técnica](#5-documentação-técnica)
   5.1 [Arquitetura do Sistema](#51-arquitetura-do-sistema)
   * 5.1.1 [Segmentação da Arquitetura](#511-segmentação-da-arquitetura)
   * 5.1.1.1 [Camada Cliente (Frontend)](#5111-camada-cliente-frontend)
   * 5.1.1.2 [Camada Servidor (Backend)](#5112-camada-servidor-backend)
   * 5.1.1.3 [Camada de Dados (Database)](#5113-camada-de-dados-database)
   * 5.1.1.4 [Explicação da Segmentação](#5114-explicação-da-segmentação)
   5.2 [Tecnologias Utilizadas](#52-tecnologias-utilizadas)
   * 5.2.1 [Frontend](#521-frontend)
   * 5.2.2 [Backend](#522-backend)
   * 5.2.3 [Banco de Dados](#523-banco-de-dados)
   * 5.2.4 [Ferramentas de Apoio](#524-ferramentas-de-apoio)
   * 5.2.5 [Padrões Adotados](#525-padrões-adotados)
   * 5.2.5.1 [Padrões Arquiteturais](#5251-padrões-arquiteturais)
   * 5.2.5.2 [Arquitetura em Camadas](#5252-arquitetura-em-camadas)
   * 5.2.6 [Boas Práticas e Convenções](#526-boas-práticas-e-convenções)
   * 5.2.7 [Requisitos de Infraestrutura](#527-requisitos-de-infraestrutura)
   * 5.2.7.1 [Ambiente de Desenvolvimento Atual](#5271-ambiente-de-desenvolvimento-atual)
   * 5.2.7.2 [Hospedagem Planejada para Produção](#5272-hospedagem-planejada-para-produção)
   * 5.2.8 [APIs e Integrações](#528-apis-e-integrações)
   * 5.2.9 [Caracterização da API](#529-caracterização-da-api)
   5.3 [Repositório e Código-Fonte](#53-repositório-e-código-fonte)

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

* **Descrição:** Pacientes podem agendar consultas com profissionais disponíveis. Sistema suporta status de reagendamento, integração completa com prontuário médico, acesso e edição de prontuário durante consulta, finalização com bloqueio de prontuário (exceto complementos), e geração de PDF completo da consulta.
* **Atores:** Paciente, Profissional da Saúde
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Seleção de Profissional, Especialidade, Data e Horário disponíveis.
* **Saídas / Pós-Condições:** Confirmação do agendamento e notificação das partes. Consulta registrada com status (scheduled, in_progress, completed, no_show, cancelled, rescheduled).
* **Funcionalidades Implementadas:**
  * Status Rescheduled para consultas reagendadas
  * Integração com Prontuário: Consultas conectam com múltiplas entidades de prontuário
  * Prontuário Durante Consulta: Médico pode acessar e editar prontuário durante consulta
  * Finalização com Prontuário: Ao finalizar, prontuário é bloqueado (exceto complementos)
  * Complementos: Médico pode adicionar complementos após finalização
  * PDF de Consulta: Gerar PDF completo da consulta com prontuário

### \[RF004] Realizar Consultas Online (Videoconferência e Chat Interno)

* **Descrição:** Consultas por videoconferência integrada ou chat interno. Sistema cria salas de videoconferência automaticamente, rastreia eventos em tempo real, gerencia expiração automática de salas e integra com consultas.
* **Atores:** Paciente, Profissional da Saúde
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Acesso no horário agendado com internet, câmera e microfone habilitados.
* **Saídas / Pós-Condições:** Consulta registrada no histórico do paciente e profissional.
* **Funcionalidades Implementadas:**
  * Salas de Videoconferência: Sistema cria salas automaticamente para consultas
  * Eventos de Videoconferência: Rastreamento de eventos (entrada, saída, ações)
  * Expiração Automática: Salas expiram automaticamente após período configurado
  * Limpeza Automática: Jobs automáticos para limpeza de eventos antigos
  * Integração com Consultas: Consultas atualizadas automaticamente a partir das salas

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

### \[RF014] Gestão de Prontuários Médicos

* **Descrição:** Sistema completo de gestão de prontuários médicos digitais com registro de diagnósticos, prescrições, exames, anotações clínicas, atestados, sinais vitais e documentos. Suporta visualização diferenciada para médicos e pacientes, exportação em PDF e auditoria completa.
* **Atores:** Médico, Paciente
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Consulta agendada ou em andamento para médicos; acesso autenticado para pacientes.
* **Saídas / Pós-Condições:** Prontuário atualizado e disponível para visualização/exportação.
* **Funcionalidades para Médicos:**
  * Visualização de Prontuário Completo de pacientes atendidos
  * Registro de Diagnóstico com CID-10 durante consulta
  * Prescrição Digital com medicamentos estruturados
  * Solicitação de Exames (laboratoriais, imagem, outros)
  * Anotações Clínicas (públicas ou privadas)
  * Atestados Médicos com código de verificação único
  * Registro de Sinais Vitais durante consulta
  * Anexar Documentos médicos ao prontuário
  * Geração de PDF completo de consulta com prontuário
  * Exportação de Prontuário completo em PDF
* **Funcionalidades para Pacientes:**
  * Visualização de Prontuário (itens não privados)
  * Histórico completo de Consultas
  * Visualização de Prescrições (ativas e expiradas)
  * Visualização de Exames (solicitados e resultados)
  * Visualização de Atestados emitidos
  * Exportação de próprio prontuário em PDF
* **Auditoria:**
  * Logs Completos: Todas as ações em prontuários são registradas
  * Rastreabilidade: IP, user agent e metadados registrados
  * Compliance LGPD: Logs não podem ser excluídos

### \[RF015] Sistema de Agenda e Disponibilidade

* **Descrição:** Sistema completo de gestão de agenda e disponibilidade para médicos, permitindo configuração de múltiplos locais de atendimento, slots recorrentes e específicos, bloqueio de datas e consulta pública de disponibilidade.
* **Atores:** Médico, Paciente
* **Prioridade:** Essencial
* **Entradas / Pré-Condições:** Médico autenticado para configuração; acesso público para consulta de disponibilidade.
* **Saídas / Pós-Condições:** Agenda configurada e disponível para agendamento de consultas.
* **Funcionalidades:**
  * Locais de Atendimento: Médico pode cadastrar múltiplos locais (teleconsulta, consultório, hospital, clínica)
  * Slots Recorrentes: Configurar disponibilidade semanal (ex: toda segunda-feira 8h-12h)
  * Slots Específicos: Configurar disponibilidade para datas específicas
  * Datas Bloqueadas: Bloquear datas específicas (feriados, férias)
  * Validação de Conflitos: Sistema valida conflitos de horários
  * Disponibilidade Padrão: Sistema cria disponibilidade padrão se médico não configurar
  * Consulta de Disponibilidade: Pacientes podem consultar disponibilidade de médicos por data

### \[RF016] Timeline de Profissional

* **Descrição:** Sistema de timeline para profissionais registrarem eventos de educação, cursos, certificados e projetos profissionais, com controle de visibilidade pública e ordenação personalizada.
* **Atores:** Profissional da Saúde
* **Prioridade:** Importante
* **Entradas / Pré-Condições:** Profissional autenticado com perfil ativo.
* **Saídas / Pós-Condições:** Eventos registrados e disponíveis no perfil público (quando visíveis).
* **Funcionalidades:**
  * Eventos de Educação: Registrar formação acadêmica (fundamental, médio, graduação, pós)
  * Cursos: Registrar cursos realizados
  * Certificados: Registrar certificações profissionais
  * Projetos: Registrar projetos profissionais
  * Visibilidade: Controlar se evento aparece no perfil público
  * Ordenação: Controlar ordem de exibição dos eventos
  * Mídia: Anexar imagens/certificados aos eventos

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

### RN001 - Agendamento de Consultas com Profissionais Ativos

**Descrição:** Pacientes só podem agendar consultas com médicos que possuem status ativo no sistema. Médicos inativos, suspensos ou bloqueados não podem receber novos agendamentos.

**Funcionalidade:** Validação automática do status do médico durante o processo de agendamento. Sistema verifica se o médico possui status "active" antes de permitir a criação da consulta.

**Usuários:** Paciente (quem agenda), Médico (quem recebe o agendamento)

**Relações:** Relaciona-se com RN002 (Status de Médico), RN003 (Cadastro Completo de Paciente), RN004 (Validação de Conflitos de Horário)

---

### RN002 - Status de Médico para Agendamento

**Descrição:** Médicos devem estar com status "active" para aparecerem nas buscas de disponibilidade e receberem agendamentos. Médicos com status "inactive" ou "suspended" não são exibidos para pacientes e não podem receber novas consultas.

**Funcionalidade:** Filtragem automática de médicos por status nas consultas de disponibilidade e validação no momento do agendamento.

**Usuários:** Médico, Paciente, Administrador

**Relações:** Relaciona-se com RN001 (Agendamento com Profissionais Ativos), RN010 (Cadastro de Médico)

---

### RN003 - Cadastro Completo de Paciente para Agendamento

**Descrição:** Pacientes devem completar duas etapas de cadastro antes de poderem agendar consultas. Primeira etapa: dados básicos (nome, email, senha, gênero, data de nascimento, telefone). Segunda etapa: contato de emergência (nome e telefone do contato de emergência) obrigatório para agendamento.

**Funcionalidade:** Validação automática da completude do cadastro antes de permitir agendamento. Sistema verifica se todos os campos obrigatórios da segunda etapa foram preenchidos.

**Usuários:** Paciente

**Relações:** Relaciona-se com RN001 (Agendamento com Profissionais Ativos)

---

### RN004 - Validação de Conflitos de Horário

**Descrição:** Não é permitido agendar consultas em horários que conflitem com outras consultas já agendadas do mesmo médico. Sistema valida sobreposição de horários considerando a duração padrão da consulta (configurável, padrão 30 minutos).

**Funcionalidade:** Verificação automática de conflitos antes de confirmar agendamento ou reagendamento. Valida se o horário solicitado não se sobrepõe a consultas existentes com status "scheduled", "rescheduled" ou "in_progress".

**Usuários:** Paciente, Médico

**Relações:** Relaciona-se com RN001 (Agendamento com Profissionais Ativos), RN005 (Reagendamento de Consultas), RN015 (Agenda e Disponibilidade)

---

### RN005 - Reagendamento de Consultas

**Descrição:** Consultas podem ser reagendadas apenas se estiverem com status "scheduled" ou "rescheduled" e dentro da janela de tempo permitida (configurável, padrão 2 horas antes do horário agendado). O novo horário deve ser validado para conflitos.

**Funcionalidade:** Validação de status e janela de tempo antes de permitir reagendamento. Atualização automática do status para "rescheduled" após confirmação.

**Usuários:** Paciente, Médico

**Relações:** Relaciona-se com RN004 (Validação de Conflitos), RN006 (Cancelamento de Consultas), RN007 (Transições de Status)

---

### RN006 - Cancelamento de Consultas

**Descrição:** Consultas podem ser canceladas apenas se estiverem com status "scheduled" ou "rescheduled" e dentro da janela de tempo permitida (configurável, padrão 2 horas antes do horário agendado). Consultas em andamento ou finalizadas não podem ser canceladas.

**Funcionalidade:** Validação de status e janela de tempo antes de permitir cancelamento. Atualização automática do status para "cancelled" e registro de motivo (quando informado) no log da consulta.

**Usuários:** Paciente, Médico

**Relações:** Relaciona-se com RN005 (Reagendamento), RN007 (Transições de Status), RN008 (Notificações de Cancelamento)

---

### RN007 - Transições de Status de Consultas

**Descrição:** Consultas possuem um fluxo de status controlado com transições permitidas definidas. Status inicial: "scheduled". Transições permitidas: scheduled → in_progress, cancelled, rescheduled, no_show; rescheduled → in_progress, cancelled, scheduled; in_progress → completed; completed, cancelled, no_show → (sem transições).

**Funcionalidade:** Validação automática de transições de status antes de aplicar mudanças. Bloqueio de transições inválidas.

**Usuários:** Sistema (automático), Médico, Paciente

**Relações:** Relaciona-se com RN005 (Reagendamento), RN006 (Cancelamento), RN009 (Início de Consulta), RN010 (Finalização de Consulta)

---

### RN008 - Notificações de Cancelamento e Alterações

**Descrição:** Todas as partes envolvidas (médico e paciente) devem ser notificadas quando uma consulta é cancelada, reagendada ou sofre alterações significativas. Notificações podem ser enviadas via email e/ou painel da plataforma.

**Funcionalidade:** Disparo automático de notificações após cancelamento, reagendamento ou alterações relevantes na consulta.

**Usuários:** Paciente, Médico

**Relações:** Relaciona-se com RN005 (Reagendamento), RN006 (Cancelamento)

---

### RN009 - Início de Consulta

**Descrição:** Consultas podem ser iniciadas apenas se estiverem com status "scheduled" ou "rescheduled" e dentro da janela de tempo permitida (configurável, padrão 10 minutos antes do horário agendado). Ao iniciar, status muda para "in_progress" e registra timestamp de início.

**Funcionalidade:** Validação de status e janela de tempo antes de permitir início. Atualização automática de status e criação de log de evento.

**Usuários:** Médico, Paciente

**Relações:** Relaciona-se com RN007 (Transições de Status), RN010 (Finalização de Consulta), RN011 (Videoconferência)

---

### RN010 - Finalização de Consulta

**Descrição:** Consultas podem ser finalizadas apenas se estiverem com status "in_progress". Ao finalizar, status muda para "completed", registra timestamp de término, bloqueia o prontuário para edição (exceto complementos) e calcula duração da consulta.

**Funcionalidade:** Validação de status antes de permitir finalização. Atualização automática de status, bloqueio de prontuário e criação de log de evento.

**Usuários:** Médico

**Relações:** Relaciona-se com RN007 (Transições de Status), RN012 (Prontuário Médico), RN013 (Complementos ao Prontuário)

---

### RN011 - Videoconferência de Consultas

**Descrição:** Videoconferências podem ser iniciadas apenas durante consultas com status "in_progress". Sistema cria salas de videoconferência automaticamente para consultas agendadas. Salas expiram automaticamente após período configurado.

**Funcionalidade:** Criação automática de salas de videoconferência, rastreamento de eventos (entrada, saída, ações) e expiração automática de salas antigas.

**Usuários:** Médico, Paciente

**Relações:** Relaciona-se com RN009 (Início de Consulta), RN010 (Finalização de Consulta)

---

### RN012 - Prontuário Médico Durante Consulta

**Descrição:** Médicos podem acessar e editar prontuário completo do paciente apenas durante consultas com status "in_progress". Após finalização, prontuário é bloqueado para edição, exceto para adição de complementos.

**Funcionalidade:** Controle de acesso ao prontuário baseado no status da consulta. Bloqueio automático após finalização.

**Usuários:** Médico

**Relações:** Relaciona-se com RN010 (Finalização de Consulta), RN013 (Complementos ao Prontuário), RN014 (Prescrições e Documentos)

---

### RN013 - Complementos ao Prontuário Após Finalização

**Descrição:** Após finalização da consulta, médicos podem adicionar apenas complementos ao prontuário. Dados principais registrados durante a consulta não podem ser alterados após finalização.

**Funcionalidade:** Controle de permissões diferenciado para complementos após finalização. Bloqueio de edição de dados principais.

**Usuários:** Médico

**Relações:** Relaciona-se com RN010 (Finalização de Consulta), RN012 (Prontuário Médico)

---

### RN014 - Prescrições e Documentos por Médicos com Registro Válido

**Descrição:** Apenas médicos com CRM válido e registro profissional ativo podem emitir prescrições digitais, atestados médicos e outros documentos médicos. Sistema valida existência e formato do CRM antes de permitir emissão.

**Funcionalidade:** Validação automática de CRM e status do médico antes de permitir emissão de documentos. Registro de documentos vinculados à consulta e ao paciente.

**Usuários:** Médico

**Relações:** Relaciona-se com RN002 (Status de Médico), RN010 (Finalização de Consulta), RN012 (Prontuário Médico)

---

### RN015 - Agenda e Disponibilidade de Médicos

**Descrição:** Médicos podem configurar múltiplos locais de atendimento, slots de disponibilidade recorrentes (semanais) e específicos (datas), e bloquear datas específicas. Sistema valida conflitos entre slots e cria disponibilidade padrão se médico não configurar.

**Funcionalidade:** Gestão de agenda com validação de conflitos, criação de disponibilidade padrão e consulta pública de disponibilidade por data.

**Usuários:** Médico, Paciente (consulta de disponibilidade)

**Relações:** Relaciona-se com RN001 (Agendamento), RN004 (Validação de Conflitos)

---

### RN016 - Registro de Consultas no Histórico

**Descrição:** Todas as consultas devem ser registradas no histórico tanto do paciente quanto do médico. Histórico inclui dados da consulta, prontuário, prescrições, exames e documentos relacionados.

**Funcionalidade:** Registro automático de consultas nos perfis de paciente e médico. Associação de todos os dados relacionados à consulta.

**Usuários:** Sistema (automático)

**Relações:** Relaciona-se com RN001 (Agendamento), RN010 (Finalização de Consulta), RN012 (Prontuário Médico)

---

### RN017 - CRM Único e Formato Válido

**Descrição:** Cada médico deve possuir um CRM único no sistema. CRM deve seguir formato alfanumérico (apenas letras maiúsculas e números), com tamanho mínimo de 4 e máximo de 20 caracteres.

**Funcionalidade:** Validação de unicidade e formato do CRM durante cadastro e atualização de perfil médico.

**Usuários:** Médico, Administrador

**Relações:** Relaciona-se com RN002 (Status de Médico), RN010 (Cadastro de Médico), RN014 (Prescrições e Documentos)

---

### RN018 - Especializações Mínimas para Médicos

**Descrição:** Médicos devem possuir pelo menos uma especialização vinculada ao perfil. Máximo de 5 especializações por médico. Especializações devem existir no catálogo do sistema.

**Funcionalidade:** Validação de quantidade mínima e máxima de especializações durante cadastro e atualização. Validação de existência das especializações no sistema.

**Usuários:** Médico, Administrador

**Relações:** Relaciona-se com RN010 (Cadastro de Médico), RN019 (Catálogo de Especializações)

---

### RN019 - Catálogo de Especializações

**Descrição:** Especializações devem ser cadastradas no catálogo do sistema antes de serem vinculadas a médicos. Especializações possuem nome único (máximo 100 caracteres) e podem ser ativadas ou desativadas.

**Funcionalidade:** CRUD de especializações com validação de unicidade de nome. Endpoints públicos para consulta de especializações disponíveis.

**Usuários:** Administrador

**Relações:** Relaciona-se com RN018 (Especializações Mínimas)

---

### RN020 - Acesso a Consultas por Participantes

**Descrição:** Apenas o médico e o paciente vinculados a uma consulta podem visualizar e acessar os detalhes da mesma. Outros usuários não têm permissão de acesso, mesmo sendo médicos ou pacientes do sistema.

**Funcionalidade:** Validação de permissões baseada em relacionamento médico-paciente da consulta. Bloqueio de acesso não autorizado.

**Usuários:** Médico, Paciente

**Relações:** Relaciona-se com RN001 (Agendamento), RN012 (Prontuário Médico)

---

### RN021 - Edição de Consultas em Andamento

**Descrição:** Consultas com status "in_progress" não podem ter campos críticos alterados (médico, paciente, data/hora). Apenas campos não críticos podem ser atualizados durante a consulta.

**Funcionalidade:** Validação de status e bloqueio de edição de campos críticos durante consulta em andamento.

**Usuários:** Médico, Paciente

**Relações:** Relaciona-se com RN007 (Transições de Status), RN009 (Início de Consulta)

---

### RN022 - Marcação de No-Show

**Descrição:** Apenas médicos podem marcar consultas como "no_show" (paciente não compareceu). Consulta deve estar com status "scheduled" para ser marcada como no-show.

**Funcionalidade:** Validação de permissão e status antes de permitir marcação de no-show. Atualização automática de status e criação de log.

**Usuários:** Médico

**Relações:** Relaciona-se com RN007 (Transições de Status)

---

### RN023 - Auditoria de Prontuários Médicos

**Descrição:** Todas as ações realizadas em prontuários médicos devem ser registradas em logs de auditoria. Logs incluem IP, user agent, metadados e não podem ser excluídos (compliance LGPD).

**Funcionalidade:** Registro automático de todas as ações em prontuários. Armazenamento permanente de logs de auditoria.

**Usuários:** Sistema (automático)

**Relações:** Relaciona-se com RN012 (Prontuário Médico), RN013 (Complementos ao Prontuário)

---

### RN024 - Exportação de Prontuários em PDF

**Descrição:** Médicos podem exportar prontuário completo de pacientes atendidos em PDF. Pacientes podem exportar seu próprio prontuário em PDF. PDFs incluem todos os dados não privados do prontuário.

**Funcionalidade:** Geração de PDF completo do prontuário com formatação adequada. Filtragem de dados privados para pacientes.

**Usuários:** Médico, Paciente

**Relações:** Relaciona-se com RN012 (Prontuário Médico), RN020 (Acesso a Consultas)

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

### Navegação e Autenticação
• Navegação autenticada: `GET /dashboard`, `GET /appointments`, `GET /consultations`, `GET /healthRecords`
• Autenticação e registro: `GET/POST /login`, `GET/POST /register`, `GET/POST /register/patient`, `GET/POST /register/doctor`
• Configurações: `GET/PATCH /settings/profile`, `GET/PUT /settings/password`

### Especializações
• Especializações (web): `Route::resource('specializations', ...)`
• Especializações (API pública): `GET /api/specializations/list`, `GET /api/specializations/options`

### Videoconferência
• Videoconferência: `POST /video-call/request/{user}`, `POST /video-call/request/status/{user}`

### Prontuários Médicos
• `GET /doctor/patients/{patient}/medical-record` - Visualizar prontuário
• `POST /doctor/patients/{patient}/medical-record/export` - Exportar prontuário
• `POST /doctor/patients/{patient}/medical-record/diagnoses` - Criar diagnóstico
• `POST /doctor/patients/{patient}/medical-record/prescriptions` - Criar prescrição
• `POST /doctor/patients/{patient}/medical-record/examinations` - Criar exame
• `POST /doctor/patients/{patient}/medical-record/notes` - Criar anotação
• `POST /doctor/patients/{patient}/medical-record/certificates` - Criar atestado
• `POST /doctor/patients/{patient}/medical-record/vital-signs` - Registrar sinais vitais
• `POST /doctor/patients/{patient}/medical-record/consultations/pdf` - Gerar PDF de consulta
• `POST /patient/medical-records/export` - Paciente exportar prontuário
• `POST /patient/medical-records/documents` - Paciente anexar documento

### Agenda e Disponibilidade
• `GET /doctor/schedule` - Visualizar agenda
• `GET /doctor/doctors/{doctor}/schedule` - Visualizar agenda de médico
• `POST /doctor/doctors/{doctor}/schedule/save` - Salvar configuração de agenda
• `POST /doctor/doctors/{doctor}/locations` - Criar local de atendimento
• `PUT /doctor/doctors/{doctor}/locations/{location}` - Atualizar local
• `DELETE /doctor/doctors/{doctor}/locations/{location}` - Excluir local
• `POST /doctor/doctors/{doctor}/availability` - Criar slot de disponibilidade
• `PUT /doctor/doctors/{doctor}/availability/{slot}` - Atualizar slot
• `DELETE /doctor/doctors/{doctor}/availability/{slot}` - Excluir slot
• `POST /doctor/doctors/{doctor}/blocked-dates` - Bloquear data
• `DELETE /doctor/doctors/{doctor}/blocked-dates/{blockedDate}` - Desbloquear data
• `GET /api/doctors/{doctor}/availability/{date}` - Consultar disponibilidade (público)

### Timeline de Profissional
• `GET /api/timeline-events` - Listar eventos
• `POST /api/timeline-events` - Criar evento
• `GET /api/timeline-events/{timelineEvent}` - Visualizar evento
• `PUT /api/timeline-events/{timelineEvent}` - Atualizar evento
• `DELETE /api/timeline-events/{timelineEvent}` - Excluir evento

### Consultas
• `GET /doctor/consultations/{appointment}` - Detalhes da consulta
• `POST /doctor/consultations/{appointment}/start` - Iniciar consulta
• `POST /doctor/consultations/{appointment}/save-draft` - Salvar rascunho
• `POST /doctor/consultations/{appointment}/finalize` - Finalizar consulta
• `POST /doctor/consultations/{appointment}/complement` - Adicionar complemento
• `GET /doctor/consultations/{appointment}/pdf` - Gerar PDF da consulta

---

# 4. DOCUMENTO DE ESPECIFICAÇÃO DE INTERFACES (DEI)

## 4.1 Mockups / Protótipos de Tela

(Aqui deverão constar os mockups das telas principais, fluxos de navegação e descrição do layout das interfaces.)

---

# 5. DOCUMENTAÇÃO TÉCNICA

## 5.1. Arquitetura do Sistema

O sistema **Telemedicina para Todos** foi desenvolvido seguindo uma arquitetura em camadas bem definida, separando responsabilidades e garantindo manutenibilidade, escalabilidade e testabilidade do código.

### 5.1.1. Segmentação da Arquitetura

A arquitetura do sistema é segmentada em três camadas principais:

#### 5.1.1.1. Camada Cliente (Frontend)

A camada cliente é responsável pela interface do usuário e interação com o sistema. Implementada como Single Page Application (SPA) utilizando Vue.js 3 com Inertia.js, proporciona uma experiência fluida e responsiva sem recarregamentos de página.

**Características:**
- **Framework:** Vue.js 3 (Composition API)
- **Roteamento:** Inertia.js (server-side routing)
- **Build Tool:** Vite 7.0+
- **Estilização:** Tailwind CSS 4.1+
- **TypeScript:** Suporte completo para type-safety
- **Componentes UI:** Reka UI (componentes acessíveis)
- **Videoconferência:** PeerJS (WebRTC P2P)
- **WebSockets:** Laravel Echo + Vue Echo

**Estrutura do Frontend:**
```
resources/js/
├── pages/              # Páginas Inertia (rotas)
│   ├── Doctor/        # Páginas específicas do médico
│   ├── Patient/       # Páginas específicas do paciente
│   └── Shared/        # Páginas compartilhadas
├── components/         # Componentes Vue reutilizáveis
├── composables/       # Composables Vue (lógica reutilizável)
├── layouts/           # Layouts base
├── types/            # Definições TypeScript
└── app.ts            # Entry point da aplicação
```

#### 5.1.1.2. Camada Servidor (Backend)

A camada servidor é responsável pelo processamento de requisições, lógica de negócio, validações e comunicação com o banco de dados. Implementada em Laravel 12 (PHP 8.2+), seguindo o padrão MVC com separação clara de responsabilidades.

**Características:**
- **Framework:** Laravel 12
- **Linguagem:** PHP 8.2+
- **Padrão Arquitetural:** Controller → Service → Repository (DDD Light)
- **Autenticação:** Laravel Sanctum
- **WebSockets:** Laravel Reverb
- **Queue System:** Redis (para jobs assíncronos)
- **Cache:** Redis
- **Validação:** Form Requests (validação de entrada)

**Estrutura do Backend:**
```
app/
├── Http/
│   ├── Controllers/   # Controladores (Presentation Layer)
│   │   ├── Doctor/   # Controllers específicos do médico
│   │   ├── Patient/  # Controllers específicos do paciente
│   │   └── Auth/     # Controllers de autenticação
│   ├── Middleware/   # Middleware personalizado
│   └── Requests/     # Form Requests (validação)
├── Services/         # Services (Application Layer)
├── Models/          # Models Eloquent (Domain Layer)
├── Events/          # Eventos do sistema
├── Observers/       # Observers de modelos
├── Jobs/            # Jobs assíncronos
└── Policies/        # Políticas de autorização
```

#### 5.1.1.3. Camada de Dados (Database)

A camada de dados é responsável pelo armazenamento persistente de informações. Utiliza PostgreSQL 16 como banco de dados relacional principal, com Redis para cache e filas.

**Características:**
- **SGBD Principal:** PostgreSQL 16
- **Cache/Queue:** Redis 7
- **ORM:** Eloquent (Laravel)
- **Migrations:** Controle de versão do schema
- **Soft Deletes:** Exclusão lógica de registros
- **UUIDs:** Identificadores únicos universais

**Estrutura de Dados:**
- **Tabelas Principais:** users, doctors, patients, appointments, specializations
- **Tabelas de Relacionamento:** doctor_specialization (N:N)
- **Tabelas de Prontuário:** diagnoses, prescriptions, examinations, clinical_notes, medical_certificates, vital_signs, medical_documents
- **Tabelas de Auditoria:** appointment_logs, medical_record_audit_logs
- **Tabelas de Videoconferência:** video_call_rooms, video_call_events

#### 5.1.1.4. Explicação da Segmentação

A segmentação em três camadas principais (Cliente, Servidor e Dados) proporciona:

1. **Separação de Responsabilidades:** Cada camada possui responsabilidades bem definidas, facilitando manutenção e evolução.
2. **Escalabilidade:** Camadas podem ser escaladas independentemente conforme necessidade.
3. **Testabilidade:** Cada camada pode ser testada isoladamente.
4. **Reutilização:** Lógica de negócio centralizada no backend pode ser reutilizada por diferentes clientes (web, mobile, API).
5. **Segurança:** Validações e regras de negócio centralizadas no servidor garantem segurança independente do cliente.

## 5.2. Tecnologias Utilizadas

### 5.2.1. Frontend

**Framework e Bibliotecas Principais:**
- **Vue.js 3.5.13** - Framework JavaScript reativo para construção de interfaces
- **Inertia.js 2.1.0** - Bridge entre frontend e backend, eliminando necessidade de API REST
- **TypeScript 5.2.2** - Superset JavaScript com tipagem estática
- **Vite 7.0.4** - Build tool e dev server de alta performance
- **Tailwind CSS 4.1.1** - Framework CSS utility-first
- **Reka UI 2.6.0** - Biblioteca de componentes Vue acessíveis
- **VueUse 12.8.2** - Coleção de composables Vue úteis
- **PeerJS 1.5.5** - Biblioteca para WebRTC P2P (videoconferência)
- **Laravel Echo 2.2.0** - Cliente WebSocket para Laravel Reverb
- **Lucide Vue Next 0.468.0** - Biblioteca de ícones

**Ferramentas de Desenvolvimento:**
- **ESLint 9.17.0** - Linter para JavaScript/TypeScript
- **Prettier 3.4.2** - Formatador de código
- **Vue TSC 2.2.4** - Type checking para Vue

### 5.2.2. Backend

**Framework e Bibliotecas Principais:**
- **Laravel 12.0** - Framework PHP moderno e robusto
- **PHP 8.2+** - Linguagem de programação do servidor
- **Laravel Sanctum** - Sistema de autenticação para SPAs
- **Laravel Reverb 1.0** - Servidor WebSocket nativo do Laravel
- **Laravel Wayfinder 0.1.9** - Utilitário de navegação
- **Intervention Image 3.11** - Processamento de imagens
- **DomPDF 3.1** - Geração de PDFs

**Ferramentas de Desenvolvimento:**
- **Laravel Pint 1.18** - Code style fixer
- **Laravel Sail 1.41** - Ambiente Docker para desenvolvimento
- **PHPUnit 11.5.3** - Framework de testes
- **Laravel Pail 1.2.2** - Visualizador de logs em tempo real

### 5.2.3. Banco de Dados

**Sistemas de Gerenciamento:**
- **PostgreSQL 16** - Banco de dados relacional principal
- **Redis 7** - Cache e sistema de filas

**Características:**
- **ORM:** Eloquent (Laravel)
- **Migrations:** Controle de versão do schema
- **Soft Deletes:** Exclusão lógica implementada
- **UUIDs:** Identificadores únicos universais para todas as entidades
- **Relacionamentos:** Suporte completo a relacionamentos 1:1, 1:N e N:N

### 5.2.4. Ferramentas de Apoio

**Versionamento:**
- **Git** - Controle de versão distribuído
- **GitHub** - Repositório remoto e colaboração

**Containerização:**
- **Docker** - Containerização da aplicação
- **Docker Compose** - Orquestração de containers

**Build e Deploy:**
- **Composer** - Gerenciador de dependências PHP
- **NPM/Pnpm** - Gerenciador de dependências JavaScript
- **Vite** - Build tool para frontend

**Monitoramento e Logs:**
- **Laravel Pail** - Visualização de logs em tempo real
- **Laravel Telescope** (planejado) - Debug e monitoramento

### 5.2.5. Padrões Adotados

#### 5.2.5.1. Padrões Arquiteturais

**Padrão MVC (Model-View-Controller):**
- **Models:** Representam entidades de domínio e acesso a dados
- **Views:** Componentes Vue.js renderizados via Inertia.js
- **Controllers:** Recebem requisições e coordenam Services

**Padrão Service Layer:**
- Lógica de negócio isolada em Services
- Controllers delegam processamento para Services
- Services orquestram Models e regras de negócio

**Padrão Repository (DDD Light):**
- Eloquent ORM atua como Repository pattern
- Models encapsulam acesso a dados
- Scopes e Query Builders para consultas reutilizáveis

**Padrão Observer:**
- Observers monitoram eventos de modelos
- Exemplo: `AppointmentsObserver` registra logs automáticos

**Padrão Event-Driven:**
- Eventos para comunicação assíncrona
- Broadcasting em tempo real via WebSockets
- Jobs para processamento assíncrono

#### 5.2.5.2. Arquitetura em Camadas

O sistema segue uma arquitetura em camadas bem definida:

```
┌─────────────────────────────────────┐
│   Presentation Layer (Controllers)   │
│   - Recebe requisições HTTP          │
│   - Valida entrada (Form Requests)   │
│   - Retorna respostas Inertia        │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Application Layer (Services)        │
│   - Lógica de negócio                │
│   - Orquestra Models                  │
│   - Aplica regras de negócio          │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Domain Layer (Models)               │
│   - Entidades de domínio              │
│   - Relacionamentos                   │
│   - Scopes e Accessors                │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Infrastructure Layer (Database)     │
│   - PostgreSQL                        │
│   - Redis (Cache/Queue)               │
└──────────────────────────────────────┘
```

**Benefícios:**
- **Separação de Responsabilidades:** Cada camada tem função específica
- **Testabilidade:** Camadas podem ser testadas isoladamente
- **Manutenibilidade:** Mudanças em uma camada não afetam outras
- **Reutilização:** Services podem ser reutilizados por diferentes controllers

### 5.2.6. Boas Práticas e Convenções

**Nomenclatura:**
- **Controllers:** PascalCase com sufixo "Controller" (ex: `DoctorDashboardController`)
- **Services:** PascalCase com sufixo "Service" (ex: `AppointmentService`)
- **Models:** PascalCase singular (ex: `Appointment`, `Doctor`)
- **Migrations:** snake_case com timestamp (ex: `2024_01_01_create_appointments_table`)
- **Routes:** kebab-case (ex: `/doctor/dashboard`)

**Estrutura de Código:**
- **PSR-12:** Padrão de codificação PHP
- **ESLint + Prettier:** Padrão de codificação JavaScript/TypeScript
- **TypeScript:** Tipagem estrita para type-safety
- **Composables:** Lógica reutilizável no frontend

**Segurança:**
- **Validação:** Form Requests para validação de entrada
- **Autorização:** Policies para controle de acesso
- **Autenticação:** Laravel Sanctum para SPAs
- **Criptografia:** Senhas com bcrypt
- **HTTPS:** Comunicação criptografada (produção)

**Performance:**
- **Cache:** Redis para cache de consultas frequentes
- **Queue:** Jobs assíncronos para tarefas pesadas
- **Eager Loading:** Prevenção de N+1 queries
- **Indexação:** Índices em colunas frequentemente consultadas

### 5.2.7. Requisitos de Infraestrutura

#### 5.2.7.1. Ambiente de Desenvolvimento Atual

**Servidor Web:**
- PHP 8.2 ou superior
- Extensões PHP: pdo, pdo_pgsql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath
- Composer 2.0+

**Banco de Dados:**
- PostgreSQL 16 ou superior
- Redis 7 ou superior

**Node.js:**
- Node.js 18+ ou superior
- NPM ou Pnpm

**Ferramentas:**
- Git
- Docker e Docker Compose (opcional, para ambiente containerizado)

**Comandos de Desenvolvimento:**
```bash
# Instalar dependências
composer install
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Executar migrations
php artisan migrate

# Iniciar servidor de desenvolvimento
composer dev  # Inicia servidor PHP, Vite, Queue e Reverb simultaneamente
```

#### 5.2.7.2. Hospedagem Planejada para Produção

**Servidor de Aplicação:**
- **Servidor Web:** Nginx ou Apache
- **PHP-FPM:** PHP 8.2+ com opcache habilitado
- **Process Manager:** Supervisor (para queues e workers)

**Banco de Dados:**
- **PostgreSQL:** 16+ em servidor dedicado ou cluster
- **Redis:** 7+ para cache e filas

**Infraestrutura:**
- **Load Balancer:** Nginx ou AWS ELB
- **CDN:** Para assets estáticos (CloudFlare, AWS CloudFront)
- **Storage:** S3 ou compatível para arquivos (PDFs, imagens)
- **SSL/TLS:** Certificado válido (Let's Encrypt ou comercial)

**Monitoramento:**
- **Logs:** Centralizados (CloudWatch, Papertrail)
- **APM:** New Relic, Datadog ou Laravel Telescope
- **Uptime:** Monitoramento de disponibilidade

**Backup:**
- **Banco de Dados:** Backups diários automatizados
- **Arquivos:** Backup de storage (S3 versioning)
- **Retenção:** Mínimo 30 dias

### 5.2.8. APIs e Integrações

**APIs Internas:**
- **Inertia.js:** Comunicação entre frontend e backend (não é REST API tradicional)
- **Laravel Echo:** WebSocket para comunicação em tempo real
- **Endpoints Públicos:** APIs REST para consulta de especializações e disponibilidade

**APIs Externas (Futuras):**
- **Pagamentos:** Integração com gateway de pagamento (Stripe, PagSeguro)
- **Validação CRM:** Integração com webservice de validação de CRM (futuro)
- **Notificações Push:** Serviço de notificações push (OneSignal, Firebase)

**WebSockets:**
- **Laravel Reverb:** Servidor WebSocket nativo
- **Canais Privados:** Comunicação segura por usuário
- **Eventos:** Broadcasting de eventos em tempo real

### 5.2.9. Caracterização da API

**Tipo de API:**
- **Híbrida:** Inertia.js para SPA + Endpoints REST públicos

**Endpoints REST Públicos:**
- `GET /api/specializations/list` - Listar especializações (com filtros)
- `GET /api/specializations/options` - Opções de especializações para selects
- `GET /api/doctors/{doctor}/availability/{date}` - Consultar disponibilidade de médico

**Endpoints REST Autenticados:**
- `GET /api/timeline-events` - Listar eventos de timeline
- `POST /api/timeline-events` - Criar evento de timeline
- `GET /api/timeline-events/{id}` - Visualizar evento
- `PUT /api/timeline-events/{id}` - Atualizar evento
- `DELETE /api/timeline-events/{id}` - Excluir evento

**Autenticação:**
- **SPA:** Laravel Sanctum (cookies HTTP-only)
- **API:** Tokens de API (futuro, se necessário)

**Formato de Resposta:**
- **JSON:** Para endpoints REST
- **Inertia Response:** Para rotas web (componentes Vue)

**Versionamento:**
- Versionamento via prefixo `/api/v1/` (planejado para futuras versões)

## 5.3. Repositório e Código-Fonte

**Plataforma de Versionamento:**
- **GitHub:** Repositório principal do projeto
- **URL:** `https://github.com/Audri-Rian/TelemedicinaParaTodos`

**Estrutura do Repositório:**
```
TelemedicinaParaTodos/
├── app/                    # Código-fonte do backend (Laravel)
├── resources/              # Código-fonte do frontend (Vue.js)
├── database/               # Migrations, seeders, factories
├── routes/                 # Definição de rotas
├── config/                 # Arquivos de configuração
├── tests/                  # Testes automatizados
├── public/                 # Arquivos públicos
├── docs/                   # Documentação do projeto
├── docker-compose.yml      # Configuração Docker
├── Dockerfile              # Imagem Docker
├── composer.json           # Dependências PHP
├── package.json            # Dependências JavaScript
└── README.md               # Documentação principal
```

**Branches:**
- **main:** Branch principal (produção)
- **develop:** Branch de desenvolvimento (planejado)

**Commits:**
- Mensagens em português brasileiro
- Padrão: "tipo: descrição" (ex: "feat: adiciona sistema de prontuários")

**Licença:**
- MIT License (ver arquivo LICENSE)

---

## Histórico de Revisão

| Data       | Versão | Descrição                                                                                                 | Autor                              |
| ---------- | -----: | --------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| 08/08/2025 |    1.0 | Elaboração dos primeiros conteúdos para implementação no documento.                                      | Audri Rian Cordeiro Carvalho Alves |
| 14/08/2025 |    1.1 | Detalhamento de todos os tópicos já existentes na documentação.                                          | Audri Rian Cordeiro Carvalho Alves |
| 21/08/2025 |    1.2 | Inclusão dos requisitos funcionais e não funcionais, regras de negócio e classes.                       | Audri Rian Cordeiro Carvalho Alves |
| 28/08/2025 |    1.4 | Adição dos mockups de tela e o fluxo de navegação.                                                       | Audri Rian Cordeiro Carvalho Alves |
| 13/09/2025 |    1.5 | Alinhado com implementação: Especializações, cadastro separado, videoconferência P2P, modelo Consultas. | Audri Rian Cordeiro Carvalho Alves |
| 15/01/2025 |    2.0 | Integração de funcionalidades implementadas: Prontuários (RF014), Agenda (RF015), Timeline (RF016), atualizações em RF003 e RF004. | Audri Rian Cordeiro Carvalho Alves |

---

*Última atualização: Janeiro 2025*
*Versão: 2.0*

*Fim do documento.*
