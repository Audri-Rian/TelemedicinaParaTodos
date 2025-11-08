<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';
import { useRoleRoutes, useAuth } from '@/composables/auth';
import ChatModal from '@/components/modals/ChatModal.vue';

const page = usePage();
const { routes } = useRoleRoutes();
const { isAuthenticated } = useAuth();

const isModalOpen = ref(false);

// Verificar se deve ocultar o botão baseado na rota atual
const shouldShow = computed(() => {
    if (!isAuthenticated.value) {
        return false;
    }

    const currentUrl = page.url as string;
    
    // Ocultar nas páginas de mensagens e videoconferência
    const hiddenPaths = [
        '/messages',
        '/video-call',
        '/consultations', // Página de videoconferência do médico
    ];
    
    return !hiddenPaths.some(path => currentUrl.includes(path));
});

const handleClick = () => {
    // Abrir modal ao invés de redirecionar
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<template>
    <div>
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-90 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-90 translate-y-4"
        >
            <button
                v-if="shouldShow"
                @click="handleClick"
                class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg hover:bg-primary/90 hover:shadow-xl transition-all duration-300 hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                aria-label="Abrir chat"
                title="Abrir chat"
            >
                <MessageCircle class="h-6 w-6" />
            </button>
        </Transition>

        <ChatModal 
            :is-open="isModalOpen" 
            @update:is-open="isModalOpen = $event"
            @close="closeModal"
        />
    </div>
</template>

