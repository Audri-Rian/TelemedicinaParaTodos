<!-- 
  EXEMPLO DE USO DO DOTLOTTIE VUE
  
  Este é um arquivo de exemplo mostrando diferentes formas de usar
  a biblioteca @lottiefiles/dotlottie-vue no projeto.
  
  Você pode copiar e adaptar este código para suas necessidades.
-->

<script setup lang="ts">
import { ref } from 'vue';
import LottieAnimation from '@/components/LottieAnimation.vue';
import { DotLottieVue } from '@lottiefiles/dotlottie-vue';

// Exemplo 1: Uso básico com componente wrapper
const isLoading = ref(false);

// Exemplo 2: Controle programático
const animationRef = ref<InstanceType<typeof LottieAnimation> | null>(null);

const toggleLoading = () => {
    isLoading.value = !isLoading.value;
};

const playAnimation = () => {
    animationRef.value?.play();
};

const pauseAnimation = () => {
    animationRef.value?.pause();
};

const stopAnimation = () => {
    animationRef.value?.stop();
};

// Exemplo 3: Uso direto com DotLottieVue
const directPlayerRef = ref<InstanceType<typeof DotLottieVue> | null>(null);

const handleAnimationComplete = () => {
    console.log('Animação concluída!');
    // Execute ações após a animação terminar
};
</script>

<template>
    <div class="p-8 space-y-8">
        <h1 class="text-3xl font-bold">Exemplos de Uso - DotLottie Vue</h1>

        <!-- Exemplo 1: Uso Básico -->
        <section class="border p-4 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">1. Uso Básico</h2>
            <div class="flex items-center gap-4">
                <LottieAnimation
                    v-if="isLoading"
                    src="/animations/loading.lottie"
                    :width="100"
                    :height="100"
                />
                <button @click="toggleLoading" class="px-4 py-2 bg-blue-500 text-white rounded">
                    {{ isLoading ? 'Parar Loading' : 'Iniciar Loading' }}
                </button>
            </div>
        </section>

        <!-- Exemplo 2: Controle Programático -->
        <section class="border p-4 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">2. Controle Programático</h2>
            <LottieAnimation
                ref="animationRef"
                src="/animations/success.lottie"
                :width="200"
                :height="200"
                :autoplay="false"
                :loop="false"
            />
            <div class="mt-4 flex gap-2">
                <button @click="playAnimation" class="px-4 py-2 bg-green-500 text-white rounded">
                    Play
                </button>
                <button @click="pauseAnimation" class="px-4 py-2 bg-yellow-500 text-white rounded">
                    Pause
                </button>
                <button @click="stopAnimation" class="px-4 py-2 bg-red-500 text-white rounded">
                    Stop
                </button>
            </div>
        </section>

        <!-- Exemplo 3: Uso Direto com DotLottieVue -->
        <section class="border p-4 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">3. Uso Direto (sem wrapper)</h2>
            <DotLottieVue
                ref="directPlayerRef"
                src="/animations/error.lottie"
                :autoplay="true"
                :loop="true"
                style="width: 150px; height: 150px;"
            />
        </section>

        <!-- Exemplo 4: Diferentes Tamanhos -->
        <section class="border p-4 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">4. Diferentes Tamanhos</h2>
            <div class="flex items-end gap-4">
                <LottieAnimation
                    src="/animations/loading.lottie"
                    :width="50"
                    :height="50"
                />
                <LottieAnimation
                    src="/animations/loading.lottie"
                    :width="100"
                    :height="100"
                />
                <LottieAnimation
                    src="/animations/loading.lottie"
                    :width="200"
                    :height="200"
                />
            </div>
        </section>

        <!-- Exemplo 5: Com Velocidade Customizada -->
        <section class="border p-4 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">5. Velocidade Customizada</h2>
            <div class="flex gap-4">
                <div class="text-center">
                    <LottieAnimation
                        src="/animations/loading.lottie"
                        :width="100"
                        :height="100"
                        :speed="0.5"
                    />
                    <p class="mt-2 text-sm">0.5x (Lento)</p>
                </div>
                <div class="text-center">
                    <LottieAnimation
                        src="/animations/loading.lottie"
                        :width="100"
                        :height="100"
                        :speed="1"
                    />
                    <p class="mt-2 text-sm">1x (Normal)</p>
                </div>
                <div class="text-center">
                    <LottieAnimation
                        src="/animations/loading.lottie"
                        :width="100"
                        :height="100"
                        :speed="2"
                    />
                    <p class="mt-2 text-sm">2x (Rápido)</p>
                </div>
            </div>
        </section>
    </div>
</template>

