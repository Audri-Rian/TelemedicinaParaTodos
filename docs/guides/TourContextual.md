# Guia para Criar um Tour Contextual

Este documento serve como um guia completo para planejar e implementar um Tour Contextual na plataforma **Telemedicina Para Todos**, com foco na página Dashboard do Paciente.

---

## 1. Defina o Objetivo do Tour e o Público

### Objetivo Principal

O tour tem como objetivo **orientar novos pacientes** a entenderem rapidamente as funcionalidades essenciais da plataforma e realizarem sua primeira consulta médica online. Após completar o tour, o usuário deve ser capaz de:

- **Agendar sua primeira consulta** com um médico especialista
- **Navegar pelo dashboard** e encontrar informações importantes
- **Acessar funcionalidades principais** como histórico, prontuário e mensagens
- **Entender o fluxo de trabalho** da plataforma de telemedicina

### Público-Alvo

- **Usuários iniciantes**: Pacientes que acabaram de se cadastrar e estão acessando o dashboard pela primeira vez
- **Nível de familiaridade**: Assumimos que o usuário tem familiaridade básica com interfaces web modernas, mas pode não estar acostumado com plataformas de telemedicina
- **Contexto de uso**: O usuário está buscando agendar uma consulta médica online de forma rápida e prática

---

## 2. Mapeie os Pontos Chave (Onde e o Quê)

Com base na análise do Dashboard do Paciente (`resources/js/pages/Patient/Dashboard.vue`), identificamos **5 funcionalidades essenciais** que devem ser destacadas no tour:

| Passo | Localização na Página | Funcionalidade | Título do Passo |
|------|----------------------|----------------|-----------------|
| 1 | Botão "Agendar Nova Consulta" (linha 265-270) | Ação principal para iniciar agendamento | Pronto para Começar? |
| 2 | Seção "Médicos Disponíveis Agora" (linha 220-262) | Visualização rápida de médicos online | Médicos à Sua Disposição |
| 3 | Card "Próxima Consulta" (linha 274-330) | Acompanhamento de consultas agendadas | Sua Próxima Consulta |
| 4 | Cards de Acesso Rápido (linha 347-384) | Histórico, Receitas e Exames | Seus Documentos Médicos |
| 5 | Seção "Encontrar Médico" (linha 386-481) | Busca e filtros de médicos | Encontre o Médico Ideal |

### Justificativa da Seleção

- **Máximo de 5 passos**: Evita sobrecarga cognitiva e mantém o tour focado
- **Ordem lógica**: Começa com a ação principal (agendar) e progride para funcionalidades de acompanhamento
- **Cobertura essencial**: Abrange desde o agendamento até o acesso a documentos médicos

---

## 3. Escreva o Conteúdo (O Coração do Tour)

Para cada passo, a mensagem deve responder: **Onde estou**, **O que é isso** e, o mais importante, **Por que isso me importa**.

### Passo 1: Pronto para Começar?

**Título**: "Pronto para Começar?"

**Descrição**: 
> "Este é o botão principal para agendar sua primeira consulta médica online. Clique aqui para encontrar médicos disponíveis, escolher um horário e iniciar seu atendimento de saúde no conforto da sua casa. É rápido, seguro e você pode fazer isso agora mesmo!"

**Elemento a destacar**: Botão "Agendar Nova Consulta" com ícone de calendário

**Valor para o usuário**: 
- Ação direta e clara para o objetivo principal
- Reduz fricção no primeiro uso
- Estabelece confiança ao mostrar o caminho mais simples

---

### Passo 2: Médicos à Sua Disposição

**Título**: "Médicos à Sua Disposição"

**Descrição**:
> "Aqui você vê os médicos que estão disponíveis para consulta agora mesmo. Cada card mostra o nome e a especialidade do profissional. Clique em qualquer médico para agendar uma consulta rapidamente. Esta área é atualizada em tempo real, então você sempre verá quem está online."

**Elemento a destacar**: Grid de médicos disponíveis (seção "Médicos Disponíveis Agora")

**Valor para o usuário**:
- Acesso rápido a profissionais disponíveis
- Economiza tempo na busca
- Mostra que a plataforma está ativa e com opções

---

### Passo 3: Sua Próxima Consulta

**Título**: "Sua Próxima Consulta"

**Descrição**:
> "Este card mostra os detalhes da sua próxima consulta agendada: médico, data, horário e especialidade. Quando chegar o momento, você poderá entrar na videochamada diretamente daqui. Se ainda não tem consultas agendadas, este espaço ficará disponível para quando você agendar."

**Elemento a destacar**: Card lateral direito "Próxima Consulta" ou estado vazio correspondente

**Valor para o usuário**:
- Visibilidade clara do próximo compromisso
- Acesso rápido à videochamada
- Reduz ansiedade sobre quando será a consulta

---

### Passo 4: Seus Documentos Médicos

**Título**: "Seus Documentos Médicos"

**Descrição**:
> "Estes três cards dão acesso rápido ao seu histórico médico completo: consultas passadas, receitas prescritas e resultados de exames. Tudo fica organizado e acessível aqui no seu dashboard. Você pode revisar qualquer informação médica a qualquer momento."

**Elemento a destacar**: Grid de 3 cards (Histórico de Consultas, Receitas Prescritas, Laudos e Exames)

**Valor para o usuário**:
- Centralização de informações importantes
- Autonomia para acessar seu próprio histórico
- Facilita o acompanhamento contínuo da saúde

---

### Passo 5: Encontre o Médico Ideal

**Título**: "Encontre o Médico Ideal"

**Descrição**:
> "Use esta seção para buscar médicos por nome, especialidade ou convênio. Os filtros ajudam você a encontrar exatamente o profissional que precisa. Você pode rolar horizontalmente para ver mais opções e clicar no ícone de calendário para agendar diretamente."

**Elemento a destacar**: Seção completa "Encontrar Médico" com barra de busca e filtros

**Valor para o usuário**:
- Ferramenta poderosa de busca
- Personalização da escolha do médico
- Flexibilidade para diferentes necessidades

---

## 4. Welcome Screen: Primeira Impressão Amigável

### Por Que um Welcome Screen?

