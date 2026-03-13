1 DOCUMENTO DE DEFINIÇÃO DE ESCOPO (DDE)

1.1 INTRODUÇÃO

O **Telemedicina para Todos** é um software de telemedicina moderna, segura e acessível, desenvolvido com Laravel (PHP) e Vue.js. Destina-se ao setor de **saúde**, conectando médicos e pacientes de forma remota. A ideia central é oferecer consultas online, agendamento inteligente, prontuários digitais, prescrições digitais, videoconferência em tempo real e comunicação segura em um único sistema integrado, eliminando barreiras geográficas e otimizando o acesso à saúde.

1.2 VISÃO GERAL DO DOCUMENTO

Este documento apresenta o escopo detalhado do **Telemedicina para Todos**, uma plataforma web destinada à teleatendimento entre profissionais da saúde (médicos, psicólogos, nutricionistas, fisioterapeutas, entre outros) e pacientes. Funciona como um "mapa" do projeto: contém as definições de escopo, objetivos, entregáveis, premissas e restrições que guiam o desenvolvimento, além da especificação de requisitos (ERS), modelagem (DEM), interfaces (DEI), documentação técnica, manual do usuário e referências.

1.3 IDENTIFICAÇÃO DO PROJETO

- **Nome do Projeto:** Telemedicina para Todos
- **Autor:** Audri Rian Cordeiro Carvalho Alves
- **Curso/Contexto:** Projeto de Desenvolvimento de Sistemas Web (Superior de Análise e Desenvolvimento de Sistemas)
- **Repositório:** Código-fonte disponível no GitHub (organização do projeto conforme documentação em `docs/`)

1.4 OBJETIVOS DO PROJETO

**Objetivo Geral:** Desenvolver uma plataforma web de teleatendimento que conecte profissionais da saúde e pacientes, permitindo agendamento e realização de consultas online de forma prática, segura e acessível.

**Objetivos Específicos:**
- Levantar e documentar requisitos funcionais e não funcionais (regras de negócio, compliance LGPD, segurança).
- Projetar a arquitetura em camadas (DDD Light) com separação de domínio, aplicação e infraestrutura.
- Implementar cadastro e autenticação segregados para médicos e pacientes, com perfis e permissões.
- Implementar sistema de agenda e disponibilidade (locais, slots recorrentes/específicos, datas bloqueadas).
- Implementar módulo de consultas (agendamento, status, reagendamento, cancelamento) integrado ao prontuário.
- Implementar videoconferência em tempo real (WebRTC via PeerJS) com sinalização por WebSockets (Laravel Reverb).
- Implementar prontuários digitais (diagnósticos, prescrições, exames, anotações, atestados, sinais vitais, documentos) com auditoria.
- Validar a solução por meio de testes, documentação da API (OpenAPI/Swagger/ReDoc) e conformidade com regras do sistema.

1.5 JUSTIFICATIVA

O acesso à saúde ainda enfrenta barreiras geográficas, tempo de deslocamento e custos. Pacientes em cidades pequenas ou com rotina apertada têm dificuldade para consultas presenciais; médicos perdem tempo com faltas e desorganização de agenda. A telemedicina regulamentada reduz esses problemas ao permitir consultas remotas com segurança e rastreabilidade.

O Telemedicina para Todos resolve isso ao centralizar em uma única plataforma: agendamento inteligente, videoconferência integrada, prontuários digitais com auditoria (LGPD), prescrições e atestados digitais. Assim, pacientes ganham praticidade e acesso; médicos ganham eficiência e organização; e o sistema de saúde amplia seu alcance sem depender apenas do modelo tradicional presencial.  

1.6 IDENTIFICAÇÃO DOS REQUISITOS 

Por convenção, os requisitos são referenciados pelo nome da subseção onde estão descritos, seguido do seu identificador, conforme o esquema abaixo:
O requisito funcional [Cadastro de Usuários.RF-01] está localizado na subseção “Requisitos Funcionais”, dentro do bloco identificado como [RF-01].
O requisito não funcional [Disponibilidade.NF-04] encontra-se na seção “Requisitos Não Funcionais de Confiabilidade”, no bloco identificado como [NF-04].
1.6.1 Prioridades dos Requisitos
Os requisitos do sistema são classificados em três níveis de prioridade:
Essencial: indispensável para o funcionamento do sistema. Sem ele, o sistema não opera. Deve ser obrigatoriamente implementado.
Importante: afeta a qualidade do funcionamento. O sistema pode ser utilizado sem esse requisito, mas de forma insatisfatória. Sua implementação é recomendada.
Desejável: não interfere nas funcionalidades básicas. O sistema funciona bem sem ele. Pode ser incluído em versões futuras, caso não haja tempo para implementá-lo na versão atual.


