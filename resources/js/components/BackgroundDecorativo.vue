<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

interface Props {
  variant?: 'patient' | 'doctor' | 'default';
  intensity?: 'low' | 'medium' | 'high';
  enableAnimations?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  intensity: 'medium',
  enableAnimations: true
});

const isVisible = ref(false);
const animationFrame = ref<number | null>(null);

// Configurações de intensidade
const intensityConfig = {
  low: {
    opacity: { min: 0.1, max: 0.2 },
    blur: { min: 2, max: 3 },
    size: { min: 0.8, max: 1.2 }
  },
  medium: {
    opacity: { min: 0.15, max: 0.3 },
    blur: { min: 2, max: 3 },
    size: { min: 0.9, max: 1.1 }
  },
  high: {
    opacity: { min: 0.2, max: 0.4 },
    blur: { min: 2, max: 3 },
    size: { min: 0.8, max: 1.3 }
  }
};

// Configurações específicas por variante
const variantConfig = {
  patient: {
    colors: ['from-primary/20', 'from-primary/15', 'from-primary/12'],
    positions: ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center']
  },
  doctor: {
    colors: ['from-primary/25', 'from-primary/20', 'from-primary/18'],
    positions: ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center']
  },
  default: {
    colors: ['from-primary/20', 'from-primary/15', 'from-primary/12'],
    positions: ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center']
  }
};

// Lazy loading para animações
const startAnimations = () => {
  if (!props.enableAnimations) return;
  
  const animate = () => {
    // Animação sutil de pulsação
    const elements = document.querySelectorAll('.bg-animated');
    elements.forEach((el, index) => {
      const element = el as HTMLElement;
      const time = Date.now() * 0.001;
      const delay = index * 0.5;
      const opacity = intensityConfig[props.intensity].opacity.min + 
        (intensityConfig[props.intensity].opacity.max - intensityConfig[props.intensity].opacity.min) * 
        (Math.sin(time + delay) * 0.5 + 0.5);
      
      element.style.opacity = opacity.toString();
    });
    
    animationFrame.value = requestAnimationFrame(animate);
  };
  
  animate();
};

const stopAnimations = () => {
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value);
    animationFrame.value = null;
  }
};

onMounted(() => {
  // Lazy loading: só inicia animações quando componente está visível
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        isVisible.value = true;
        startAnimations();
      } else {
        isVisible.value = false;
        stopAnimations();
      }
    });
  });
  
  observer.observe(document.body);
});

onUnmounted(() => {
  stopAnimations();
});
</script>