Antes de iniciar o tour, é recomendado apresentar um **Welcome Screen** (tela de boas-vindas) que cria uma primeira impressão positiva e dá **escolha ao usuário**, em vez de forçá-lo diretamente ao tour. Este padrão é amplamente recomendado por guias de onboarding de produtos modernos.

### Estrutura do Welcome Screen

**Quando aparecer**: Logo após o primeiro login bem-sucedido, antes de qualquer tour.

**Conteúdo sugerido**:

```
┌─────────────────────────────────────────┐
│  🎉 Bem-vindo ao Telemedicina Para     │
│     Todos!                              │
│                                         │
│  Estamos felizes em tê-lo conosco.     │
│  Vamos ajudá-lo a começar?             │
│                                         │
│  [Fazer Tour]  [Explorar por Conta]    │
└─────────────────────────────────────────┘
```

**Elementos do Welcome Screen**:

1. **Saudação calorosa e personalizada**
   - Usar o nome do usuário: "Olá, [Nome]! 👋"
   - Mensagem de boas-vindas breve e acolhedora

2. **Duas opções claras**:
   - **"Fazer Tour"**: Inicia o tour guiado completo
   - **"Explorar por Conta"**: Fecha o modal e permite exploração livre

3. **Design visual**:
   - Modal centralizado e não intrusivo
   - Cores alinhadas com a identidade visual
   - Ícone ou ilustração sutil (opcional)

4. **Comportamento**:
   - Não bloquear completamente a interface (overlay sutil)
   - Permitir fechar com ESC ou clique fora (opcional)
   - Salvar preferência se o usuário escolher "Explorar por Conta"

### Implementação do Welcome Screen

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
  showWelcome?: boolean;
  userName?: string;
}

const props = withDefaults(defineProps<Props>(), {
  showWelcome: false,
  userName: '',
});

const showModal = ref(props.showWelcome);

const startTour = () => {
  showModal.value = false;
  // Iniciar tour
  emit('start-tour');
};

const exploreFreely = async () => {
  showModal.value = false;
  // Marcar que usuário escolheu explorar
  await router.post('/onboarding/skip-welcome', {
    action: 'explore'
  });
};
</script>

<template>
  <Modal v-if="showModal" @close="exploreFreely">
    <div class="welcome-screen">
      <h2>🎉 Bem-vindo ao Telemedicina Para Todos!</h2>
      <p>Olá, {{ userName }}! Estamos felizes em tê-lo conosco.</p>
      <p>Vamos ajudá-lo a começar?</p>
      <div class="actions">
        <Button @click="startTour" variant="primary">
          Fazer Tour
        </Button>
        <Button @click="exploreFreely" variant="outline">
          Explorar por Conta
        </Button>
      </div>
    </div>
  </Modal>
</template>
```

### Vantagens do Welcome Screen

- ✅ **Dá controle ao usuário**: Não força o tour
- ✅ **Cria conexão emocional**: Primeira impressão positiva
- ✅ **Reduz ansiedade**: Usuário sabe que pode escolher
- ✅ **Aumenta engajamento**: Usuários que escolhem fazer o tour tendem a completá-lo mais

---

## 5. Determine o Gatilho e a Conclusão

### Gatilho: Quando o Tour Deve Começar?

**Recomendação Principal**: 
Após o **Welcome Screen**, se o usuário escolher "Fazer Tour", o tour deve iniciar imediatamente. Se escolher "Explorar por Conta", o tour não inicia automaticamente, mas permanece disponível via botão "Ver Tour" no header/sidebar.

**Condições de Ativação**:
1. ✅ Usuário autenticado como paciente
2. ✅ Primeira visita ao dashboard (verificar flag `has_seen_tour` no banco de dados)
3. ✅ Email verificado
4. ✅ Não iniciar se o usuário já completou o tour anteriormente

**Alternativas**:
- **Botão manual**: Adicionar um botão "Ver Tour" no header ou sidebar para usuários que queiram revisar
- **Reativação condicional**: Se houver mudanças significativas na interface, oferecer o tour novamente

**Implementação Sugerida**:
```typescript
// Verificar no backend (Controller)
if (!$user->has_seen_dashboard_tour && $user->email_verified_at) {
    // Iniciar tour
}
```

---

### Conclusão: O Que Acontece Quando o Tour Termina?

**Mensagem de Conclusão**:
> "🎉 Parabéns! Você concluiu o tour do dashboard. Agora você conhece as principais funcionalidades da plataforma. **Próximo passo**: Clique em 'Agendar Nova Consulta' para agendar sua primeira consulta médica online!"

**Ações Pós-Tour**:
1. **Marcar como completo**: Atualizar flag `has_seen_dashboard_tour = true` no banco de dados
2. **Call-to-Action direto**: Destacar o botão "Agendar Nova Consulta" com uma animação sutil
3. **Opção de feedback**: Mostrar um pequeno modal perguntando "O tour foi útil?" (opcional)
4. **Não mostrar novamente**: Salvar preferência para não exibir automaticamente

**Experiência de Fechamento**:
- Mostrar confetti ou animação de sucesso (opcional, mas melhora a experiência)
- Permitir fechar a mensagem facilmente
- Não bloquear a interface após o tour

---

## 6. Onboarding Contextual (Just-in-Time)

### O Que É Onboarding Contextual?

Nem todas as funcionalidades precisam estar no tour principal. O **onboarding contextual** (também conhecido como "just-in-time") fornece ajuda no momento exato em que o usuário precisa, através de tooltips, hotspots, pop-ups ou callouts que aparecem quando o usuário interage com áreas específicas pela primeira vez.

### Quando Usar Onboarding Contextual

Use para funcionalidades que são:
- **Secundárias**: Não críticas para a primeira ação
- **Avançadas**: Usadas depois que o usuário já está familiarizado
- **Contextuais**: Só fazem sentido em situações específicas
- **Opcionais**: Não essenciais para o fluxo principal

### Exemplos de Onboarding Contextual no Dashboard

#### 1. Tooltip no Histórico de Consultas

**Gatilho**: Primeira vez que o usuário passa o mouse sobre o card "Histórico de Consultas"

**Conteúdo**:
```
┌────────────────────────────────────┐
│ 📋 Histórico de Consultas          │
│                                    │
│ Aqui você verá todas as suas       │
│ consultas passadas, incluindo      │
│ receitas e exames anteriores.      │
│                                    │
│ [Fechar]                           │
└────────────────────────────────────┘
```

**Implementação**:
```vue
<Card 
  @mouseenter="showTooltip('history')"
  @mouseleave="hideTooltip('history')"
  data-tooltip-target="history"
