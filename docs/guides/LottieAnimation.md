# Guia de Uso - DotLottie Vue

Este guia explica como usar a biblioteca `@lottiefiles/dotlottie-vue` no projeto.

## Instalação

A biblioteca já está instalada. Se precisar reinstalar:

```bash
npm install @lottiefiles/dotlottie-vue
```

## Uso Básico

### 1. Importação Direta

```vue
<script setup lang="ts">
import { DotLottieVue } from '@lottiefiles/dotlottie-vue';
</script>

<template>
  <DotLottieVue
    src="/animations/loading.lottie"
    :autoplay="true"
    :loop="true"
    style="width: 300px; height: 300px;"
  />
</template>
```

### 2. Usando o Componente Wrapper (Recomendado)

O projeto possui um componente wrapper `LottieAnimation.vue` que facilita o uso:

```vue
<script setup lang="ts">
import LottieAnimation from '@/components/LottieAnimation.vue';
</script>

<template>
  <LottieAnimation
    src="/animations/loading.lottie"
    :width="300"
    :height="300"
    :autoplay="true"
    :loop="true"
  />
</template>
```

## Onde Colocar os Arquivos .lottie

### Opção 1: Pasta Public (Recomendado para animações estáticas)

Coloque os arquivos `.lottie` em `public/animations/`:

```
public/
  └── animations/
      ├── loading.lottie
      ├── success.lottie
      └── error.lottie
```

Uso:
```vue
<LottieAnimation src="/animations/loading.lottie" />
```

### Opção 2: Storage (Para animações dinâmicas/upload)

Se as animações forem geradas ou enviadas pelos usuários:

```
storage/app/public/animations/
```

Uso:
```vue
<LottieAnimation :src="`/storage/animations/${animationName}.lottie`" />
```

## Propriedades Disponíveis

### Propriedades do Componente Wrapper (`LottieAnimation.vue`)

| Propriedade | Tipo | Padrão | Descrição |
|------------|------|--------|-----------|
| `src` | `string` | **obrigatório** | Caminho para o arquivo .lottie |
| `width` | `string \| number` | `300` | Largura da animação |
| `height` | `string \| number` | `300` | Altura da animação |
| `autoplay` | `boolean` | `true` | Inicia automaticamente |
| `loop` | `boolean` | `true` | Repete indefinidamente |
| `speed` | `number` | `1` | Velocidade (1 = normal, 2 = 2x, 0.5 = metade) |
| `backgroundColor` | `string` | - | Cor de fundo |
| `direction` | `'forward' \| 'backward'` | `'forward'` | Direção da animação |
| `renderer` | `'svg' \| 'canvas'` | `'svg'` | Modo de renderização |
| `class` | `string` | - | Classe CSS adicional |

## Exemplos Práticos

### Exemplo 1: Loading Spinner

```vue
<script setup lang="ts">
import LottieAnimation from '@/components/LottieAnimation.vue';
</script>

<template>
  <div class="flex items-center justify-center">
    <LottieAnimation
      src="/animations/loading.lottie"
      :width="100"
      :height="100"
      :loop="true"
      :autoplay="true"
    />
  </div>
</template>
```

### Exemplo 2: Animação com Controle

```vue
<script setup lang="ts">
import { ref } from 'vue';
import LottieAnimation from '@/components/LottieAnimation.vue';

const animationRef = ref<InstanceType<typeof LottieAnimation> | null>(null);

const playAnimation = () => {
  animationRef.value?.play();
};

const pauseAnimation = () => {
  animationRef.value?.pause();
};

const stopAnimation = () => {
  animationRef.value?.stop();
};
</script>

<template>
  <div>
    <LottieAnimation
      ref="animationRef"
      src="/animations/success.lottie"
      :width="200"
      :height="200"
      :autoplay="false"
      :loop="false"
    />
    
    <div class="mt-4 flex gap-2">
      <button @click="playAnimation">Play</button>
      <button @click="pauseAnimation">Pause</button>
      <button @click="stopAnimation">Stop</button>
    </div>
  </div>
</template>
```

### Exemplo 3: Animação Condicional

```vue
<script setup lang="ts">
import { ref } from 'vue';
import LottieAnimation from '@/components/LottieAnimation.vue';

const isLoading = ref(false);
const isSuccess = ref(false);
</script>

<template>
  <div>
    <LottieAnimation
      v-if="isLoading"
      src="/animations/loading.lottie"
      :width="150"
      :height="150"
    />
    
    <LottieAnimation
      v-else-if="isSuccess"
      src="/animations/success.lottie"
      :width="150"
      :height="150"
      :loop="false"
    />
  </div>
</template>
```

### Exemplo 4: Animação com Eventos

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { DotLottieVue } from '@lottiefiles/dotlottie-vue';

const playerRef = ref<InstanceType<typeof DotLottieVue> | null>(null);

onMounted(() => {
  if (playerRef.value) {
    const instance = playerRef.value.getDotLottieInstance();
    
    instance?.addEventListener('complete', () => {
      console.log('Animação concluída!');
      // Executar ação após animação
    });
    
    instance?.addEventListener('load', () => {
      console.log('Animação carregada!');
    });
  }
});
</script>

<template>
  <DotLottieVue
    ref="playerRef"
    src="/animations/success.lottie"
    :autoplay="true"
    :loop="false"
    style="width: 200px; height: 200px;"
  />
</template>
```

## Onde Obter Arquivos .lottie

1. **LottieFiles** (https://lottiefiles.com/)
   - Biblioteca gratuita de animações
   - Exporte como `.lottie` ou `.json` (converta para .lottie)

2. **After Effects + Bodymovin**
   - Crie suas próprias animações
   - Exporte usando o plugin Bodymovin

3. **Conversão de JSON para Lottie**
   - Use ferramentas online para converter `.json` para `.lottie`

## Dicas de Performance

1. **Lazy Loading**: Carregue animações apenas quando necessário
2. **Tamanho dos Arquivos**: Mantenha arquivos pequenos (< 500KB)
3. **Renderização**: Use `svg` para qualidade, `canvas` para performance
4. **Autoplay**: Desabilite `autoplay` se a animação não estiver visível inicialmente

## Troubleshooting

### Animação não aparece
- Verifique se o caminho do arquivo está correto
- Confirme que o arquivo `.lottie` existe no local especificado
- Verifique o console do navegador para erros

### Animação muito lenta
- Reduza o tamanho do arquivo
- Use `renderer="canvas"` em vez de `svg`
- Verifique se há muitas animações rodando simultaneamente

### Animação não para
- Verifique se `loop` está definido como `false`
- Use o método `stop()` do componente