1.7 ESCOPO DO PRODUTO E ENTREGÁVEIS

**1.7.1 Funcionalidades Previstas**

- Cadastro e autenticação de usuários (pacientes e médicos), com perfis segregados e fluxos dedicados.
- Agendamento de consultas com validação de disponibilidade, conflitos de horário, reagendamento e cancelamento.
- Consultas por videoconferência em tempo real (WebRTC via PeerJS) com sinalização por WebSockets (Laravel Reverb).
- Prontuários digitais: diagnósticos (CID-10), prescrições digitais, solicitação de exames, anotações clínicas (públicas/privadas), atestados médicos com código de verificação, sinais vitais, documentos anexados e auditoria completa (LGPD).
- Sistema de agenda e disponibilidade: locais de atendimento (teleconsulta, consultório, hospital, clínica), slots recorrentes e específicos, datas bloqueadas.
- Timeline profissional (educação, cursos, certificados, projetos) com controle de visibilidade.
- Emissão de PDF de consulta e exportação de prontuário em PDF.
- Notificações e sinalização em tempo real via canais privados (Laravel Echo/Reverb).
- API REST documentada (OpenAPI/Swagger/ReDoc) para especializações e disponibilidade pública.

**1.7.2 Entregáveis**

- Código-fonte no repositório GitHub (frontend Vue.js + backend Laravel).
- Banco de dados estruturado (migrations, modelo documentado em `docs/layers/persistence/database/diagrama_banco_dados.md`).
- Documentação de arquitetura, requisitos, regras de negócio, guias de desenvolvimento e instalação (em `docs/`).
- Especificação da API (OpenAPI 3.x) e interfaces Swagger UI e ReDoc.
- Documento de Definição de Escopo (DDE), Especificação de Requisitos (ERS), modelagem (DEM), interfaces (DEI) e documentação técnica (este documento e artefatos referenciados).

1.8 PREMISSAS E RESTRIÇÕES

**1.8.1 Premissas**

- Usuários terão acesso à internet estável e dispositivos compatíveis (navegador atualizado, câmera e microfone para videoconferência).
- Profissionais cadastrados possuem registro válido em conselhos de classe (ex.: CRM).
- Haverá interesse de médicos e pacientes em utilizar a plataforma para consultas remotas.
- O ambiente de produção ou homologação terá suporte a WebSockets (Laravel Reverb) e filas (quando configurado).

**1.8.2 Restrições**

- O projeto deve ser concluído dentro do prazo definido (disciplina/entrega acadêmica).
- Backend obrigatoriamente em **Laravel** (PHP 8.2+); banco de dados **MySQL** ou SQLite em desenvolvimento.
- Frontend em **Vue.js 3** com **Inertia.js**, **TypeScript** e **Tailwind CSS** (conforme stack atual do projeto).
- O sistema será inicialmente apenas web (sem aplicativo nativo).
- Funcionalidades devem estar em conformidade com a **LGPD** e regras de negócio documentadas em `docs/layers/architecture-governance/requirements/SystemRules.md`.

1.9 CRITÉRIOS DE ACEITAÇÃO DO PROJETO

- Plataforma funcionando em navegadores suportados (Chrome, Firefox, Edge, Safari em versões recentes).
- Consultas online com videoconferência em qualidade aceitável de áudio e vídeo (WebRTC/PeerJS).
- Autenticação segura com senhas criptografadas (bcrypt) e controle de acesso por perfil (médico/paciente).
- Desempenho: tempo de resposta das operações críticas inferior a 3 segundos em rede banda larga; testes de carga suportando até 500 usuários simultâneos (conforme requisitos não funcionais documentados).
- Interface responsiva e usável (layout responsivo, mensagens de erro claras).
- Conformidade com LGPD: consentimento, auditoria de ações em prontuários, dados sensíveis protegidos.
- Documentação da API (OpenAPI) gerada e acessível (Swagger UI / ReDoc) para endpoints públicos e protegidos.