>
  <Tooltip v-if="tooltips.history" position="top">
    Aqui você verá todas as suas consultas passadas...
  </Tooltip>
</Card>
```

#### 2. Hotspot na Seção de Lembretes

**Gatilho**: Primeira vez que o usuário visualiza a seção "Lembretes & Dicas de Saúde" quando ela está vazia

**Conteúdo**:
```
┌────────────────────────────────────┐
│ 💡 Dica                             │
│                                    │
│ Quando você tiver consultas com    │
│ prescrições ou exames agendados,   │
│ os lembretes aparecerão aqui       │
│ automaticamente.                   │
│                                    │
│ [Entendi]                           │
└────────────────────────────────────┘
```

#### 3. Callout na Busca de Médicos

**Gatilho**: Primeira vez que o usuário clica na barra de busca "Encontrar Médico"

**Conteúdo**:
```
┌────────────────────────────────────┐
│ 🔍 Dica de Busca                   │
│                                    │
│ Você pode buscar por:              │
│ • Nome do médico                   │
│ • Especialidade                    │
│ • Convênio                         │
│                                    │
│ Use os filtros para refinar sua    │
│ busca ainda mais!                  │
│                                    │
│ [Fechar] [Não mostrar novamente]   │
└────────────────────────────────────┘
```

### Estrutura de Dados para Tooltips Contextuais

```typescript
interface ContextualTooltip {
  id: string;
  target: string; // Seletor CSS ou ref
  trigger: 'hover' | 'click' | 'focus' | 'first-view';
  title?: string;
  description: string;
  position: 'top' | 'bottom' | 'left' | 'right';
  showOnce?: boolean; // Se true, só mostra uma vez
  dismissible?: boolean; // Se pode ser fechado
  action?: {
    label: string;
    onClick: () => void;
  };
}

// Exemplo de configuração
const contextualTooltips: ContextualTooltip[] = [
  {
    id: 'history-card',
    target: '[data-tooltip="history"]',
    trigger: 'hover',
    description: 'Aqui você verá todas as suas consultas passadas...',
    position: 'top',
    showOnce: false,
  },
  {
    id: 'reminders-section',
    target: '[data-tooltip="reminders"]',
    trigger: 'first-view',
    description: 'Quando você tiver consultas com prescrições...',
    position: 'bottom',
    showOnce: true,
  },
];
```

### Gerenciamento de Estado

```typescript
// Composable para gerenciar tooltips contextuais
export function useContextualTooltips() {
  const seenTooltips = ref<Set<string>>(new Set());
  
  const shouldShowTooltip = (tooltipId: string, showOnce: boolean) => {
    if (showOnce && seenTooltips.value.has(tooltipId)) {
      return false;
    }
    return true;
  };
  
  const markAsSeen = (tooltipId: string) => {
    seenTooltips.value.add(tooltipId);
    // Salvar no localStorage ou backend
    localStorage.setItem('seen_tooltips', JSON.stringify([...seenTooltips.value]));
  };
  
  return {
    shouldShowTooltip,
    markAsSeen,
  };
}
```

### Boas Práticas para Onboarding Contextual

- ✅ **Não sobrecarregue**: Máximo de 1-2 tooltips por página
- ✅ **Seja breve**: Tooltips devem ter no máximo 2-3 linhas
- ✅ **Respeite a escolha**: Sempre permita fechar ou "não mostrar novamente"
- ✅ **Timing correto**: Apareça no momento certo, não antes
- ✅ **Visual discreto**: Não bloqueie a interface

---

## 7. Onboarding Contínua e Progressiva

### Por Que Onboarding Contínua?

Um bom onboarding **não termina quando o tour inicial acaba**. Conforme o usuário explora áreas mais avançadas ou a plataforma adiciona novas funcionalidades, você deve introduzir onboarding adicional para manter o usuário engajado e informado.

### Estratégias de Onboarding Contínua

#### 1. Checklist Inicial (Getting Started)

Crie um painel de checklist com 3-4 ações recomendadas que o usuário deve completar nos primeiros dias.

**Exemplo de Checklist**:

```
┌─────────────────────────────────────┐
│ ✅ Começar                           │
│                                     │
│ ☐ Completar perfil (foto, telefone)│
│ ☐ Agendar primeira consulta        │
│ ☐ Configurar notificações          │
│ ☐ Ler instruções de uso            │
│                                     │
│ Progresso: 1/4 (25%)                │
└─────────────────────────────────────┘
```

**Implementação**:

```typescript
interface OnboardingTask {
  id: string;
  title: string;
  description: string;
  completed: boolean;
  actionUrl?: string;
  actionLabel?: string;
}

const onboardingTasks: OnboardingTask[] = [
  {
    id: 'complete-profile',
    title: 'Completar perfil',
    description: 'Adicione sua foto e telefone',
    completed: user.has_photo && user.has_phone,
    actionUrl: '/settings/profile',
    actionLabel: 'Completar perfil',
  },
  {
    id: 'first-appointment',
    title: 'Agendar primeira consulta',
    description: 'Encontre um médico e agende',
    completed: user.appointments_count > 0,
    actionUrl: '/patient/search-consultations',
    actionLabel: 'Agendar consulta',
  },
  // ... outros
];
```

#### 2. Tours Adicionais por Funcionalidade

Crie tours menores e específicos para áreas avançadas:

- **Tour de Prontuário**: Quando o usuário acessa pela primeira vez
- **Tour de Mensagens**: Quando recebe a primeira mensagem
- **Tour de Videochamada**: Antes da primeira consulta online
- **Tour de Configurações**: Quando acessa configurações pela primeira vez

**Estrutura**:

```typescript
interface FeatureTour {
  feature: string;
  trigger: 'first-access' | 'manual' | 'after-action';
  steps: TourStep[];
  optional: boolean; // Se pode ser pulado
}

