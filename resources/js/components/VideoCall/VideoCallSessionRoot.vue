<script setup lang="ts">
import { useVideoCallNavigation } from '@/composables/useVideoCallNavigation';
import { useVideoCallSession } from '@/composables/useVideoCallSession';
import { useVideoCallStore } from '@/stores/videoCall';
import { computed, onMounted, onUnmounted } from 'vue';
import VideoCallActiveModal from './VideoCallActiveModal.vue';
import VideoCallFloatingWidget from './VideoCallFloatingWidget.vue';

const props = defineProps<{
    userId: string;
}>();

const store = useVideoCallStore();
const session = useVideoCallSession();
const { enterCall } = useVideoCallNavigation();

const showModal = computed(() => store.isActive && !store.modalDismissed && !store.isOnVideoCallPage);

const showWidget = computed(() => store.isActive && store.modalDismissed && !store.isOnVideoCallPage);

onMounted(async () => {
    if (!store.isActive) {
        await session.bootstrap();
    }
    session.setupEchoListeners(props.userId);
});

onUnmounted(() => {
    // Não chamar teardown aqui — sessão global deve persistir enquanto layout existir
});
</script>

<template>
    <VideoCallActiveModal :open="showModal" :appointment-label="store.appointmentLabel" @enter="enterCall" @dismiss="store.dismissModal" />

    <VideoCallFloatingWidget v-if="showWidget" :call-status="store.status" :appointment-label="store.appointmentLabel" @enter="enterCall" />
</template>
