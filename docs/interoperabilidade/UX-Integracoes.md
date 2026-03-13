# UX da feature de integrações

Este documento detalha a **experiência de uso** da própria feature de interoperabilidade: interface de gestão (hub), fluxo de conexão, logs e o **modelo mental** do usuário. Complementa a [Análise de propósito, UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md).

---

## 1. Por que a UX dessa feature importa

A maioria dos sistemas entrega apenas **API + documentação**. O usuário (administrador da clínica, suporte) precisa de **interface visual** para:

- Ver quais integrações existem e quais estão ativas
- Conectar e desconectar parceiros
- Entender falhas e sincronizações sem depender só de desenvolvedor

Sem essa interface, a feature vira “coisa de TI” e o suporte vira caos quando algo falha.

---

## 2. Hub de integrações

### 2.1 Navegação

```
Configurações
   ↓
Integrações
```

Uma tela dedicada, acessível a perfis com permissão de administração (ex.: gestor da clínica).

### 2.2 Conteúdo da tela principal

Agrupamento por tipo de parceiro:

| Categoria | Ação |
|-----------|------|
| Laboratórios | [Conectar] / listar conectados |
| Farmácias | [Conectar] / listar conectados |
| Convênios | [Conectar] / listar conectados |
| Hospitais | (futuro) |
| Outros sistemas médicos | (futuro) |

### 2.3 Cards de integração

Cada integração ativa pode ser exibida em card, por exemplo:

```
┌─────────────────────────────────────────┐
│ Laboratório Hermes                       │
│ Status: Conectado                        │
│ ✔ Receber pedidos de exame              │
│ ✔ Enviar resultados                     │
│ Última sincronização: hoje, 14:32       │
│                      [Gerenciar]         │
└─────────────────────────────────────────┘
```

Ao clicar em **Gerenciar**, o usuário acessa detalhes, logs e opção de desconectar.

---

## 3. Fluxo de conexão

O fluxo ideal assemelha-se a **OAuth** ou conexão autorizada:

1. **Clique em “Conectar”** (ex.: Conectar Laboratório Hermes)
2. **Autenticação** — usuário é direcionado ao parceiro (ou insere credenciais conforme modelo do parceiro)
3. **Escolha de permissões** — o que a integração pode fazer (ex.: enviar pedidos, receber resultados)
4. **Confirmação** — integração ativa; volta ao hub com status “Conectado”

Detalhes (redirect, token, escopos) dependem da decisão técnica (OAuth2, API key, etc.), mas a **UX** deve ser clara: poucos passos, mensagens de sucesso e de erro objetivas.

---

## 4. Logs de integração

Administradores e suporte precisam ver **o que aconteceu** na integração.

**Exemplo de lista de eventos:**

| Data/hora | Evento | Status |
|-----------|--------|--------|
| 07/03 14:32 | Resultado recebido — Hemograma, Paciente João | ✔ |
| 07/03 14:15 | Pedido enviado — Glicemia, Paciente Maria | ✔ |
| 07/03 12:00 | Chamada à API do laboratório | ⚠ Falha (timeout) |

**Sem logs:** quando algo falha, ninguém sabe se foi envio, recebimento ou parceiro; suporte vira inferno e confiança na feature cai.

**Com logs:** admin vê última sincronização, erros recentes e pode acionar o parceiro ou o suporte com contexto.

---

## 5. Modelo mental do usuário

**Ponto central:** o usuário **não** pensa “vou usar interoperabilidade”. Ele pensa em **resultados concretos**:

| O usuário pensa… | Não pensa… |
|------------------|------------|
| “Meus exames aparecem aqui” | “Vou usar a API de interoperabilidade” |
| “Minha receita chegou na farmácia” | “A integração com a farmácia está ativa” |
| “Meu plano foi validado” | “O convênio está conectado” |

Ou seja: **interoperabilidade é infraestrutura invisível**. O valor está nos **efeitos** (exames no prontuário, receita aceita, cobertura validada). A interface deve reforçar esses efeitos (mensagens, badges, timeline) e **não** expor jargão técnico (“sincronização FHIR”, “webhook”) a menos que seja em tela avançada para admin/TI.

---

## 6. Resumo de princípios

- **Hub visível:** Configurações → Integrações, com categorias (laboratório, farmácia, convênio) e cards por parceiro.
- **Conexão simples:** fluxo tipo OAuth (conectar → autenticar → permissões → ativo).
- **Logs obrigatórios:** eventos de envio/recebimento e falhas para admin e suporte.
- **Linguagem do usuário:** “Exames recebidos”, “Receita na farmácia”, “Plano validado” — não “interoperabilidade” ou “API” na interface do dia a dia.

---

*Última atualização: março/2025.*
