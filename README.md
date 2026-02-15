# Telemedicina para Todos 🧑‍⚕️📹

![GitHub repo size](https://img.shields.io/github/repo-size/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub language count](https://img.shields.io/github/languages/count/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub forks](https://img.shields.io/github/forks/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub issues](https://img.shields.io/github/issues/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub pull requests](https://img.shields.io/github/issues-pr/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)

## 📑 Sumário Navegável
- [📋 Sobre o Projeto](#telemedicina-para-todos-)
- [🎯 Idealização](#idealização-)
- [📚 Documentação Estrutural](#-documentação-estrutural)
- [🚀 Navegação Rápida](#-navegação-rápida)
- [💻 Tecnologias Utilizadas](#técnologias-utilizadas-)
- [👤 Autor](#autor-)
- [🛠️ Como Rodar o Projeto](#️-como-rodar-o-projeto)
- [📄 Licença](#licença-)

Esse projeto nasceu para resolver um problema simples: ninguém gosta de perder horas em filas de consultório. A ideia aqui é criar uma plataforma de telemedicina moderna, segura e acessível, onde médicos e pacientes podem se encontrar sem sair de casa.

Com ela, dá para agendar consultas de forma rápida, conversar por vídeo em tempo real e ainda ter prontuários digitais sempre à mão. Tudo isso num só lugar, sem complicação e com a praticidade que a tecnologia pode oferecer.

No fim das contas, é sobre facilitar a vida: menos correria, mais cuidado e saúde na tela do seu computador ou celular. 

## Idealização 🐊👍

Esse projeto nasceu da vontade de tornar a saúde mais próxima, prática e humana. Para entender melhor, imagine algumas pessoas que se beneficiariam do Telemedicina para Todos:

👩‍🦳 Dona Maria, 67 anos (Paciente)
Mora em uma cidade pequena e precisa de acompanhamento com um cardiologista que fica a 80 km de distância. Com a plataforma, ela pode realizar consultas sem sair de casa, economizando tempo, dinheiro e esforço com deslocamentos. Além disso, recebe notificações no celular lembrando o horário das consultas e tem sempre o histórico clínico organizado para mostrar ao médico.

👨‍💼 João, 35 anos (Paciente ocupado)
Trabalha em horário comercial e quase nunca consegue ir a um consultório sem faltar ao trabalho. Com a plataforma, ele agenda uma consulta online em horário flexível, participa de uma chamada de vídeo rápida e ainda recebe a prescrição digital direto no app. O que antes levava meio dia de ausência, agora pode ser resolvido em 20 minutos no intervalo do almoço.

👩‍⚕️ Dra. Ana, 42 anos (Médica)
É endocrinologista em um grande centro e atende dezenas de pacientes por semana. Com a agenda integrada do sistema, consegue reduzir faltas com lembretes automáticos, consultar rapidamente o histórico de cada paciente e até discutir casos com colegas médicos de forma segura. Isso otimiza seu tempo e melhora a qualidade do atendimento.

👨‍⚕️ Dr. Paulo, 29 anos (Recém-formado)
Está começando a construir sua base de pacientes. A plataforma lhe oferece uma forma acessível de se conectar a novos pacientes pela internet, com agenda organizada, prontuários digitais e até a possibilidade de prescrever receitas de forma online, dando mais credibilidade e praticidade para seu trabalho.

**Em resumo:**

• Pacientes ganham praticidade, acessibilidade e redução de tempo perdido.

• Médicos ganham eficiência, organização e uma forma moderna de se conectar aos seus pacientes.

> **A plataforma** não é só sobre tecnologia: é sobre quebrar barreiras e fazer a saúde chegar até onde antes era difícil.

## 🐴 👍 Documentação Estrutural

Esta seção reúne os documentos essenciais organizados em uma **estrutura hierárquica** para facilitar a navegação e compreensão do sistema. A documentação segue os princípios de **hiperdocumentação** com referenciamento cruzado contextual.

### 🗂️ Índice Central
- **[📋 Visão Geral](docs/index/VisaoGeral.md)** — **Documento mestre** com índice central e navegação guiada
- **[📊 Matriz de Rastreabilidade](docs/index/MatrizRequisitos.md)** — Mapeamento completo requisito → implementação
- **[📚 Glossário](docs/index/Glossario.md)** — Definições unificadas de termos técnicos e de negócio

### 📜 Requisitos e Regras de Negócio
- **[📋 Regras do Sistema](docs/requirements/SystemRules.md)** — Regras de negócio, compliance e segurança
- **[⚙️ Guia de Funcionalidades](docs/requirements/FuncionalitsGuide.md)** — Requisitos funcionais e casos de uso detalhados
- **[🔐 Lógica de Autenticação](docs/modules/auth/RegistrationLogic.md)** — Fluxos de registro e login

### 🏗️ Arquitetura e Padrões
- **[🏗️ Arquitetura do Sistema](docs/architecture/Arquitetura.md)** — Estrutura geral, camadas e padrões arquiteturais
- **[🎨 Guia do Frontend](docs/architecture/VueGuide.md)** — Convenções Vue.js e estrutura do frontend
- **[💻 Guia de Desenvolvimento](docs/architecture/DevGuide.md)** — Padrões de código e boas práticas

### 💾 Modelo de Dados
- **[🗄️ Diagrama do Banco de Dados](docs/database/diagrama_banco_dados.md)** — Estrutura das tabelas e relacionamentos
- **[📁 Migrações](../database/migrations/)** — Implementação das estruturas no banco

### ⚙️ Lógica de Domínio
- **[📅 Lógica de Consultas](docs/modules/appointments/AppointmentsLogica.md)** — Regras de agendamento e fluxos de negócio
- **[🔧 Implementação de Consultas](docs/modules/appointments/AppointmentsImplementationStudy.md)** — Detalhes técnicos e checklist
- **[📹 Implementação de Videochamadas](docs/modules/videocall/VideoCallImplementation.md)** — Sistema de vídeo em tempo real
- **[📋 Tarefas de Videochamadas](docs/modules/videocall/VideoCallTasks.md)** — Checklist de implementação

### 🔧 Configuração e Instalação
- **[🚀 Guia de Instalação](docs/setup/Start.md)** — Configuração do ambiente de desenvolvimento
- **[⚙️ Regras do Cursor](docs/setup/CursorRulesGuide.md)** — Configurações do ambiente de desenvolvimento
- **[📊 Diagramas](docs/diagrams/)** — Artefatos visuais (Draw.io/Mermaid) do projeto

### 🚀 Navegação Rápida

#### Por Papel do Usuário
- **👨‍⚕️ Médicos**: [Regras de Negócio](docs/requirements/SystemRules.md) → [Funcionalidades](docs/requirements/FuncionalitsGuide.md) → [Arquitetura](docs/architecture/Arquitetura.md)
- **👤 Pacientes**: [Visão Geral do Projeto](#idealização-) → [Funcionalidades](docs/requirements/FuncionalitsGuide.md) → [Regras](docs/requirements/SystemRules.md)
- **💻 Desenvolvedores**: [Arquitetura](docs/architecture/Arquitetura.md) → [Guia de Dev](docs/architecture/DevGuide.md) → [Implementações](docs/modules/appointments/AppointmentsImplementationStudy.md)

#### Por Tipo de Documentação
- **📋 Requisitos**: [SystemRules.md](docs/requirements/SystemRules.md) + [FuncionalitsGuide.md](docs/requirements/FuncionalitsGuide.md)
- **🏗️ Técnico**: [Arquitetura.md](docs/architecture/Arquitetura.md) + [VueGuide.md](docs/architecture/VueGuide.md)
- **💾 Dados**: [diagrama_banco_dados.md](docs/database/diagrama_banco_dados.md)
- **⚙️ Lógica**: [AppointmentsLogica.md](docs/modules/appointments/AppointmentsLogica.md) + [VideoCallImplementation.md](docs/modules/videocall/VideoCallImplementation.md)

---

## Técnologias Utilizadas 🥵 👌

### Backend

- **Laravel 12** - Framework PHP para desenvolvimento web
- **PHP 8.2+** - Linguagem de programação
- **Inertia.js** - Integração entre Laravel e Vue.js
- **MySQL/SQLite** - Banco de dados
- **Laravel Reverb** - Broadcasting em tempo real
- **Laravel Wayfinder** - Sistema de roteamento avançado

### Frontend

- **Vue.js 3** - Framework JavaScript progressivo
- **TypeScript** - Superset JavaScript com tipagem estática
- **Inertia.js Vue 3** - Adaptador Vue para Inertia
- **Tailwind CSS 4** - Framework CSS utilitário
- **Reka UI** - Biblioteca de componentes Vue
- **Lucide Vue** - Ícones vetoriais

### Ferramentas de Desenvolvimento

- **Vite** - Build tool e dev server
- **ESLint** - Linting de código
- **Prettier** - Formatação de código
- **PHPUnit** - Framework de testes PHP
- **Laravel Sail** - Ambiente Docker para desenvolvimento
- **Laravel Pint** - Formatação de código PHP

## Autor 🐵👍

Apenas EU.

<table>
    <a href="#" title="defina o título do link">
        <img src="https://i.postimg.cc/bN9MmsNB/1698243588646.jpg" width="150px;" alt="Foto de Audri Rian"/><br>
        <sub>
          <b>Audri Rian</b>
        </sub>
      </a>
</table>

## Como Começar 😡👍

### Para Novos Desenvolvedores
1. **Leia** o [README Principal](#telemedicina-para-todos-) para entender o projeto
2. **Configure** o ambiente com o [Guia de Instalação](docs/setup/Start.md)
3. **Estude** a [Arquitetura](docs/architecture/Arquitetura.md) do sistema
4. **Consulte** o [Glossário](docs/index/Glossario.md) para termos técnicos
5. **Explore** as implementações específicas conforme necessário

### Para Stakeholders
1. **Comece** com a [Visão Geral do Projeto](#idealização-)
2. **Entenda** as [Regras de Negócio](docs/requirements/SystemRules.md)
3. **Veja** as [Funcionalidades](docs/requirements/FuncionalitsGuide.md) disponíveis
4. **Consulte** a [Matriz de Requisitos](docs/index/MatrizRequisitos.md) para rastreabilidade

### Para Auditores
1. **Acesse** a [Matriz de Rastreabilidade](docs/index/MatrizRequisitos.md)
2. **Revise** as [Regras de Compliance](docs/requirements/SystemRules.md#segurança-e-compliance)
3. **Verifique** os [Testes](../tests/) implementados
4. **Analise** a [Documentação de Segurança](docs/requirements/SystemRules.md#segurança-e-compliance)

## 🛠️ Como Rodar o Projeto

Siga os passos detalhados no [Guia de Instalação](docs/setup/Start.md) para configurar o ambiente de desenvolvimento.

### Serviços necessários (backend)

| Serviço | Quando é necessário | Comando / observação |
|--------|----------------------|------------------------|
| **Redis** | Opcional: só se `CACHE_STORE=redis` ou `QUEUE_CONNECTION=redis` | Instalar e rodar Redis localmente |
| **Queue worker** | Necessário para jobs em background (lembretes, notificações, etc.) | `php artisan queue:work` (em terminal separado ou Supervisor em produção) |
| **Reverb** | Necessário para notificações e atualizações em tempo real (WebSocket) | `php artisan reverb:start` (em terminal separado) |
| **Scheduler** | Necessário para lembretes de consulta e rotinas agendadas | Em dev: `php artisan schedule:work`; em produção: cron com `php artisan schedule:run` |

### Comandos de setup

```bash
# Configuração inicial
cp .env.example .env
php artisan key:generate

# Banco de dados
php artisan migrate
php artisan db:seed

# Processos em background (cada um em um terminal, se for usar)
php artisan queue:work
php artisan schedule:work
php artisan reverb:start
```

### Checklist — Subiu local e está ok

- [ ] `cp .env.example .env` e variáveis mínimas preenchidas (APP_KEY com `php artisan key:generate`, DB_*, etc.)
- [ ] `composer install` e `npm install` executados sem erro
- [ ] `php artisan migrate` executou sem falha
- [ ] (Opcional) `php artisan db:seed` rodou
- [ ] `php artisan serve` sobe e a aplicação abre no navegador (ex.: http://localhost:8000)
- [ ] (Se usar Reverb) `php artisan reverb:start` está rodando e não há erro de WebSocket no console do front
- [ ] (Se usar filas) `php artisan queue:work` está rodando e jobs são processados
- [ ] (Opcional) Login/registro e uma consulta de teste funcionando

## Licença 📄

Este projeto está licenciado sob a Licença Apache 2.0 - veja o arquivo [LICENSE](LICENSE) para detalhes.

A Licença Apache 2.0 é uma licença de software permissiva que permite que outros desenvolvedores utilizem, modifiquem e distribuam seu código livremente, desde que mantenham a atribuição original e incluam uma cópia da licença. Esta licença também oferece proteção de patentes e é amplamente utilizada em projetos empresariais e open source.

