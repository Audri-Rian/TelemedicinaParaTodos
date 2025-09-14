# Guia Completo das Regras do Cursor

## O que é o Cursor?

O **Cursor** é um editor de código inteligente que usa IA para ajudar na programação. Ele pode sugerir código, corrigir erros e seguir regras específicas que você definir para seu projeto.

## Por que Criar Regras?

Imagine que você tem um projeto com Laravel + Vue.js. Sem regras, o Cursor pode:
- Sugerir código em inglês quando você quer português
- Criar arquivos desnecessários
- Não seguir os padrões do seu projeto
- Fazer mudanças muito grandes sem você pedir

**Com regras bem definidas, o Cursor se torna um assistente personalizado que entende exatamente como você quer que ele ajude.**

## Estrutura das Regras

### 📁 Pasta `.cursor/`
Todas as regras ficam dentro desta pasta na raiz do seu projeto:

```
.cursor/
├── rules.mdc          # Regras gerais
├── laravel.mdc        # Regras específicas para Laravel
├── vue.mdc            # Regras específicas para Vue.js
├── telemedicina.mdc   # Regras de negócio
├── quality.mdc        # Regras de qualidade
├── behavior.mdc       # Regras de comportamento
└── settings.json      # Configuração principal
```

## Tipos de Arquivos de Regras

### 📝 Arquivos `.mdc`
São arquivos de texto simples (como Markdown) onde você escreve suas regras. Exemplo:

```markdown
# Regras Laravel

## Controllers
- Use Resource Controllers quando possível
- Mantenha controllers enxutos (máximo 5 métodos)

description: Regras específicas para desenvolvimento Laravel
globs: ["app/**/*.php", "database/**/*.php"]
alwaysApply: false
---
```

### ⚙️ Arquivo `settings.json`
É o "cérebro" que organiza todas as regras e diz ao Cursor:
- Quais regras usar
- Quando aplicá-las
- Como organizá-las

## Como as Regras Funcionam

### 🔄 **Tipos de Aplicação:**

#### 1. **Always Apply** (`alwaysApply: true`)
- **O que é**: Regras que SEMPRE são aplicadas
- **Exemplo**: `quality.mdc` e `behavior.mdc`
- **Quando**: Em todo arquivo, sempre

#### 2. **Auto Attach** (`autoAttach`)
- **O que é**: Regras que são anexadas automaticamente
- **Exemplo**: `rules.mdc`, `quality.mdc`, `behavior.mdc`
- **Quando**: O Cursor as carrega automaticamente

#### 3. **Agent Requested** (`agentRequested`)
- **O que é**: Regras que são aplicadas quando solicitadas
- **Exemplo**: `laravel.mdc`, `vue.mdc`, `telemedicina.mdc`
- **Quando**: Quando você está editando arquivos específicos

### 🎯 **Escopo por Globs:**

Globs são padrões que definem onde cada regra se aplica:

```json
"globs": ["app/**/*.php", "database/**/*.php"]
```

- `app/**/*.php` = Todos os arquivos PHP dentro da pasta `app/`
- `resources/js/**/*.vue` = Todos os arquivos Vue dentro de `resources/js/`
- `**/*` = Todos os arquivos do projeto

## Exemplo Prático

### Cenário: Editando um Controller Laravel

1. **Você abre**: `app/Http/Controllers/UserController.php`
2. **O Cursor detecta**: É um arquivo PHP na pasta `app/`
3. **Aplica automaticamente**:
   - `behavior.mdc` (sempre)
   - `quality.mdc` (sempre)
   - `rules.mdc` (gerais)
4. **Aplica quando solicitado**:
   - `laravel.mdc` (porque é um arquivo PHP em `app/`)

### Resultado:
O Cursor agora "sabe" que deve:
- Usar português
- Seguir padrões Laravel
- Fazer mudanças mínimas
- Questionar antes de implementar
- Aplicar boas práticas de qualidade

## Como Criar Suas Próprias Regras

### Passo 1: Crie um arquivo `.mdc`
```markdown
# Minhas Regras

## Exemplo
- Faça isso
- Não faça aquilo

description: Descrição da regra
globs: ["**/*.js"]  # Aplica em todos os arquivos JavaScript
alwaysApply: false
---
```

### Passo 2: Adicione ao `settings.json`
```json
{
  "name": "Minhas Regras",
  "file": "minhas-regras.mdc",
  "description": "Descrição da regra",
  "globs": ["**/*.js"],
  "alwaysApply": false
}
```

### Passo 3: Escolha quando aplicar
- **`alwaysApply: true`** = Sempre
- **`autoAttach`** = Automaticamente
- **`agentRequested`** = Quando solicitado

## Regras Específicas do Seu Projeto

### 🏥 **Telemedicina** (`telemedicina.mdc`)
- Segurança HIPAA
- Validações específicas (CPF, CRM)
- Fluxos de negócio médicos

### 🎨 **Vue.js** (`vue.mdc`)
- Composition API
- TypeScript
- Tailwind CSS
- Inertia.js

### 🐘 **Laravel** (`laravel.mdc`)
- Controllers enxutos
- Eloquent ORM
- Migrations
- Testes

### ✨ **Qualidade** (`quality.mdc`)
- Código limpo
- Documentação
- Testes
- Segurança

### 🤖 **Comportamento** (`behavior.mdc`)
- Ser assistente, não criador
- Mudanças mínimas
- Questionar premissas
- Boas práticas

## Vantagens do Sistema

### ✅ **Para o Desenvolvedor:**
- Código consistente
- Menos erros
- Padrões mantidos
- Assistência personalizada

### ✅ **Para o Projeto:**
- Arquitetura consistente
- Manutenibilidade
- Qualidade garantida
- Documentação viva

### ✅ **Para a Equipe:**
- Todos seguem as mesmas regras
- Onboarding mais fácil
- Menos divergências de código
- Padrões claros

## Dicas de Uso

### 🎯 **Regras Focadas:**
- Cada arquivo deve ter um propósito específico
- Evite regras muito longas (máximo 500 linhas)
- Use exemplos concretos

### 🔧 **Manutenção:**
- Revise as regras periodicamente
- Atualize conforme o projeto evolui
- Teste se estão funcionando

### 📚 **Documentação:**
- Mantenha as regras atualizadas
- Documente decisões importantes
- Use comentários claros

## Resumo

O sistema de regras do Cursor transforma uma ferramenta genérica em um assistente personalizado que:

1. **Entende seu projeto** (Laravel + Vue.js + Telemedicina)
2. **Segue seus padrões** (português, convenções, qualidade)
3. **Respeita suas preferências** (mudanças mínimas, questionamento crítico)
4. **Aplica automaticamente** as regras apropriadas
5. **Mantém consistência** em todo o código

É como ter um programador júnior que conhece perfeitamente seu projeto e sempre segue as melhores práticas que você definiu!
