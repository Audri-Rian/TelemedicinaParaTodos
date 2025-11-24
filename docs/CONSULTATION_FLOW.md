# Fluxo de Consulta - Análise e Melhorias

## 🔴 Problema Atual

O médico precisa alternar entre duas páginas:
1. `/doctor/consultations` - Para videochamada
2. `/doctor/consultations/{id}` - Para preencher prontuário

**Isso não é ideal porque:**
- Médico precisa abrir nova aba/janela
- Perde contexto da videochamada
- Não pode preencher durante a consulta
- Experiência fragmentada

## ✅ Solução Proposta: Layout Integrado

### Opção 1: Sidebar com Formulário (Recomendado)

Durante a videochamada, adicionar um botão "Abrir Prontuário" que abre uma sidebar com o formulário:

```
┌─────────────────────────────────────────────────────────┐
│ HEADER: [Vídeo] [Prontuário] [Finalizar]              │
├─────────────────────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────────────────────────┐ │
│ │ VÍDEO        │  │  PRONTUÁRIO (Sidebar)            │ │
│ │              │  │  (Abre ao clicar no botão)        │ │
│ │ [Paciente]   │  │                                   │ │
│ │              │  │  - Queixa Principal              │ │
│ │ [Médico]     │  │  - Anamnese                      │ │
│ │              │  │  - Diagnóstico                   │ │
│ │              │  │  - Prescrição                    │ │
│ │              │  │  - Exames                        │ │
│ │              │  │  - Anotações                      │ │
│ └──────────────┘  └──────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Opção 2: Layout Dividido (Alternativa)

Tela dividida automaticamente quando consulta está em andamento:

```
┌─────────────────────────────────────────────────────────┐
│ HEADER: Informações da Consulta                        │
├─────────────────────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────────────────────────┐ │
│ │ VÍDEO        │  │  FORMULÁRIO                      │ │
│ │ (50%)        │  │  (50%)                            │ │
│ │              │  │                                   │ │
│ │ [Paciente]   │  │  - Queixa Principal              │ │
│ │              │  │  - Anamnese                      │ │
│ │ [Médico]     │  │  - Diagnóstico                   │ │
│ │              │  │  - Prescrição                    │ │
│ └──────────────┘  └──────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## 🎯 Recomendação: Opção 1 (Sidebar)

**Vantagens:**
- ✅ Médico escolhe quando abrir o formulário
- ✅ Vídeo sempre visível (não perde contexto)
- ✅ Pode minimizar/maximizar sidebar
- ✅ Funciona bem em diferentes tamanhos de tela
- ✅ Não sobrecarrega a interface

**Implementação:**
1. Adicionar botão "Abrir Prontuário" na barra superior da videochamada
2. Ao clicar, abre sidebar deslizante com formulário
3. Formulário carrega dados da consulta atual
4. Auto-save funciona normalmente
5. Pode fechar sidebar e continuar apenas com vídeo

## 📋 Fluxo Ideal

```
1. Médico inicia videochamada
   ↓
2. Durante a consulta, clica "Abrir Prontuário"
   ↓
3. Sidebar abre com formulário
   ↓
4. Médico preenche durante a consulta (vídeo continua visível)
   ↓
5. Auto-save salva automaticamente
   ↓
6. Ao finalizar chamada, pode finalizar consulta também
   ↓
7. Tudo salvo e sincronizado
```

## 🔄 Alternativa: Pós-Consulta

Se o médico preferir:
- Fazer a videochamada completa
- Depois abrir `/doctor/consultations/{id}` para preencher
- Isso também funciona, mas é menos eficiente

## 💡 Decisão

**Recomendo implementar a Opção 1 (Sidebar)** porque:
- Melhor UX durante a consulta
- Médico pode preencher em tempo real
- Não perde contexto da videochamada
- Flexível (pode usar ou não)