1.10 EXCLUSÕES DO ESCOPO

- Validação automática de registros profissionais (ex.: CRM) via webservice externo; o CRM é cadastrado e único por contexto, sem integração com conselhos.
- Sistema de pagamentos completo integrado (cartão, PIX, boleto) — previsto para versão futura.
- Aplicativo mobile nativo (iOS/Android) — apenas interface web responsiva nesta versão.
- Integração com laboratórios, farmácias ou hospitais externos (interoperabilidade está em estudo/documentação em `docs/interoperabilidade/`).

1.11 STAKEHOLDERS ENVOLVIDOS

- **Pacientes** — usuários finais que agendam consultas, participam de videoconferências e acessam prontuários.
- **Profissionais da saúde (médicos)** — prestadores que configuram agenda, atendem por vídeo e preenchem prontuários.
- **Equipe de desenvolvimento / Autor** — responsável pela implementação e documentação (Audri Rian Cordeiro Carvalho Alves).
- **Professor orientador** — alinhamento acadêmico e avaliação do projeto.
- **Administrador do sistema** — gestão de especializações e configurações gerais (quando aplicável).
- **Testers / Avaliadores** — validação funcional e de aceitação (quando houver). 


1.12 RISCOS INICIAIS

- **Prazo curto:** entrega limitada ao período da disciplina pode restringir escopo ou refinamentos.
- **Complexidade da videoconferência:** dependência de WebRTC/PeerJS e Laravel Reverb; falhas de rede ou firewall podem afetar chamadas.
- **Dependência de APIs e serviços externos:** Reverb, filas (quando usadas) e ambiente de hospedagem precisam estar estáveis.
- **Segurança e LGPD:** implementação incorreta de auditoria, consentimento ou criptografia pode gerar não conformidade.
- **Equipe em aprendizado:** uso de Laravel 12, Vue 3, Inertia, TypeScript e Reverb exige curva de aprendizado.








2 DOCUMENTO DE ESPECIFICAÇÃO DE REQUISITOS (ERS)

2.1 REQUISITOS FUNCIONAIS

Requisitos funcionais são as funções que usuários e clientes esperam que o software ofereça. Eles estão diretamente ligados às funcionalidades que o sistema deve fornecer. Abaixo estão os principais requisitos funcionais do Telemedicina para Todos (detalhamento completo em `docs/layers/architecture-governance/requirements/FuncionalitsGuide.md`).

**[RF-01] Cadastro de Pacientes**  
O sistema deve permitir cadastro, alteração e consulta de pacientes com dados básicos (nome, e-mail, senha, gênero, data de nascimento, telefone) e, em etapa posterior, contato de emergência e dados clínicos opcionais.  
Atores: Paciente.  
Prioridade: Essencial.  
Critério de aceitação: Usuário preenche campos obrigatórios e recebe confirmação de conta; cadastro completo (incluindo contato de emergência) é exigido para agendamento.

**[RF-02] Cadastro de Médicos**  
O sistema deve permitir cadastro de médicos com CRM único, seleção de uma ou mais especializações e criação de perfil vinculado ao usuário.  
Atores: Médico.  
Prioridade: Essencial.  
Critério de aceitação: Médico registrado com CRM e especializações válidas; perfil ativo disponível para agendamentos.

**[RF-03] Agendamento de Consultas**  
O sistema deve permitir que pacientes agendem consultas com médicos disponíveis, com validação de slots, conflitos de horário, reagendamento e cancelamento dentro das janelas configuradas.  
Atores: Paciente, Médico.  
Prioridade: Essencial.  
Critério de aceitação: Consulta criada com status (scheduled, rescheduled, in_progress, completed, no_show, cancelled); integração com prontuário e geração de PDF da consulta.

**[RF-04] Videoconferência**  
O sistema deve permitir solicitação e realização de chamada de vídeo entre médico e paciente em tempo real (WebRTC via PeerJS), com sinalização por WebSockets (Laravel Reverb).  
Atores: Médico, Paciente.  
Prioridade: Essencial.  
Critério de aceitação: Sala de videoconferência criada para a consulta; áudio e vídeo estabelecidos; eventos de entrada/saída rastreados.