<template>
  <div 
    class="fixed inset-0 overflow-hidden pointer-events-none"
    role="presentation"
    aria-hidden="true"
  >
    <!-- Gradiente base sutil -->
    <div 
      class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-primary/10"
      aria-hidden="true"
    ></div>
    
    <!-- Formas abstratas flutuantes -->
    <!-- Canto superior esquerdo -->
    <div 
      class="absolute -top-20 -left-20 w-80 h-80 bg-primary/20 rounded-full blur-3xl opacity-30 bg-animated"
      :class="{
        'animate-pulse': enableAnimations && isVisible,
        'sm:w-96 sm:h-96': true,
        'md:w-80 md:h-80': true,
        'lg:w-80 lg:h-80': true,
        'xl:w-96 xl:h-96': true,
        '2xl:w-80 2xl:h-80': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute top-10 left-10 w-40 h-40 bg-primary/15 rounded-full blur-2xl opacity-25 bg-animated"
      :class="{
        'sm:w-48 sm:h-48': true,
        'md:w-40 md:h-40': true,
        'lg:w-40 lg:h-40': true,
        'xl:w-48 xl:h-48': true,
        '2xl:w-40 2xl:h-40': true
      }"
      aria-hidden="true"
    ></div>
    
    <!-- Canto superior direito -->
    <div 
      class="absolute -top-32 -right-32 w-96 h-96 bg-primary/25 rounded-full blur-3xl opacity-20 bg-animated"
      :class="{
        'sm:w-80 sm:h-80': true,
        'md:w-96 md:h-96': true,
        'lg:w-96 lg:h-96': true,
        'xl:w-80 xl:h-80': true,
        '2xl:w-96 2xl:h-96': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute top-20 right-20 w-32 h-32 bg-primary/20 rounded-full blur-xl opacity-30 bg-animated"
      :class="{
        'sm:w-40 sm:h-40': true,
        'md:w-32 md:h-32': true,
        'lg:w-32 lg:h-32': true,
        'xl:w-40 xl:h-40': true,
        '2xl:w-32 2xl:h-32': true
      }"
      aria-hidden="true"
    ></div>
    
    <!-- Canto inferior esquerdo -->
    <div 
      class="absolute -bottom-24 -left-24 w-72 h-72 bg-primary/18 rounded-full blur-3xl opacity-25 bg-animated"
      :class="{
        'sm:w-80 sm:h-80': true,
        'md:w-72 md:h-72': true,
        'lg:w-72 lg:h-72': true,
        'xl:w-80 xl:h-80': true,
        '2xl:w-72 2xl:h-72': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute bottom-16 left-16 w-48 h-48 bg-primary/12 rounded-full blur-2xl opacity-30 bg-animated"
      :class="{
        'sm:w-56 sm:h-56': true,
        'md:w-48 md:h-48': true,
        'lg:w-48 lg:h-48': true,
        'xl:w-56 xl:h-56': true,
        '2xl:w-48 2xl:h-48': true
      }"
      aria-hidden="true"
    ></div>
    
    <!-- Canto inferior direito -->
    <div 
      class="absolute -bottom-20 -right-20 w-64 h-64 bg-primary/22 rounded-full blur-3xl opacity-20 bg-animated"
      :class="{
        'sm:w-72 sm:h-72': true,
        'md:w-64 md:h-64': true,
        'lg:w-64 lg:h-64': true,
        'xl:w-72 xl:h-72': true,
        '2xl:w-64 2xl:h-64': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute bottom-12 right-12 w-36 h-36 bg-primary/15 rounded-full blur-xl opacity-25 bg-animated"
      :class="{
        'sm:w-44 sm:h-44': true,
        'md:w-36 md:h-36': true,
        'lg:w-36 lg:h-36': true,
        'xl:w-44 xl:h-44': true,
        '2xl:w-36 2xl:h-36': true
      }"
      aria-hidden="true"
    ></div>
    
    <!-- Centro da tela - luz difusa -->
    <div 
      class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl opacity-20 bg-animated"
      :class="{
        'sm:w-80 sm:h-80': true,
        'md:w-96 md:h-96': true,
        'lg:w-96 lg:h-96': true,
        'xl:w-80 xl:h-80': true,
        '2xl:w-96 2xl:h-96': true
      }"
      aria-hidden="true"
    ></div>
    
    <!-- Formas menores espalhadas -->
    <div 
      class="absolute top-1/3 left-1/4 w-24 h-24 bg-primary/20 rounded-full blur-xl opacity-25 bg-animated"
      :class="{
        'sm:w-28 sm:h-28': true,
        'md:w-24 md:h-24': true,
        'lg:w-24 lg:h-24': true,
        'xl:w-28 xl:h-28': true,
        '2xl:w-24 2xl:h-24': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute top-2/3 right-1/3 w-20 h-20 bg-primary/15 rounded-full blur-lg opacity-30 bg-animated"
      :class="{
        'sm:w-24 sm:h-24': true,
        'md:w-20 md:h-20': true,
        'lg:w-20 lg:h-20': true,
        'xl:w-24 xl:h-24': true,
        '2xl:w-20 2xl:h-20': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute top-1/4 right-1/4 w-28 h-28 bg-primary/18 rounded-full blur-2xl opacity-20 bg-animated"
      :class="{
        'sm:w-32 sm:h-32': true,
        'md:w-28 md:h-28': true,
        'lg:w-28 lg:h-28': true,
        'xl:w-32 xl:h-32': true,
        '2xl:w-28 2xl:h-28': true
      }"
      aria-hidden="true"
    ></div>
    <div 
      class="absolute bottom-1/3 left-1/3 w-16 h-16 bg-primary/25 rounded-full blur-lg opacity-25 bg-animated"
      :class="{
        'sm:w-20 sm:h-20': true,
        'md:w-16 md:h-16': true,
        'lg:w-16 lg:h-16': true,
        'xl:w-20 xl:h-20': true,
        '2xl:w-16 2xl:h-16': true
      }"
      aria-hidden="true"
    ></div>
  </div>
</template>
