# Visão Geral - Telemedicina para Todos 🧑‍⚕️📹

## 📋 Sobre Este Documento

Este é o **documento mestre** do projeto Telemedicina para Todos - um índice central que conecta toda a documentação do sistema. Aqui você encontrará uma visão geral do projeto, seus objetivos e links para todos os documentos detalhados.

### 📑 Sumário Navegável
- [📋 Sobre Este Documento](#-sobre-este-documento)
- [🎯 Audiência](#-audiência)
- [📖 Escopo](#-escopo)
- [🏥 O Que É o Projeto](#-o-que-é-o-projeto)
- [🗂️ Estrutura da Documentação](#️-estrutura-da-documentação)
- [🔗 Navegação Rápida](#-navegação-rápida)
- [🚀 Começando](#-começando)
- [📊 Status da Documentação](#-status-da-documentação)
- [🤝 Contribuindo com a Documentação](#-contribuindo-com-a-documentação)
- [📞 Suporte e Contato](#-suporte-e-contato)

### 🎯 Audiência
- **Desenvolvedores**: Encontrarão links diretos para documentação técnica
- **Stakeholders**: Terão visão geral dos requisitos e funcionalidades
- **Novos colaboradores**: Poderão navegar rapidamente pela estrutura do projeto
- **Auditores**: Terão acesso à matriz de rastreabilidade de requisitos

### 📖 Escopo
Este documento cobre toda a documentação do sistema, desde requisitos até implementação técnica. Ele **não** substitui a documentação específica de cada módulo.

---

## 🏥 O Que É o Projeto

O **Telemedicina para Todos** é uma plataforma moderna de telemedicina que conecta médicos e pacientes de forma remota, oferecendo:

- 📅 **Agendamento inteligente** de consultas
- 📹 **Consultas por vídeo** em tempo real
- 📋 **Prontuários digitais** organizados
- 🔒 **Comunicação segura** entre médico e paciente
- 📱 **Interface moderna** e acessível

### 💡 Problema que Resolve
- **Para Pacientes**: Elimina deslocamentos desnecessários, economiza tempo e dinheiro
- **Para Médicos**: Otimiza agenda, reduz faltas e melhora qualidade do atendimento
- **Para o Sistema**: Democratiza o acesso à saúde de qualidade

---

## 🗂️ Estrutura da Documentação

### 1. 📜 Requisitos e Regras de Negócio
- **[Regras do Sistema](../requirements/SystemRules.md)** - Regras de negócio, compliance e segurança
- **[Guia de Funcionalidades](../requirements/FuncionalitsGuide.md)** - Requisitos funcionais e casos de uso
- **[Matriz de Rastreabilidade](MatrizRequisitos.md)** - Mapeamento completo requisito → implementação

### 2. 🏗️ Arquitetura e Padrões
- **[Arquitetura do Sistema](../architecture/Arquitetura.md)** - Estrutura geral, camadas e padrões
- **[Guia do Frontend](../architecture/VueGuide.md)** - Convenções Vue.js e estrutura do frontend
- **[Guia de Desenvolvimento](../architecture/DevGuide.md)** - Padrões de código e boas práticas

### 3. 💾 Modelo de Dados
- **[Diagrama do Banco de Dados](../database/diagrama_banco_dados.md)** - Estrutura das tabelas e relacionamentos
- **[Migrações](../../database/migrations/)** - Implementação das estruturas no banco

### 4. ⚙️ Lógica de Domínio
- **[Lógica de Consultas](../modules/appointments/AppointmentsLogica.md)** - Regras de agendamento e fluxos
- **[Implementação de Consultas](../modules/appointments/AppointmentsImplementationStudy.md)** - Detalhes técnicos
- **[Lógica de Autenticação](../modules/auth/RegistrationLogic.md)** - Fluxos de registro e login
- **[Implementação de Videochamadas](../modules/videocall/VideoCallImplementation.md)** - Sistema de vídeo
- **[Tarefas de Videochamadas](../modules/videocall/VideoCallTasks.md)** - Checklist de implementação

### 5. 🔧 Configuração e Instalação
- **[Guia de Instalação](../setup/Start.md)** - Como configurar o ambiente de desenvolvimento
- **[Regras do Cursor](../setup/CursorRulesGuide.md)** - Configurações do ambiente de desenvolvimento

### 6. ☁️ Cloud e Escalabilidade
- **[Estratégia AWS Cloud](../aws/CloudScalabilityStrategy.md)** - Roadmap completo para migração e escalabilidade na nuvem

### 7. 📚 Referências
- **[Glossário](Glossario.md)** - Definições de termos técnicos e de negócio
- **[README Principal](../../README.md)** - Visão geral do projeto no GitHub

---

## 🔗 Navegação Rápida

### Por Papel do Usuário
- **👨‍⚕️ Médicos**: [Regras de Negócio](../requirements/SystemRules.md) → [Funcionalidades](../requirements/FuncionalitsGuide.md) → [Arquitetura](../architecture/Arquitetura.md)
- **👤 Pacientes**: [Visão Geral do Projeto](../../README.md) → [Funcionalidades](../requirements/FuncionalitsGuide.md) → [Regras](../requirements/SystemRules.md)
- **💻 Desenvolvedores**: [Arquitetura](../architecture/Arquitetura.md) → [Guia de Dev](../architecture/DevGuide.md) → [Implementações](../modules/appointments/AppointmentsImplementationStudy.md)
- **☁️ DevOps/Cloud**: [Estratégia AWS](../aws/CloudScalabilityStrategy.md) → [Arquitetura](../architecture/Arquitetura.md) → [Configuração](../setup/Start.md)

### Por Tipo de Documentação
- **📋 Requisitos**: [SystemRules.md](../requirements/SystemRules.md) + [FuncionalitsGuide.md](../requirements/FuncionalitsGuide.md)
- **🏗️ Técnico**: [Arquitetura.md](../architecture/Arquitetura.md) + [VueGuide.md](../architecture/VueGuide.md)
- **💾 Dados**: [diagrama_banco_dados.md](../database/diagrama_banco_dados.md)
- **⚙️ Lógica**: [AppointmentsLogica.md](../modules/appointments/AppointmentsLogica.md) + [VideoCallImplementation.md](../modules/videocall/VideoCallImplementation.md)
- **☁️ Cloud**: [CloudScalabilityStrategy.md](../aws/CloudScalabilityStrategy.md)

---

## 🚀 Começando

### Para Novos Desenvolvedores
1. Leia o [README Principal](../../README.md) para entender o projeto
2. Configure o ambiente com o [Guia de Instalação](../setup/Start.md)
3. Estude a [Arquitetura](../architecture/Arquitetura.md) do sistema
4. Consulte o [Glossário](Glossario.md) para termos técnicos
5. Explore as implementações específicas conforme necessário

### Para DevOps/Cloud Engineers
1. Comece com a [Estratégia AWS Cloud](../aws/CloudScalabilityStrategy.md)
2. Entenda a [Arquitetura](../architecture/Arquitetura.md) atual do sistema
3. Revise os [Requisitos](../requirements/SystemRules.md) de segurança e compliance
4. Configure o ambiente de desenvolvimento com o [Guia de Instalação](../setup/Start.md)

### Para Stakeholders
1. Comece com a [Visão Geral do Projeto](../../README.md)
2. Entenda as [Regras de Negócio](../requirements/SystemRules.md)
3. Veja as [Funcionalidades](../requirements/FuncionalitsGuide.md) disponíveis
4. Consulte a [Matriz de Requisitos](MatrizRequisitos.md) para rastreabilidade

### Para Auditores
1. Acesse a [Matriz de Rastreabilidade](MatrizRequisitos.md)
2. Revise as [Regras de Compliance](../requirements/SystemRules.md#segurança-e-compliance)
3. Verifique os [Testes](../../tests/) implementados
4. Analise a [Documentação de Segurança](../requirements/SystemRules.md#segurança-e-compliance)

---

## 📊 Status da Documentação

| Módulo | Status | Documentação |
|--------|--------|--------------|
| ✅ Autenticação | Completo | [RegistrationLogic.md](Auth/RegistrationLogic.md) |
| ✅ Consultas | Completo | [AppointmentsLogica.md](Appointments/AppointmentsLogica.md) |
| ✅ Videochamadas | Em Desenvolvimento | [VideoCallTasks.md](VideoCall/VideoCallTasks.md) |
| ✅ Arquitetura | Completo | [Arquitetura.md](Architecture/Arquitetura.md) |
| ✅ Cloud/AWS | Completo | [CloudScalabilityStrategy.md](../aws/CloudScalabilityStrategy.md) |
| 🔄 Prontuários | Planejado | *Em breve* |
| 🔄 Prescrições | Planejado | *Em breve* |

---

## 🤝 Contribuindo com a Documentação

### Como Atualizar
1. **Identifique** o documento que precisa de atualização
2. **Edite** o arquivo correspondente
3. **Atualize** este índice se necessário
4. **Teste** os links para garantir que funcionam
5. **Commit** com mensagem descritiva

### Padrões de Escrita
- Use **linguagem clara** e evite jargões excessivos
- Inclua **exemplos práticos** quando possível
- Mantenha **parágrafos curtos** para melhor legibilidade
- Use **links contextuais** em vez de "clique aqui"
- Atualize **referências cruzadas** quando modificar documentos

---

## 📞 Suporte e Contato

Para dúvidas sobre a documentação ou sugestões de melhoria:
- 📧 Abra uma **Issue** no GitHub
- 💬 Use as **Discussões** do repositório
- 📝 Faça um **Pull Request** com melhorias

---

*Última atualização: Dezembro 2024*
*Versão da documentação: 1.0*