**[RF-05] Prontuários Médicos**  
O sistema deve permitir gestão completa de prontuários: diagnósticos (CID-10), prescrições, exames, anotações clínicas, atestados, sinais vitais, documentos anexados, com auditoria e exportação em PDF.  
Atores: Médico, Paciente.  
Prioridade: Essencial.  
Critério de aceitação: Médico edita prontuário durante/finalização da consulta; paciente visualiza itens não privados; todas as ações registradas em log.

**[RF-06] Agenda e Disponibilidade**  
O sistema deve permitir que médicos configurem locais de atendimento, slots recorrentes e específicos, e datas bloqueadas; pacientes devem poder consultar disponibilidade por data.  
Atores: Médico, Paciente.  
Prioridade: Essencial.  
Critério de aceitação: Slots ativos e não bloqueados disponíveis para agendamento; validação de conflitos de horário.

**[RF-07] Autenticação e Controle de Acesso**  
O sistema deve autenticar usuários (e-mail e senha) e redirecionar conforme perfil (médico ou paciente), com proteção de rotas.  
Atores: Todos.  
Prioridade: Essencial.  
Critério de aceitação: Login válido concede acesso à área restrita correspondente ao perfil.

2.2 REQUISITOS NÃO FUNCIONAIS

Os requisitos não funcionais representam atributos de qualidade que o software deve possuir (desempenho, segurança, usabilidade, confiabilidade). Referência completa em `docs/layers/architecture-governance/requirements/FuncionalitsGuide.md`.

**[NF-01] Desempenho**  
O tempo de resposta das operações críticas não deve exceder 3 segundos em rede banda larga; o sistema deve suportar até 500 usuários simultâneos em testes de carga.  
Prioridade: Essencial.  
Critério de medição: Ferramentas de medição (Lighthouse, testes de carga).

**[NF-02] Acesso Web**  
Acesso por navegadores modernos; suporte a dispositivos com câmera e microfone para videoconferência.  
Prioridade: Essencial.

**[NF-03] Autenticação Segura**  
Senhas criptografadas (bcrypt); proteção contra força bruta (ex.: bloqueio após tentativas falhas).  
Prioridade: Essencial.

**[NF-04] Conformidade LGPD**  
Consentimento, direito de exclusão, criptografia em repouso e em trânsito (HTTPS/TLS), auditoria de ações em prontuários.  
Prioridade: Essencial.

**[NF-05] Interface Amigável**  
Layout responsivo e intuitivo; mensagens de erro claras.  
Prioridade: Importante.

2.3 REGRAS DE NEGÓCIO

As regras de negócio definem os limites e comportamentos do sistema. Detalhamento em `docs/layers/architecture-governance/requirements/SystemRules.md` e `FuncionalitsGuide.md`.

**[RN-01] Agendamento com profissionais ativos**  
Pacientes só podem agendar consultas com médicos com status "active". Médicos inativos ou suspensos não recebem novos agendamentos.  
Prioridade: Essencial.

**[RN-02] Cadastro completo para agendamento**  
Pacientes devem completar a segunda etapa de cadastro (contato de emergência) antes de agendar consultas.  
Prioridade: Essencial.

**[RN-03] Validação de conflitos de horário**  
Não é permitido agendar consultas em horários que conflitem com outras consultas do mesmo médico (status scheduled, rescheduled ou in_progress).  
Prioridade: Essencial.

**[RN-04] Transições de status de consulta**  
Consultas seguem fluxo controlado: scheduled/rescheduled → in_progress, cancelled, no_show; in_progress → completed. Transições inválidas são bloqueadas.  
Prioridade: Essencial.

**[RN-05] Acesso ao prontuário**  
Apenas médicos que atenderam o paciente podem editar o prontuário; paciente visualiza apenas itens não privados. Todas as ações são registradas em log de auditoria.  
Prioridade: Essencial.

3 DOCUMENTO DE ESPECIFICAÇÃO DE MODELAGEM (DEM)

A modelagem do sistema está documentada na pasta `docs/layers/` e em diagramas referenciados abaixo. O backend utiliza Laravel Eloquent (ORM) para mapeamento objeto-relacional.

**3.1 MODELAGEM DE DADOS**

