# Estrutura de Páginas — Interoperabilidade na visão do usuário

Este documento descreve **o que muda na experiência** do médico e do paciente com a interoperabilidade, e traduz isso em **páginas e funcionalidades** concretas no frontend.

---

## 1. Para o Médico

### 1.1 O que muda no dia a dia

**Hoje (sem interoperabilidade):**

```
Consulta → Solicita exame → Fala pro paciente "procure um lab"
→ Paciente faz o exame → Recebe PDF por email
→ Manda por WhatsApp ou faz upload no sistema
→ Na próxima consulta, médico abre o PDF manualmente
```

**Com interoperabilidade:**

```
Consulta → Solicita exame → Acabou
→ Sistema envia pedido automaticamente ao laboratório parceiro
→ Paciente vai ao lab, se identifica, lab já sabe o que fazer
→ Resultado aparece direto no prontuário
→ Médico recebe notificação: "Resultado do hemograma de João disponível"
```

O médico nunca mais pede para o paciente "trazer o exame depois". O resultado aparece no prontuário como se fosse mágica — com valores, faixas de referência, tudo separado. Se precisar do resultado agora, clica em **"Atualizar resultados"** e o sistema puxa direto do lab.

### 1.2 Páginas do médico

#### Página: Integrações (hub) — item novo na sidebar

É a "central de controle" das conexões da clínica. O médico (ou gestor) vê **com quem o sistema está conectado** e se está tudo funcionando.

**O que aparece:**

```
┌─────────────────────────────────────────────────┐
│  Integrações                                     │
│                                                  │
│  Laboratórios                                    │
│  ┌───────────────────────────────────────────┐   │
│  │ ● Laboratório Hermes          Conectado   │   │
│  │   Recebe pedidos · Envia resultados       │   │
│  │   Última sincronização: hoje, 14:32       │   │
│  │                         [Gerenciar]       │   │
│  └───────────────────────────────────────────┘   │
│                                                  │
│  ┌───────────────────────────────────────────┐   │
│  │   Farmácia Vida              Disponível   │   │
│  │                           [Conectar]      │   │
│  └───────────────────────────────────────────┘   │
│                                                  │
│  Convênios                                       │
│  ┌───────────────────────────────────────────┐   │
│  │   (Nenhum conectado)        [Conectar]    │   │
│  └───────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

**Funcionalidades:**
- Cards por parceiro com status claro (Conectado / Disponível / Erro)
- Capacidades em linguagem clínica ("Recebe pedidos", "Envia resultados"), não técnica
- Última sincronização visível — confiança de que está funcionando
- Botão **Conectar** para novos parceiros
- Botão **Gerenciar** para parceiros ativos

**Por que na sidebar principal:** o médico precisa saber se as integrações estão funcionando sem precisar entrar em Configurações. É operacional, não é "configuração de sistema".

---

#### Página: Gerenciar parceiro (detalhe)

Ao clicar em **Gerenciar** no card de um parceiro:

```
┌─────────────────────────────────────────────────┐
│  ← Laboratório Hermes                           │
│                                                  │
│  Status: ● Conectado                             │
│  Conectado em: 15/03/2026                        │
│                                                  │
│  Permissões                                      │
│  ✔ Receber pedidos de exame                     │
│  ✔ Enviar resultados automaticamente            │
│                                                  │
│  [Sincronizar agora]    [Pausar]  [Desconectar] │
│                                                  │
│  ─── Histórico recente ───                       │
│                                                  │
│  Hoje 14:32  Resultado recebido — Hemograma,    │
│              Paciente João                   ✔  │
│  Hoje 14:15  Pedido enviado — Glicemia,         │
│              Paciente Maria                  ✔  │
│  Hoje 12:00  Envio de pedido              ⚠ Falha│
│              Tentando novamente...               │
└─────────────────────────────────────────────────┘
```

**Funcionalidades:**
- Resumo do que a integração faz (permissões em linguagem humana)
- Botão **Sincronizar agora** — puxa resultados pendentes deste lab
- Histórico de eventos com status claro (sucesso / falha / tentando)
- Opção de pausar ou desconectar (controle de risco)

**Por que existe:** sem esta tela, quando algo falha ninguém sabe se foi envio, recebimento ou problema do parceiro. O suporte vira caos.

---

#### Fluxo: Conectar parceiro (wizard/modal)

Poucos passos, direto ao ponto:

```
Passo 1: Escolher parceiro
  "Qual laboratório deseja conectar?"
  → Laboratório Hermes    → Lab Fleury    → Outro

Passo 2: Autenticar
  "Insira as credenciais fornecidas pelo laboratório"
  → Chave de acesso: [________]

Passo 3: Permissões
  "O que esta integração poderá fazer?"
  ☑ Receber pedidos de exame
  ☑ Enviar resultados automaticamente