const featureTours: FeatureTour[] = [
  {
    feature: 'medical-records',
    trigger: 'first-access',
    steps: [
      {
        title: 'Seu Prontuário Digital',
        description: 'Aqui estão todos os seus documentos médicos...',
        target: '[data-tour="medical-records"]',
      },
    ],
    optional: true,
  },
];
```

#### 3. Banners de Novas Funcionalidades

Quando novas funcionalidades são lançadas, mostre um banner discreto:

```
┌─────────────────────────────────────┐
│ 🆕 Novo! Agora você pode compartilhar│
│    receitas com farmácias           │
│    [Saber mais] [Fechar]            │
└─────────────────────────────────────┘
```

#### 4. Descobertas Espontâneas

Use badges ou indicadores visuais para destacar funcionalidades não exploradas:

- Badge "Novo" em itens do menu não visitados
- Indicador de notificação em áreas não exploradas
- Destaque sutil em botões importantes não utilizados

### Gerenciamento de Onboarding Progressivo

```typescript
// Sistema de níveis de onboarding
enum OnboardingLevel {
  WELCOME = 'welcome',
  DASHBOARD_TOUR = 'dashboard_tour',
  FIRST_APPOINTMENT = 'first_appointment',
  MEDICAL_RECORDS = 'medical_records',
  MESSAGES = 'messages',
  ADVANCED = 'advanced',
}

interface OnboardingProgress {
  level: OnboardingLevel;
  completed: boolean;
  completedAt?: Date;
  skipped?: boolean;
}

// Verificar progresso
const getNextOnboardingStep = (user: User): OnboardingLevel | null => {
  if (!user.onboarding_progress.welcome) return OnboardingLevel.WELCOME;
  if (!user.onboarding_progress.dashboard_tour) return OnboardingLevel.DASHBOARD_TOUR;
  if (!user.appointments_count && !user.onboarding_progress.first_appointment) {
    return OnboardingLevel.FIRST_APPOINTMENT;
  }
  // ... outros níveis
  return null;
};
```

---

## 8. Personalização Baseada no Perfil do Usuário

### Por Que Personalizar?

Um tour genérico pode parecer irrelevante se o usuário já completou algumas ações. A **personalização** adapta a experiência considerando o contexto e histórico do usuário, aumentando a percepção de relevância.

### Estratégias de Personalização

#### 1. Adaptar Conteúdo Baseado em Dados do Usuário

```typescript
interface PersonalizedTourConfig {
  skipSteps: string[]; // IDs dos passos a pular
  highlightSteps: string[]; // IDs dos passos a destacar
  customMessages: Record<string, string>; // Mensagens personalizadas
}

const getPersonalizedTour = (user: User): PersonalizedTourConfig => {
  const config: PersonalizedTourConfig = {
    skipSteps: [],
    highlightSteps: [],
    customMessages: {},
  };
  
  // Se já tem consulta agendada, destacar o passo de "Próxima Consulta"
  if (user.upcoming_appointments_count > 0) {
    config.highlightSteps.push('proxima-consulta');
    config.customMessages['proxima-consulta'] = 
      `Você já tem ${user.upcoming_appointments_count} consulta(s) agendada(s)! Veja os detalhes aqui.`;
  } else {
    // Se não tem consulta, destacar o passo de agendamento
    config.highlightSteps.push('agendar-consulta');
  }
  
  // Se perfil está completo, pular passo de perfil (se houver)
  if (user.profile_completed) {
    config.skipSteps.push('completar-perfil');
  }
  
  // Se já tem histórico, destacar passo de documentos
  if (user.has_medical_records) {
    config.highlightSteps.push('documentos-medicos');
    config.customMessages['documentos-medicos'] = 
      'Você já tem documentos médicos! Acesse-os aqui.';
  }
  
  return config;
};
```

#### 2. Ordem Dinâmica dos Passos

Reorganize os passos baseado na relevância:

```typescript
const getTourOrder = (user: User): string[] => {
  const baseOrder = [
    'agendar-consulta',
    'medicos-disponiveis',
    'proxima-consulta',
    'documentos-medicos',
    'encontrar-medico',
  ];
  
  // Se já tem consulta, mostrar "Próxima Consulta" primeiro
  if (user.upcoming_appointments_count > 0) {
    return [
      'proxima-consulta',
      'agendar-consulta',
      ...baseOrder.filter(id => id !== 'proxima-consulta'),
    ];
  }
  
  return baseOrder;
};
```

#### 3. Mensagens Contextuais

Adapte as mensagens baseado no estado do usuário:

```typescript
const getStepMessage = (stepId: string, user: User): string => {
  const baseMessages = {
    'agendar-consulta': 'Este é o botão principal para agendar sua primeira consulta...',
    // ... outros
  };
  
  // Personalização baseada em contexto
  if (stepId === 'agendar-consulta' && user.previous_appointments_count > 0) {
    return 'Agende sua próxima consulta aqui. Você já tem experiência com nossa plataforma!';
  }
  
  if (stepId === 'proxima-consulta' && user.upcoming_appointments_count === 0) {
    return 'Quando você agendar uma consulta, ela aparecerá aqui com todos os detalhes.';
  }
  
  return baseMessages[stepId] || '';
};
```

#### 4. Detecção de Comportamento

Use analytics para detectar padrões e personalizar:

```typescript
// Exemplo: Se usuário sempre busca por especialidade, destacar filtros
const getUserBehavior = async (userId: string) => {
  const analytics = await getUserAnalytics(userId);
  
  if (analytics.most_used_feature === 'specialty-filter') {
    return {
      highlight: 'encontrar-medico',
      message: 'Você costuma buscar por especialidade. Use os filtros aqui!',
    };
  }
  
  return null;
};
```

### Implementação no Backend

```php
// Controller
public function index()
{
    $user = auth()->user();
    
    // Obter configuração personalizada
    $tourConfig = $this->getPersonalizedTourConfig($user);
    
    return Inertia::render('Patient/Dashboard', [
        'showTour' => !$user->has_seen_dashboard_tour,
        'tourConfig' => $tourConfig,
        'user' => [
            'upcoming_appointments_count' => $user->appointments()->upcoming()->count(),
            'previous_appointments_count' => $user->appointments()->past()->count(),
            'has_medical_records' => $user->medicalRecords()->exists(),
            'profile_completed' => $user->isProfileComplete(),
        ],
    ]);
}