**3.1.1 Entidade-Relacionamento**  
O diagrama ER (Mermaid) e a descrição das entidades (users, doctors, patients, specializations, service_locations, availability_slots, blocked_dates, appointments, prescriptions, diagnoses, examinations, clinical_notes, medical_certificates, vital_signs, medical_documents, medical_record_audit_logs, video_call_rooms, video_call_events, timeline_events, etc.) estão em `docs/layers/persistence/database/diagrama_banco_dados.md`. As tabelas são criadas via migrations em `database/migrations/`.

**3.1.2 Dicionário de Dados**  
Os atributos, tipos e relacionamentos das entidades constam no diagrama e nas migrations; convenções do projeto: UUIDs como chaves primárias, timestamps obrigatórios, soft deletes onde aplicável (documentado em `docs/layers/architecture-governance/requirements/SystemRules.md`).

**3.2 MODELAGEM COMPORTAMENTAL**

**3.2.1 Diagrama de Sequência**  
Fluxos de agendamento, início de consulta, videoconferência e finalização de consulta estão descritos em `docs/modules/appointments/AppointmentsLogica.md` e `docs/layers/signaling/videocall/VideoCallImplementation.md`. Diagramas de sequência em formato Mermaid ou Draw.io podem ser incluídos na pasta `docs/layers/architecture-governance/diagrams/` ou em documentos de módulo.

**3.2.2 Diagrama de Estados**  
Os estados de consulta (scheduled, rescheduled, in_progress, completed, no_show, cancelled) e as transições permitidas estão definidos nas regras de negócio (RN007 e afins) em `docs/layers/architecture-governance/requirements/FuncionalitsGuide.md` e `SystemRules.md`.

**3.3 MODELAGEM ESTRUTURAL**

**3.3.1 Diagrama de Caso de Uso**  
Casos de uso (cadastro médico/paciente, agendamento, videoconferência, prontuário, agenda, etc.) estão listados no Guia de Funcionalidades e podem ser representados em diagrama UML na pasta de diagramas do projeto.

**3.3.2 Diagrama de Componentes**  
A segmentação em camadas (Controllers, Services, Models, Events, Jobs, Policies) e a organização por domínio (Doctor, Patient, Auth, VideoCall, Settings) estão descritas em `docs/layers/architecture-governance/Architecture/Arquitetura.md` (estrutura do backend e do frontend).

**3.3.3 Diagrama de Arquitetura**  
A arquitetura em camadas (Migrations → Models → Services → Controllers → Events/Observers → Database/Broadcasting) e o fluxo de comunicação estão documentados em `docs/layers/architecture-governance/Architecture/Arquitetura.md`.

**3.4 MAPEAMENTO OBJETO-RELACIONAL (ORM)**  
O projeto utiliza **Laravel Eloquent** como ORM: modelos em `app/Models/` com relacionamentos (belongsTo, hasMany, etc.), casts, scopes, accessors e soft deletes. Migrations definem o esquema em `database/migrations/`.

**3.5 BPMN (BUSINESS PROCESS MODEL AND NOTATION)**  
Processos de negócio (fluxo de agendamento, fluxo de consulta, fluxo de prontuário) podem ser modelados em BPMN conforme necessidade; atualmente estão descritos em texto e regras em `docs/layers/architecture-governance/requirements/`.



4 DOCUMENTO DE ESPECIFICAÇÃO DE INTERFACES (DEI)

A interface do sistema é uma SPA (Single Page Application) com Vue.js 3 e Inertia.js; layouts e páginas estão em `resources/js/pages/` (auth, Doctor, Patient, settings) e componentes em `resources/js/components/`. O guia de frontend e convenções estão em `docs/layers/architecture-governance/Architecture/VueGuide.md`.

**4.1 WIREFRAMES**  
Wireframes das telas principais (login, registro médico/paciente, dashboard médico/paciente, agenda, consultas, videoconferência, prontuário, configurações) podem ser elaborados em ferramentas de prototipação e armazenados em `docs/` ou pasta de diagramas. O layout segue estrutura com AppShell, AppSidebar, AppHeader e layouts por contexto (AuthLayout, AppLayout, Settings).

**4.2 MOCKUPS**  
Mockups e protótipos de tela (incluindo fluxos de agendamento e videoconferência) podem constar na documentação de tarefas ou em versões anteriores do DDE/DEI (conforme histórico do projeto). A interface utiliza Reka UI, Tailwind CSS 4 e Lucide Vue para componentes e ícones.