Passo 4: Confirmação
  ✔ "Laboratório Hermes conectado com sucesso!"
  → [Ir para Integrações]
```

**Por que existe:** sem um fluxo guiado, conectar parceiro exige desenvolvedor. Com o wizard, o próprio médico/gestor faz.

---

#### Dentro da consulta e do prontuário (telas existentes, sem novo item na sidebar)

Na **tela de exames do paciente** (dentro da consulta ou prontuário), adicionar:

```
Exames do paciente — João da Silva

┌──────────────────────────────────────────────────┐
│ Hemograma                                         │
│ Status: ● Resultado disponível                    │
│ Origem: Laboratório Hermes · Recebido 28/03      │
│ Hemoglobina: 14.2 g/dL (ref: 12.0-17.5) ✔       │
│ Glicemia: 95 mg/dL (ref: 70-99) ✔               │
├──────────────────────────────────────────────────┤
│ Colesterol total                                  │
│ Status: ◐ Aguardando resultado                   │
│ Enviado ao: Laboratório Hermes · 27/03           │
└──────────────────────────────────────────────────┘

                            [Atualizar resultados]
```

**Funcionalidades:**
- Resultados integrados aparecem com **origem** (qual lab, quando chegou)
- Exames aguardando resultado mostram **para onde** foi enviado
- Botão **Atualizar resultados** — pull sob demanda (quando o médico quer na hora)
- Feedback leve: spinner durante atualização, toast "2 novos resultados" ou "Nenhum novo resultado"

**Por que não é um item novo na sidebar:** o trabalho do médico é na consulta. Os resultados devem aparecer **onde ele já está**, não em outra página.

---

## 2. Para o Paciente

### 2.1 O que muda no dia a dia

**Hoje:**

```
Doutora pede exame → Paciente escolhe lab por conta própria
→ Faz o exame → Espera resultado por email
→ Baixa PDF → Manda pelo WhatsApp para a doutora
→ Se trocar de celular, perde tudo
```

**Com interoperabilidade:**

```
Doutora pede exame → Paciente recebe notificação:
  "Exame solicitado: Hemograma. Laboratório: Hermes"