private function getPersonalizedTourConfig($user)
{
    return [
        'skip_steps' => $this->getStepsToSkip($user),
        'highlight_steps' => $this->getStepsToHighlight($user),
        'custom_messages' => $this->getCustomMessages($user),
    ];
}
```

---

## 9. Teste e Itere (Simulação)

### Checklist de Validação

Antes de implementar, simule mentalmente ou com um colega seguindo o tour:

#### ✅ Comprimento do Tour
- [ ] O tour tem 5 passos (ideal para não sobrecarregar)
- [ ] Cada passo leva menos de 30 segundos para ler
- [ ] O tour completo pode ser concluído em 2-3 minutos

#### ✅ Clareza das Mensagens
- [ ] Cada mensagem explica claramente "O que é isso"
- [ ] O valor para o usuário está explícito
- [ ] A linguagem é simples e sem jargões técnicos
- [ ] As instruções são orientadas à ação

#### ✅ Navegação e UX
- [ ] O usuário sabe como avançar para o próximo passo
- [ ] O usuário pode pular o tour se desejar
- [ ] O usuário pode voltar ao passo anterior
- [ ] Os elementos destacados são claramente visíveis

#### ✅ Valor Percebido
- [ ] O usuário entende por que cada funcionalidade é importante
- [ ] O tour leva naturalmente à primeira ação (agendar consulta)
- [ ] O usuário se sente mais confiante após o tour

### Métricas de Sucesso (Pós-Implementação)

#### Métricas Quantitativas

Após implementar, monitore:

1. **Taxa de conclusão**: % de usuários que completam o tour
2. **Taxa de agendamento pós-tour**: % de usuários que agendam consulta após o tour
3. **Tempo médio**: Tempo que usuários levam para completar
4. **Taxa de pulo**: % de usuários que pulam o tour (pode indicar que é muito longo)
5. **Taxa de engajamento com Welcome Screen**: % que escolhe "Fazer Tour" vs "Explorar"
6. **Taxa de interação com tooltips contextuais**: Quantos tooltips são visualizados
7. **Taxa de conclusão de checklist**: % que completa todas as tarefas iniciais
8. **Tempo até primeira ação**: Quanto tempo leva para agendar primeira consulta

#### Métricas Qualitativas

Além das métricas numéricas, colete feedback qualitativo:

1. **Pesquisa de satisfação pós-tour**:
   ```
   "O tour foi útil?"
   [ ] Muito útil
   [ ] Útil
   [ ] Pouco útil
   [ ] Não foi útil
   
   "O que você achou mais útil?"
   [Campo de texto livre]
   
   "O que poderia ser melhorado?"
   [Campo de texto livre]
   ```

2. **Análise de pontos de fricção**:
   - Em qual passo mais usuários pulam?
   - Quais tooltips são mais fechados sem leitura?
   - Quais áreas geram mais dúvidas?

3. **Entrevistas com usuários**:
   - Realize entrevistas com 5-10 usuários após completarem o tour
   - Pergunte sobre clareza, utilidade e sugestões

#### Sistema de Coleta de Feedback

```typescript
interface TourFeedback {
  tourId: string;
  completed: boolean;
  skipped: boolean;
  timeSpent: number; // em segundos
  stepsCompleted: number;
  totalSteps: number;
  rating?: number; // 1-5
  comments?: string;
  helpfulSteps?: string[];
  confusingSteps?: string[];
}

// Componente de feedback
const submitFeedback = async (feedback: TourFeedback) => {
  await router.post('/onboarding/feedback', feedback);
};
```

**Implementação do Modal de Feedback**:

```vue
<template>
  <Modal v-if="showFeedback" @close="skipFeedback">
    <div class="feedback-modal">
      <h3>O tour foi útil?</h3>
      <div class="rating">
        <button 
          v-for="i in 5" 
          :key="i"
          @click="setRating(i)"
          :class="{ active: rating >= i }"
        >
          ⭐
        </button>
      </div>
      <textarea 
        v-model="comments"
        placeholder="O que você achou mais útil? O que poderia ser melhorado?"
      />
      <div class="actions">
        <Button @click="submitFeedback">Enviar</Button>
        <Button @click="skipFeedback" variant="ghost">Pular</Button>
      </div>
    </div>
  </Modal>
</template>
```

#### Dashboard de Métricas

Crie um dashboard interno para visualizar as métricas:

```typescript
interface OnboardingMetrics {
  totalUsers: number;
  welcomeScreenShown: number;
  tourStarted: number;
  tourCompleted: number;
  tourSkipped: number;
  averageCompletionTime: number;
  postTourAppointmentRate: number;
  feedbackRating: number;
  mostHelpfulStep: string;
  mostSkippedStep: string;
}
```

---

### Monitoramento em Tempo Real

Implemente eventos de analytics para rastrear:

```typescript
// Eventos a rastrear
const trackOnboardingEvent = (event: string, data?: any) => {
  // Usar serviço de analytics (Google Analytics, Mixpanel, etc.)
  analytics.track(event, {
    userId: user.id,
    timestamp: new Date(),
    ...data,
  });
};

// Exemplos de eventos
trackOnboardingEvent('welcome_screen_shown');
trackOnboardingEvent('welcome_tour_selected');
trackOnboardingEvent('welcome_explore_selected');
trackOnboardingEvent('tour_step_viewed', { step: 1, stepId: 'agendar-consulta' });
trackOnboardingEvent('tour_step_skipped', { step: 2 });
trackOnboardingEvent('tour_completed', { timeSpent: 120 });
trackOnboardingEvent('tooltip_viewed', { tooltipId: 'history-card' });
trackOnboardingEvent('checklist_task_completed', { taskId: 'complete-profile' });
```

### Iterações Sugeridas

Com base nos dados coletados:

- **Se muitos pularem**: Reduzir para 3-4 passos ou tornar mais interativo
- **Se poucos agendarem**: Adicionar call-to-action mais forte no final
- **Se demorar muito**: Simplificar as mensagens ou dividir em tours menores
- **Se houver dúvidas**: Adicionar tooltips ou links para ajuda

---

## 10. Elementos Visuais e Micro-Interações (O "Momento WOW")

### Por Que Micro-Interações Importam?

Pequenas animações, transições suaves e feedback visual criam uma **"primeira sensação de valor"** e geram emoção positiva. Isso é especialmente importante quando o usuário completa ações importantes, como agendar a primeira consulta ou completar o perfil.

### Elementos Visuais Recomendados

#### 1. Animações de Entrada

**Welcome Screen**:
- Fade-in suave (300ms)
- Leve escala (scale 0.95 → 1.0)
- Efeito de "bounce" sutil no botão principal

**Tour Steps**:
- Slide-in do tooltip (da direção apropriada)
- Highlight pulsante no elemento alvo
- Overlay com fade-in gradual

#### 2. Confetti e Celebração

**Quando mostrar**:
- Ao completar o tour
- Ao agendar primeira consulta
- Ao completar checklist inicial
- Ao receber primeira receita

**Implementação**:

```vue
<script setup lang="ts">
import confetti from 'canvas-confetti';