**4.3 FLUXO DE NAVEGAÇÃO**  
A navegação é orientada por perfil: após login, médico é redirecionado para área Doctor (dashboard, consultas, agenda, pacientes, etc.) e paciente para área Patient (dashboard, buscar consultas, histórico, prontuário). Rotas são gerenciadas pelo Laravel Wayfinder e protegidas por middleware e guards; documentação em `docs/guides/GuiaDesenvolvedor.md` e arquitetura do frontend em `Arquitetura.md`.

5 DOCUMENTAÇÃO TÉCNICA

**5.1 ARQUITETURA DO SISTEMA**

O sistema segue uma **arquitetura em camadas** (monolítica modular), com backend Laravel e frontend Vue.js acoplados via Inertia.js. O modelo escolhido é de **camadas lógicas** no backend: Migrations → Models (Eloquent) → Services (lógica de negócio) → Controllers (HTTP) → Events/Observers/Jobs; e no frontend: páginas (Inertia), layouts, componentes UI e composables. O diagrama de arquitetura e o fluxo de comunicação estão em `docs/layers/architecture-governance/Architecture/Arquitetura.md`.

**5.1.1 Segmentação da Arquitetura**

- **Controllers:** Recebem requisições HTTP, validam via Form Requests, delegam a Services e retornam respostas Inertia ou JSON. Organizados por domínio: Auth, Doctor, Patient, Settings, VideoCall, Api.
- **Services:** Contêm a lógica de negócio (AppointmentService, AvailabilityService, MedicalRecordService, TimelineEventService, ScheduleService, etc.); orquestram modelos e regras.
- **Models (Eloquent):** Representam entidades, relacionamentos, casts, scopes e accessors; utilizam UUIDs e soft deletes quando aplicável.
- **Events/Observers/Jobs:** Eventos de domínio (RequestVideoCall, AppointmentStatusChanged, VideoCallRoomCreated, etc.), observers (AppointmentsObserver), jobs (ExpireVideoCallRooms, GenerateMedicalRecordPDF); broadcasting via Laravel Reverb.
- **Policies:** Controle de autorização (AppointmentPolicy, MedicalRecordPolicy, VideoCallPolicy, etc.).

A comunicação é unidirecional: Controller → Service → Model → Database; eventos disparam side effects (broadcasting, logs). Detalhes em `docs/layers/architecture-governance/Architecture/Arquitetura.md`.

**5.2 TECNOLOGIAS UTILIZADAS**

**5.2.1 Frontend**  
- Vue.js 3 (Composition API), TypeScript, Inertia.js (Vue 3 adapter), Vite 7; Tailwind CSS 4, Reka UI, Lucide Vue, VueUse, PeerJS (videoconferência). Versões em `package.json` (ex.: vue ^3.5.13, tailwindcss ^4.1.1, @inertiajs/vue3 ^2.1.0).

**5.2.2 Backend**  
- PHP 8.2+, Laravel 12, Inertia.js (servidor), Laravel Reverb (WebSockets), Laravel Wayfinder (roteamento), Laravel Sanctum (autenticação API), barryvdh/laravel-dompdf (PDF), intervention/image (imagens), darkaonline/l5-swagger (OpenAPI). Versões em `composer.json` (laravel/framework ^12.0, laravel/reverb ^1.0, inertiajs/inertia-laravel ^2.0).

**5.2.3 Banco de Dados**  
- SGBD: MySQL ou SQLite (desenvolvimento). ORM: Laravel Eloquent. Migrations em `database/migrations/`; diagrama em `docs/layers/persistence/database/diagrama_banco_dados.md`.

**5.2.4 Ferramentas de Apoio**  
- Git, GitHub; Vite (build e dev server); ESLint, Prettier (frontend); Laravel Pint (PHP); PHPUnit (testes); Laravel Sail (Docker); Postman/Insomnia para testes de API; L5-Swagger para geração da spec OpenAPI; Cursor/VS Code.

**5.2.5 Padrões Adotados**  
- **Repository (implícito):** Acesso a dados centralizado nos Models Eloquent; Services utilizam Models, não implementam repositórios abstratos adicionais.  
- **Service Layer:** Lógica de negócio em Services (AppointmentService, MedicalRecordService, etc.).  
- **Dependency Injection:** Laravel resolve dependências via container (Controllers e Services recebem dependências por construtor).  
- **Form Requests:** Validação de entrada nos controllers.  
- **Policies:** Autorização por modelo (Policy).  
- **Events e Listeners/Jobs:** Desacoplamento para broadcasting e tarefas assíncronas.

