<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { DotLottieVue } from '@lottiefiles/dotlottie-vue';

interface Props {
    /**
     * Caminho para o arquivo .lottie ou URL
     * Exemplo: '/animations/loading.lottie' ou 'https://example.com/animation.lottie'
     */
    src: string;
    /**
     * Largura da animação (padrão: 300px)
     */
    width?: string | number;
    /**
     * Altura da animação (padrão: 300px)
     */
    height?: string | number;
    /**
     * Inicia a animação automaticamente (padrão: true)
     */
    autoplay?: boolean;
    /**
     * Faz a animação repetir indefinidamente (padrão: true)
     */
    loop?: boolean;
    /**
     * Velocidade de reprodução (padrão: 1)
     * 1 = velocidade normal, 2 = 2x mais rápido, 0.5 = metade da velocidade
     */
    speed?: number;
    /**
     * Cor de fundo da animação
     */
    backgroundColor?: string;
    /**
     * Direção da animação: 'forward' ou 'backward' (padrão: 'forward')
     */
    direction?: 'forward' | 'backward';
    /**
     * Modo de renderização: 'svg' ou 'canvas' (padrão: 'svg')
     */
    renderer?: 'svg' | 'canvas';
}

const props = withDefaults(defineProps<Props>(), {
    width: 300,
    height: 300,
    autoplay: true,
    loop: true,
    speed: 1,
    direction: 'forward',
    renderer: 'svg',
});

// Referência para o componente DotLottieVue
const playerRef = ref<any>(null);

// Métodos expostos para controle externo
const play = () => {
    if (playerRef.value) {
        const instance = playerRef.value.getDotLottieInstance();
        instance?.play();
    }
};

const pause = () => {
    if (playerRef.value) {
        const instance = playerRef.value.getDotLottieInstance();
        instance?.pause();
    }
};

const stop = () => {
    if (playerRef.value) {
        const instance = playerRef.value.getDotLottieInstance();
        instance?.stop();
    }
};

const setSpeed = (speed: number) => {
    if (playerRef.value) {
        const instance = playerRef.value.getDotLottieInstance();
        instance?.setSpeed(speed);
    }
};

// Expor métodos para o componente pai
defineExpose({
    play,
    pause,
    stop,
    setSpeed,
    getInstance: () => playerRef.value?.getDotLottieInstance(),
});

// Eventos
const handleComplete = () => {
    // Disparado quando a animação termina (se loop estiver desabilitado)
    console.log('Animação concluída');
};

const handleLoad = () => {
    // Disparado quando a animação é carregada
    console.log('Animação carregada');
};

onMounted(() => {
    if (playerRef.value) {
        const instance = playerRef.value.getDotLottieInstance();
        
        // Adicionar listeners de eventos
        instance?.addEventListener('complete', handleComplete);
        instance?.addEventListener('load', handleLoad);
    }
});
</script>

<template>
    <div 
        :class="['lottie-container', $attrs.class]" 
        :style="{ width: typeof props.width === 'number' ? `${props.width}px` : props.width, height: typeof props.height === 'number' ? `${props.height}px` : props.height }"
    >
        <DotLottieVue
            ref="playerRef"
            :src="props.src"
            :autoplay="props.autoplay"
            :loop="props.loop"
            :speed="props.speed"
            :direction="props.direction"
            :renderer="props.renderer"
            :background-color="props.backgroundColor"
            class="w-full h-full"
        />
    </div>
</template>

<style scoped>
.lottie-container {
    display: inline-block;
}
</style>