const celebrate = () => {
  confetti({
    particleCount: 100,
    spread: 70,
    origin: { y: 0.6 }
  });
};
</script>
```

#### 3. Destaque Visual em Botões de Ação

**Pós-Tour**: Destacar o botão "Agendar Nova Consulta" com:
- Pulso sutil e contínuo
- Borda animada
- Badge "Comece aqui"

```css
@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(var(--primary), 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(var(--primary), 0);
  }
}

.cta-button {
  animation: pulse 2s infinite;
  position: relative;
}

.cta-button::after {
  content: '✨';
  position: absolute;
  top: -10px;
  right: -10px;
  animation: bounce 1s infinite;
}
```

#### 4. Progresso Visual

**Barra de Progresso no Tour**:
```
┌─────────────────────────────────────┐
│ Passo 2 de 5                        │
│ ████████░░░░░░░░░░ 40%              │
└─────────────────────────────────────┘
```

**Checklist com Animações**:
- Checkmark animado ao completar tarefa
- Progresso visual (barra ou círculo)
- Feedback sonoro sutil (opcional)

#### 5. Transições Suaves

**Entre Passos do Tour**:
- Fade-out do passo anterior (200ms)
- Fade-in do próximo passo (200ms)
- Scroll suave até o próximo elemento

**Tooltips Contextuais**:
- Aparecer com slide-in suave
- Desaparecer com fade-out
- Não usar animações bruscas

#### 6. Micro-Feedback

**Ao Interagir**:
- Hover: Leve elevação do card (shadow)
- Click: Ripple effect sutil
- Loading: Skeleton ou spinner elegante
- Sucesso: Checkmark animado

**Exemplo de Ripple Effect**:

```vue
<template>
  <button @click="handleClick" class="ripple-button">
    Agendar Consulta
  </button>
</template>

<script setup lang="ts">
const handleClick = (e: MouseEvent) => {
  const button = e.currentTarget as HTMLElement;
  const ripple = document.createElement('span');
  const rect = button.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x = e.clientX - rect.left - size / 2;
  const y = e.clientY - rect.top - size / 2;
  
  ripple.style.width = ripple.style.height = `${size}px`;
  ripple.style.left = `${x}px`;
  ripple.style.top = `${y}px`;
  ripple.classList.add('ripple');
  
  button.appendChild(ripple);
  
  setTimeout(() => ripple.remove(), 600);
};
</script>

<style scoped>
.ripple-button {
  position: relative;
  overflow: hidden;
}

.ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.6);
  transform: scale(0);
  animation: ripple-animation 0.6s ease-out;
  pointer-events: none;
}

@keyframes ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}
</style>
```

#### 7. Ilustrações e Ícones Animados

**Welcome Screen**:
- Ilustração animada (Lottie) de boas-vindas
- Ícones com micro-animações

**Estados Vazios**:
- Ilustrações animadas em EmptyStates
- Ícones que "respirem" sutilmente

#### 8. Cores e Gradientes Dinâmicos

**Destaque Progressivo**:
- Cores mais vibrantes em elementos importantes
- Gradientes sutis em cards de destaque
- Transição de cores ao completar ações

### Biblioteca de Animações Recomendada

Para Vue 3, considere:

- **@vueuse/motion**: Animações baseadas em movimento
- **GSAP**: Biblioteca poderosa de animações
- **Framer Motion** (se usar React): Alternativa popular
- **Lottie**: Para animações complexas

**Exemplo com @vueuse/motion**:

```vue
<script setup lang="ts">
import { useMotion } from '@vueuse/motion';

const target = ref<HTMLElement>();

useMotion(target, {
  initial: { scale: 0.9, opacity: 0 },
  enter: { scale: 1, opacity: 1, transition: { duration: 300 } },
});
</script>

<template>
  <div ref="target">Conteúdo animado</div>
</template>
```

### Boas Práticas para Micro-Interações

- ✅ **Sutilidade**: Animações devem ser suaves, não chamativas
- ✅ **Performance**: Use `transform` e `opacity` (GPU-accelerated)
- ✅ **Acessibilidade**: Respeite `prefers-reduced-motion`
- ✅ **Propósito**: Cada animação deve ter um propósito claro
- ✅ **Consistência**: Mantenha padrões visuais consistentes

**Respeitando Preferências de Acessibilidade**:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## 11. Considerações Técnicas de Implementação

### Bibliotecas Recomendadas

Para implementar o tour, considere usar:

- **Shepherd.js**: Biblioteca popular e bem documentada para tours guiados
- **Intro.js**: Alternativa leve e fácil de usar
- **Vue Tour**: Específica para Vue.js (recomendado para este projeto)

### Estrutura de Dados

```typescript
interface TourStep {
  id: string;
  title: string;
  description: string;
  target: string; // Seletor CSS ou ref do elemento
  position: 'top' | 'bottom' | 'left' | 'right';
  action?: {
    label: string;
    onClick: () => void;
  };
}

interface TourConfig {
  steps: TourStep[];
  showProgress: boolean;
  allowSkip: boolean;
  highlightTarget: boolean;
}
```

### Integração com Backend

```php
// Migration para adicionar flag
Schema::table('users', function (Blueprint $table) {
    $table->boolean('has_seen_dashboard_tour')->default(false);
});

