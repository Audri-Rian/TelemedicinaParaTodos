# Telemedicina para Todos 🧑‍⚕️📹

![GitHub repo size](https://img.shields.io/github/repo-size/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub language count](https://img.shields.io/github/languages/count/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub forks](https://img.shields.io/github/forks/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub issues](https://img.shields.io/github/issues/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)
![GitHub pull requests](https://img.shields.io/github/issues-pr/Audri-Rian/TelemedicinaParaTodos?style=for-the-badge)

Esse projeto tem como objetivo de criar uma platarforma de Telemedicina Moderna, segura e acessível desenvolvida com Laravel(PHP). Ele conecta médicos e pacientes de forma remota, oferecendo consultas online, agendamento inteligente, prontuários digitais e comunicação segura tudo em um único sistema integrado.

## Idealização 🐊👍

Os Pacientes podem:

Realizar consultas médicas sem sair de casa, reduzindo deslocamentos e tempo de espera. Estudos apontam que e-visits podem levar apenas 15 a 20 minutos comparado a 2 - 4 horas de consulta presencial, incluindo transporte e espera.

Já para os profissionais de saúde, a plataforma fornece:

Consultas em tempo real via videoconferência e chat seguro;
Agenda integrada com notificações automatizadas para reduzir faltas e otmizar tempo;
Gestão de histórico clínico, prescrições e possiblidades de interconsulta com outros médicos.

## Documentação Estrutural 🐴 👍

Esta seção reúne os documentos essenciais para compreender a arquitetura, regras de negócio e funcionamento interno do sistema. Inclui:

- **[Regras de negócio](docs/SystemRules.md)** — definição clara dos requisitos e fluxos operacionais da plataforma.
- **[Descrição da arquitetura lógica](diagramas/)** — diagramas e explicações sobre módulos, camadas e seus relacionamentos.
- **[Guia de instalação](docs/start.md)** — passo a passo para configurar o ambiente de desenvolvimento, instalação de dependências e execução local.
- **[Visão por módulo](docs/FuncionalitsGuide.md)** — descrição das responsabilidades e interações entre os módulos do sistema.
- **[Fluxo de comunicação entre componentes](docs/EstruturaArquivos.md)** — como serviços, controllers, eventos e repositórios se comunicam internamente.
- **[Modelos de dados e relacionamentos](docs/BancoDeDados)** — entidades principais, relações e estrutura de banco de dados.

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

## Como Rodar o Projeto. 😡👍

É so seguir os passos dessa documentação aqui patrão [Guia de instalação](docs/start.md)

## Licença 📄

Este projeto está licenciado sob a Licença Apache 2.0 - veja o arquivo [LICENSE](LICENSE) para detalhes.

A Licença Apache 2.0 é uma licença de software permissiva que permite que outros desenvolvedores utilizem, modifiquem e distribuam seu código livremente, desde que mantenham a atribuição original e incluam uma cópia da licença. Esta licença também oferece proteção de patentes e é amplamente utilizada em projetos empresariais e open source.