**5.2.6 Boas Práticas e Convenções**  
- **SOLID:** SRP nos Services (cada um com responsabilidade clara); dependências injetadas (DIP) via container Laravel.  
- **Clean Code:** Nomes significativos, funções com responsabilidade única; convenções em `docs/layers/architecture-governance/Architecture/DevGuide.md`.  
- **Respostas de API:** API REST retorna JSON; padrão de resposta documentado na spec OpenAPI (sucesso/erro com mensagens).  
- **Tratamento de erros:** Exceções tratadas pelo Laravel (handler); mensagens amigáveis ao usuário; stack traces apenas em debug.  
- **Segurança:** Variáveis sensíveis em `.env`; senhas com bcrypt; HTTPS/TLS em produção; auditoria em prontuários (LGPD).  
- **Versionamento:** Controle via Git; tags e versão do app em `package.json` (ex.: 1.2.0).

**5.2.7 Requisitos de Infraestrutura**  
- **Desenvolvimento:** PHP 8.2+, Node.js (para npm), Composer, extensões PHP habituais (mbstring, openssl, pdo, etc.); opcional: Docker (Laravel Sail).  
- **Produção:** Servidor web (Apache/Nginx), PHP 8.2+, MySQL; suporte a WebSockets para Reverb; filas (quando utilizadas). Detalhes em `docs/setup/Start.md` e `docs/guides/GuiaDesenvolvedor.md`.

**5.2.8 APIs e Integrações**  
- **Laravel Reverb:** Servidor WebSocket para sinalização em tempo real (videoconferência, notificações).  
- **PeerJS:** Cliente WebRTC no frontend para mídia P2P (vídeo/áudio).  
- **APIs externas consumidas:** Nenhuma obrigatória para o núcleo; interoperabilidade com laboratórios/farmácias está em estudo (`docs/interoperabilidade/`).  
- **Serviços opcionais:** E-mail (SMTP) para notificações; Redis para filas/cache quando configurado.

**5.2.9 Caracterização da API**  
- **Padrão:** REST. **Formato:** JSON. **Documentação:** OpenAPI 3.x; geração com `php artisan l5-swagger:generate`; interfaces Swagger UI (`/api/documentation`) e ReDoc (`/api/redoc`). Autenticação: sessão (cookie) para uso no navegador; API tokens quando aplicável. Ver `docs/guides/GuiaDesenvolvedor.md`.

**5.3 REPOSITÓRIO E CÓDIGO-FONTE**

- **Repositório:** Código-fonte no GitHub (repositório do projeto TelemedicinaParaTodos).  
- **Estrutura principal:**  
  - `app/` — Backend: Http/Controllers, Models, Services, Events, Observers, Jobs, Policies, Providers.  
  - `resources/js/` — Frontend: pages/, components/, layouts/, composables/, types/, lib/.  
  - `database/migrations/` — Esquema do banco.  
  - `routes/` — Rotas web e API.  
  - `config/` — Configurações (telemedicine.php para regras configuráveis).  
  - `docs/` — Documentação (arquitetura, requisitos, persistência, módulos, interoperabilidade, setup, guias).  
  - `tests/` — Testes PHPUnit.  
- **Lógica de negócio:** Services em `app/Services/` e regras em `app/Policies/`; configurações de regras em `config/telemedicine.php`. **Testes:** `tests/`. **Configurações:** `.env`, `config/`.

6 MANUAL DO USUÁRIO

O Manual do Usuário deve ser visual, sequencial e didático, para que uma pessoa com conhecimentos básicos de informática consiga operar o sistema (cadastro, agendamento, consulta por vídeo, visualização de prontuário) sem depender de termos técnicos. Abaixo está o que este manual deve conter no contexto do Telemedicina para Todos.

**Requisitos para acessar o sistema**  
- Navegador recomendado: Chrome, Firefox, Edge ou Safari (versões recentes), com JavaScript habilitado.  
- Para videoconferência: câmera e microfone; permissão do navegador para mídia.  
- Resolução de tela: mínima 1024×768; ideal 1366×768 ou superior para uso confortável.  
- Credenciais de teste: fornecer usuário e senha de teste (médico e paciente) para o avaliador acessar o sistema (conforme ambiente configurado).