// Controller
public function index()
{
    $user = auth()->user();
    $showTour = !$user->has_seen_dashboard_tour && $user->email_verified_at;
    
    return Inertia::render('Patient/Dashboard', [
        'showTour' => $showTour,
        // ... outros dados
    ]);
}

// Endpoint para marcar como visto
Route::post('/tour/completed', function () {
    auth()->user()->update(['has_seen_dashboard_tour' => true]);
    return response()->json(['success' => true]);
});
```

---

## 12. Acessibilidade e Responsividade

### Por Que Acessibilidade é Crítica?

Em uma plataforma de saúde, a **diversidade de usuários é alta**. Pessoas com diferentes necessidades de acessibilidade devem conseguir usar o tour e toda a plataforma de forma eficaz. Isso não é apenas uma boa prática — é uma responsabilidade ética e legal.

### Requisitos de Acessibilidade

#### 1. Navegação por Teclado

O tour deve ser totalmente navegável usando apenas o teclado:

- ✅ **Tab**: Navegar entre elementos interativos
- ✅ **Enter/Space**: Ativar botões
- ✅ **ESC**: Fechar modais/tooltips
- ✅ **Setas**: Navegar entre passos (se aplicável)
- ✅ **Focus visível**: Indicador de foco claro em todos os elementos

**Implementação**:

```vue
<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    closeTour();
  }
  if (e.key === 'ArrowRight') {
    nextStep();
  }
  if (e.key === 'ArrowLeft') {
    previousStep();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>
```

#### 2. Compatibilidade com Leitores de Tela

**ARIA Labels e Roles**:

```vue
<template>
  <div
    role="dialog"
    aria-labelledby="tour-title"
    aria-describedby="tour-description"
    aria-modal="true"
  >
    <h2 id="tour-title">{{ currentStep.title }}</h2>
    <p id="tour-description">{{ currentStep.description }}</p>
    
    <button
      aria-label="Próximo passo do tour"
      @click="nextStep"
    >
      Próximo
    </button>
    
    <button
      aria-label="Fechar tour"
      @click="closeTour"
    >
      Fechar
    </button>
  </div>
</template>
```

**Anúncios Dinâmicos**:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue';

const liveRegion = ref<HTMLElement>();

watch(currentStep, (newStep) => {
  if (liveRegion.value) {
    liveRegion.value.textContent = 
      `Passo ${currentStepIndex.value + 1} de ${totalSteps.value}: ${newStep.title}`;
  }
});
</script>

<template>
  <div
    ref="liveRegion"
    role="status"
    aria-live="polite"
    aria-atomic="true"
    class="sr-only"
  />
</template>

<style>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
```

#### 3. Contraste de Cores

- ✅ **Contraste mínimo**: 4.5:1 para texto normal, 3:1 para texto grande
- ✅ **Não depender apenas de cor**: Use ícones, texto ou padrões além de cor
- ✅ **Modo escuro**: Garantir contraste adequado em ambos os temas

#### 4. Tamanho de Fonte e Zoom

- ✅ **Fonte legível**: Mínimo de 16px para corpo de texto
- ✅ **Zoom funcional**: Interface deve funcionar até 200% de zoom
- ✅ **Escalável**: Usar unidades relativas (rem, em) em vez de px fixos

#### 5. Respeitar Preferências do Usuário

```css
/* Reduzir animações para usuários que preferem */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}

/* Modo de alto contraste */
@media (prefers-contrast: high) {
  .tour-overlay {
    border: 2px solid;
  }
}
```

### Responsividade

#### 1. Breakpoints

O tour deve funcionar bem em todos os tamanhos de tela:

```typescript
const breakpoints = {
  mobile: '(max-width: 640px)',
  tablet: '(min-width: 641px) and (max-width: 1024px)',
  desktop: '(min-width: 1025px)',
};
```

#### 2. Adaptação para Mobile

**Mudanças necessárias em mobile**:

- Tooltips em posição inferior (não bloqueiam conteúdo)
- Botões maiores (mínimo 44x44px para toque)
- Texto mais conciso
- Scroll automático para elemento destacado
- Gestos de swipe para navegar entre passos

**Implementação**:

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { useMediaQuery } from '@vueuse/core';

const isMobile = useMediaQuery('(max-width: 640px)');

const tooltipPosition = computed(() => {
  return isMobile.value ? 'bottom' : 'auto';
});

const buttonSize = computed(() => {
  return isMobile.value ? 'lg' : 'md';
});
</script>
```

#### 3. Posicionamento Adaptativo

```typescript
const getTooltipPosition = (
  target: HTMLElement,
  viewport: { width: number; height: number }
): 'top' | 'bottom' | 'left' | 'right' => {
  const rect = target.getBoundingClientRect();
  const space = {
    top: rect.top,
    bottom: viewport.height - rect.bottom,
    left: rect.left,
    right: viewport.width - rect.right,
  };
  
  // Escolher posição com mais espaço
  const maxSpace = Math.max(...Object.values(space));
  
  if (maxSpace === space.top) return 'top';
  if (maxSpace === space.bottom) return 'bottom';
  if (maxSpace === space.left) return 'left';
  return 'right';
};
```

#### 4. Touch Targets

Em dispositivos móveis, garantir que todos os elementos interativos tenham:

- **Tamanho mínimo**: 44x44px (recomendação WCAG)
- **Espaçamento**: Mínimo de 8px entre elementos clicáveis
- **Feedback visual**: Indicar claramente quando um elemento é tocado

### Testes de Acessibilidade

#### Checklist de Testes

- [ ] Navegação completa por teclado
- [ ] Compatibilidade com leitores de tela (NVDA, JAWS, VoiceOver)
- [ ] Contraste de cores adequado (ferramenta: WebAIM Contrast Checker)
- [ ] Funcionalidade em zoom 200%
- [ ] Funcionalidade em diferentes tamanhos de tela
- [ ] Respeito a `prefers-reduced-motion`
- [ ] Foco visível em todos os elementos
- [ ] Textos alternativos em imagens/ícones

#### Ferramentas de Teste

- **axe DevTools**: Extensão do navegador
- **WAVE**: Avaliador de acessibilidade web
- **Lighthouse**: Auditoria de acessibilidade
- **Screen Readers**: Testar com NVDA (Windows), JAWS, VoiceOver (Mac/iOS)

### Implementação de Acessibilidade no Tour

```vue
<template>
  <Teleport to="body">
    <div
      v-if="isActive"
      role="dialog"
      aria-labelledby="tour-title"
      aria-describedby="tour-description"
      aria-modal="true"
      class="tour-overlay"
      @keydown.esc="closeTour"
    >
      <!-- Overlay escuro com foco no elemento -->
      <div
        class="tour-backdrop"
        @click="closeTour"
        aria-hidden="true"
      />
      
      <!-- Tooltip do tour -->
      <div
        class="tour-tooltip"
        :style="tooltipStyle"
        role="region"
      >
        <h3 id="tour-title" class="tour-title">
          {{ currentStep.title }}
        </h3>
        <p id="tour-description" class="tour-description">
          {{ currentStep.description }}
        </p>
        
        <!-- Navegação -->
        <div class="tour-navigation">
          <button
            v-if="canGoBack"
            @click="previousStep"
            aria-label="Passo anterior"
          >
            Anterior
          </button>
          
          <span class="tour-progress" aria-live="polite">
            Passo {{ currentStepIndex + 1 }} de {{ totalSteps }}
          </span>
          
          <button
            v-if="!isLastStep"
            @click="nextStep"
            aria-label="Próximo passo"
          >
            Próximo
          </button>
          
          <button
            v-else
            @click="completeTour"
            aria-label="Concluir tour"
          >
            Concluir
          </button>
          
          <button
            @click="closeTour"
            aria-label="Fechar tour"
            class="tour-close"
          >
            <span aria-hidden="true">×</span>
            <span class="sr-only">Fechar</span>
          </button>
        </div>
      </div>
      
      <!-- Região de anúncio para leitores de tela -->
      <div
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="sr-only"
      >
        {{ screenReaderAnnouncement }}
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';

const screenReaderAnnouncement = computed(() => {
  return `Passo ${currentStepIndex.value + 1} de ${totalSteps.value}: ${currentStep.value.title}. ${currentStep.value.description}`;
});

// Atualizar anúncio quando passo mudar
watch(currentStep, () => {
  // Forçar atualização da região live
  setTimeout(() => {
    // Trigger re-render
  }, 100);
});
</script>
```

---

## 13. Exemplo de Implementação Vue 3

### Componente de Tour

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
  showTour?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showTour: false,
});

const currentStep = ref(0);
const isActive = ref(props.showTour);

const tourSteps = [
  {
    id: 'agendar-consulta',
    title: 'Pronto para Começar?',
    description: 'Este é o botão principal para agendar sua primeira consulta médica online...',
    target: '[data-tour="agendar-consulta"]',
    position: 'bottom',
  },
  // ... outros passos
];

const nextStep = () => {
  if (currentStep.value < tourSteps.length - 1) {
    currentStep.value++;
  } else {
    completeTour();
  }
};

const completeTour = async () => {
  isActive.value = false;
  await router.post('/tour/completed');
};
</script>

<template>
  <TourOverlay v-if="isActive" :step="tourSteps[currentStep]" @next="nextStep" @skip="completeTour" />
</template>
```

---

## 14. Boas Práticas

### ✅ Faça

- Mantenha mensagens curtas e diretas
- Use linguagem amigável e empoderadora
- Destaque elementos visuais claramente
- Permita pular o tour a qualquer momento
- Salve o progresso se o usuário sair
- Teste em diferentes tamanhos de tela

### ❌ Evite

- Sobrecarregar com informações
- Usar jargões técnicos
- Bloquear a interface completamente
- Forçar o usuário a completar o tour
- Mostrar o tour repetidamente
- Ignorar acessibilidade (teclado, screen readers)
- Animações excessivas que distraem
- Tooltips que bloqueiam ações importantes
- Personalização baseada em suposições incorretas
- Coletar feedback de forma intrusiva

---

## Conclusão

Este guia fornece uma base sólida para criar um Tour Contextual eficaz no Dashboard do Paciente. Lembre-se de:

1. **Focar no valor**: Cada passo deve mostrar por que é importante
2. **Manter simples**: Menos é mais quando se trata de onboarding
3. **Testar com usuários reais**: Nada substitui feedback real
4. **Iterar baseado em dados**: Use métricas para melhorar continuamente

**Próximos Passos**:
1. Revisar este guia com a equipe
2. Criar mockups visuais do tour e welcome screen
3. Implementar welcome screen primeiro
4. Implementar tour principal usando uma biblioteca de tour
5. Adicionar onboarding contextual (tooltips)
6. Implementar checklist inicial
7. Configurar sistema de métricas e analytics
8. Testar acessibilidade com leitores de tela
9. Testar responsividade em diferentes dispositivos
10. Testar com usuários beta
11. Coletar feedback qualitativo e quantitativo
12. Iterar baseado em dados coletados

---

## Resumo Executivo

Este guia cobre um sistema completo de onboarding que inclui:

1. ✅ **Welcome Screen** - Primeira impressão amigável com escolha do usuário
2. ✅ **Tour Principal** - 5 passos essenciais do dashboard
3. ✅ **Onboarding Contextual** - Tooltips just-in-time para funcionalidades secundárias
4. ✅ **Onboarding Contínua** - Checklist, tours adicionais e descobertas progressivas
5. ✅ **Personalização** - Adaptação baseada no perfil e comportamento do usuário
6. ✅ **Micro-Interações** - Animações e feedback visual para criar "momento WOW"
7. ✅ **Métricas e Feedback** - Sistema completo de monitoramento e coleta de opiniões
8. ✅ **Acessibilidade** - Suporte completo para navegação por teclado, leitores de tela e diferentes dispositivos

### Priorização de Implementação

**Fase 1 (MVP)**:
- Welcome Screen
- Tour principal (5 passos)
- Sistema básico de métricas

**Fase 2 (Melhorias)**:
- Onboarding contextual (tooltips)
- Checklist inicial
- Personalização básica

**Fase 3 (Avançado)**:
- Onboarding contínua
- Micro-interações avançadas
- Sistema completo de feedback
- Personalização avançada baseada em analytics

---

**Última atualização**: 2024  
**Autor**: Equipe Telemedicina Para Todos  
**Versão**: 2.0