→ Vai ao lab, se identifica, lab já sabe o que fazer
→ Faz o exame e vai embora
→ Resultado aparece automaticamente no sistema
→ Notificação: "Resultado do seu hemograma disponível"
→ Doutora já vê o resultado e pode chamar se algo estiver fora do normal
```

Zero burocracia. Sem PDF, sem WhatsApp, sem upload. O paciente pensa **"meus exames aparecem aqui"** — não precisa saber o que é integração.

### 2.2 Páginas do paciente

#### Página: Meus Exames — item novo na sidebar

Nome pensado para o paciente, não para TI. Nada de "Integrações" ou "Interoperabilidade".

```
┌─────────────────────────────────────────────────┐
│  Meus Exames                                     │
│                                                  │
│  ● Disponível                                    │
│  ┌───────────────────────────────────────────┐   │
│  │ Hemograma                                  │   │
│  │ Resultado disponível · 28/03/2026         │   │
│  │ Recebido do Laboratório Hermes            │   │
│  │                              [Ver detalhe]│   │
│  └───────────────────────────────────────────┘   │
│                                                  │
│  ◐ Em andamento                                  │
│  ┌───────────────────────────────────────────┐   │
│  │ Colesterol total                           │   │
│  │ Enviado ao laboratório · 27/03/2026       │   │
│  │ Aguardando resultado                       │   │
│  └───────────────────────────────────────────┘   │
│                                                  │
│  ─── Compartilhamento de dados ───               │
│  Você autorizou o Laboratório Hermes a receber   │
│  seus pedidos de exame. [Gerenciar permissões]   │
└─────────────────────────────────────────────────┘
```

**Funcionalidades:**
- Lista de exames com status em linguagem simples: "Resultado disponível", "Aguardando resultado", "Solicitado"
- Origem amigável: "Recebido do Laboratório Hermes" (não "inbound webhook FHIR DiagnosticReport")
- Detalhe do exame com valores e faixas de referência
- Seção de **consentimento/transparência**: o paciente vê o que autorizou e pode revogar

**Por que "Meus Exames" e não "Integrações":** o paciente não pensa "vou usar a interoperabilidade". Ele pensa "quero ver meus exames". A linguagem reflete o resultado, não a infraestrutura.

---

#### Detalhe do exame (ao clicar em "Ver detalhe")

```
┌─────────────────────────────────────────────────┐
│  ← Hemograma                                     │
│                                                  │
│  Status: Resultado disponível                     │
│  Solicitado por: Dra. Maria · 25/03/2026         │
│  Laboratório: Hermes · Resultado em 28/03/2026   │
│                                                  │
│  ─── Resultados ───                               │
│                                                  │
│  Hemoglobina         14.2 g/dL    (12.0-17.5) ✔ │
│  Glicemia em jejum   95 mg/dL     (70-99)     ✔ │
│  Leucócitos          7.500 /mm³   (4.000-11k) ✔ │
│                                                  │
│  📎 Laudo completo em PDF         [Baixar]       │
└─────────────────────────────────────────────────┘
```

**Por que existe:** o paciente quer ver o resultado dele de forma compreensível. Valores com faixas de referência e indicadores visuais (normal/alterado) dão autonomia sem substituir o médico.

---

#### Consentimento e permissões (dentro de "Meus Exames" ou área dedicada)

```
┌─────────────────────────────────────────────────┐
│  Compartilhamento de dados                       │
│                                                  │
│  Você autorizou os seguintes compartilhamentos:  │
│                                                  │
│  ✔ Laboratório Hermes                            │
│    Pode receber: pedidos de exame                │
│    Autorizado em: 15/03/2026                     │
│    [Revogar autorização]                         │
│                                                  │
│  ✔ Envio de dados à Rede Nacional de Saúde       │
│    Autorizado em: 10/01/2026                     │
│    [Revogar autorização]                         │
└─────────────────────────────────────────────────┘
```

**Por que existe:** LGPD exige que o paciente saiba com quem seus dados são compartilhados e possa revogar. Sem essa tela, o consentimento é invisível e o sistema não está em conformidade.

---

## 3. O que nenhum dos dois vê (mas funciona por trás)

| O que acontece | O que o usuário percebe |
|---------------|------------------------|
| Sistema fala FHIR R4 com o laboratório | "Meu exame foi enviado" |
| Lab cai fora do ar, pedido vai para fila de retry | "Pedido registrado, será enviado automaticamente" |
| Resultado chega duplicado, sistema ignora a cópia | Nada — resultado aparece uma vez só |
| Circuit breaker abre porque lab está instável | Médico: toast "Laboratório temporariamente indisponível" |
| Cron de 15 minutos puxa resultados novos | Resultado "aparece" no prontuário sem ninguém clicar |
| Consentimento do paciente é verificado antes de enviar | Tudo funciona normalmente (se não consentiu, não envia) |

**Princípio:** interoperabilidade é infraestrutura invisível. O valor está no que **desaparece** — o PDF, o WhatsApp, o "traz na próxima consulta", o upload manual.

---

## 4. Matriz página × perfil

| Página / funcionalidade | Médico | Paciente |
|-------------------------|--------|----------|
| **Hub "Integrações"** (parceiros, conectar, logs) | Sim — sidebar | Não |
| **Gerenciar parceiro** (detalhe, eventos, sync) | Sim | Não |
| **Conectar parceiro** (wizard) | Sim | Não |
| **Exames na consulta** (resultado + origem + botão atualizar) | Sim — tela existente | Não |
| **"Meus Exames"** (lista, detalhe, status amigável) | Não | Sim — sidebar |
| **Detalhe do exame** (valores, referências, PDF) | Sim — prontuário | Sim — "Meus Exames" |
| **Consentimento / permissões** | Indireto (política) | Sim — explícito na UI |
| **Notificações** (resultado chegou) | Sim | Sim |

---

## 5. Navegação sugerida na sidebar

### Sidebar do médico (AppSidebar — doctorNavItems)

```
Dashboard
Agenda
Pacientes
Consultas
Prontuário
Mensagens
Integrações        ← NOVO (ícone: Link2 ou Cable)
Configurações
```

### Sidebar do paciente (AppSidebar — patientNavItems)

```
Dashboard
Consultas
Meus Exames        ← NOVO (ícone: FlaskConical ou TestTube)
Prontuário
Mensagens
Configurações
```

---

## 6. Rotas sugeridas

| Rota | Página | Perfil |
|------|--------|--------|
| `/doctor/integrations` | Hub de integrações | Médico |
| `/doctor/integrations/:partnerId` | Gerenciar parceiro | Médico |
| `/doctor/integrations/connect` | Wizard de conexão | Médico |
| `/patient/exams` | Meus Exames (lista) | Paciente |
| `/patient/exams/:examId` | Detalhe do exame | Paciente |
| `/patient/consents` | Consentimentos / compartilhamento | Paciente |

Rotas existentes que ganham funcionalidades:
- `/doctor/consultations/:id` — aba de exames com origem e botão "Atualizar resultados"
- `/doctor/patients/:id/records` — prontuário com exames integrados

---

## 7. Documentos relacionados

- [UX-Integracoes.md](../docs/interoperabilidade/UX-Integracoes.md) — princípios de interface
- [MVP1.md](MVP1.md) — fluxos técnicos, webhook + sync, critérios de aceite
- [SchemaIntegracoes.md](SchemaIntegracoes.md) — dados que sustentam logs e mapeamentos
- [TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md](../docs/interoperabilidade/TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md) — personas e casos de uso

---

*Atualizado em: março/2026.*