**Estrutura recomendada (fluxos de tarefas)**  
- **Como se cadastrar como paciente:** acessar página de registro, preencher dados obrigatórios (nome, e-mail, senha, gênero, data de nascimento, telefone), concluir cadastro e completar etapa de contato de emergência para poder agendar.  
- **Como se cadastrar como médico:** acessar registro de médico, preencher dados e CRM, escolher especializações, concluir cadastro.  
- **Como agendar uma consulta (paciente):** entrar na área do paciente, buscar médico ou especialidade, escolher data/horário disponível, confirmar agendamento.  
- **Como participar da consulta por vídeo:** acessar no horário agendado, permitir câmera e microfone, entrar na sala de vídeo.  
- **Como editar perfil e senha:** acessar Configurações (perfil e senha) e salvar alterações.

**Formato do manual**  
- Incluir **screenshots** das telas principais com setas, círculos ou numeração indicando onde clicar.  
- Textos curtos e diretos (ex.: "1. Clique em **Agendar consulta**; 2. Selecione a data; 3. Clique em **Confirmar**.").  
- **Glossário de mensagens:** tabela com as principais mensagens de sucesso e erro (ex.: "E-mail já cadastrado", "Preencha o contato de emergência para agendar", "Horário indisponível").  
- **FAQ:** 3 a 5 perguntas frequentes (ex.: "Esqueci minha senha" — link de redefinição; "O botão não habilita" — verificar campos obrigatórios; "Vídeo não abre" — verificar permissões do navegador).  
- Usar linguagem simples ("sistema", "página", "área do médico/paciente"); evitar "backend", "endpoint", "API".  
- Padronizar tamanho/proporção dos prints e destacar botões em negrito ou entre aspas.




7. REFERÊNCIAS

BRASIL. Lei nº 13.709, de 14 de agosto de 2018. Lei Geral de Proteção de Dados Pessoais (LGPD). Disponível em: http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm. Acesso em: 11 mar. 2025.

LARAVEL. Laravel Documentation. 2024. Disponível em: https://laravel.com/docs. Acesso em: 11 mar. 2025.

MARTIN, Robert C. Código limpo: habilidades práticas do software Agile. Rio de Janeiro: Alta Books, 2011.

SOMMERVILLE, Ian. Engenharia de Software. 10. ed. São Paulo: Pearson, 2018.

VUE.JS. Vue.js - The Progressive JavaScript Framework. 2024. Disponível em: https://vuejs.org/. Acesso em: 11 mar. 2025.

INERTIA.JS. Inertia.js - The Modern Monolith. 2024. Disponível em: https://inertiajs.com/. Acesso em: 11 mar. 2025.

TAILWIND LABS. Tailwind CSS. 2024. Disponível em: https://tailwindcss.com/. Acesso em: 11 mar. 2025.

LARAVEL. Laravel Reverb - WebSockets for Laravel. 2024. Disponível em: https://reverb.laravel.com/. Acesso em: 11 mar. 2025.

PEERJS. PeerJS - Simple peer-to-peer with WebRTC. Disponível em: https://peerjs.com/. Acesso em: 11 mar. 2025.












8. APÊNDICE

O Apêndice deve incluir materiais complementares produzidos pelo autor: trechos de código representativos, scripts de migração ou seed, logs de testes quando necessário, e diagramas (PlantUML, Mermaid ou exportados de ferramentas como Draw.io). O código-fonte completo do projeto está disponível no repositório GitHub (TelemedicinaParaTodos); scripts de banco em `database/migrations/` e `database/seeders/`; documentação de diagramas em `docs/layers/persistence/database/diagrama_banco_dados.md` e em `docs/layers/architecture-governance/diagrams/`. Para entrega acadêmica, o aluno pode anexar ao documento os arquivos ou trechos mais relevantes (ex.: um Controller, um Service, uma migration e uma página Vue) em fonte monoespaçada tamanho 10, mantendo o restante no repositório. O Apêndice é de autoria do próprio aluno; anexos de terceiros (normas, artigos longos) devem constar como Anexo, não Apêndice.
