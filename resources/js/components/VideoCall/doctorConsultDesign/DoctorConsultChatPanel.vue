<script setup lang="ts">
import type { CallChatMessage } from '@/composables/useCallChat';
import { Send } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    messages: CallChatMessage[];
    patientFirstName: string;
}>();

const emit = defineEmits<{ send: [text: string] }>();

const draft = ref('');
const endRef = ref<HTMLElement | null>(null);

watch(
    () => props.messages.length,
    async () => {
        await nextTick();
        endRef.value?.parentElement?.scrollTo({ top: endRef.value.parentElement.scrollHeight, behavior: 'smooth' });
    },
);

const send = () => {
    const text = draft.value.trim();
    if (!text) return;
    emit('send', text);
    draft.value = '';
};
</script>

<template>
    <div class="chat">
        <div class="chat-msgs">
            <div v-if="messages.length === 0" style="padding: 16px; text-align: center; color: var(--muted-foreground); font-size: 12.5px">
                Nenhuma mensagem ainda. As mensagens ficam salvas na conversa com o paciente.
            </div>
            <template v-for="m in messages" :key="m.id">
                <div v-if="m.type === 'system'" class="chat-system">{{ m.text }}</div>
                <div v-else :class="'chat-msg ' + m.type" :style="{ opacity: m.pending ? 0.6 : 1 }">
                    <div class="chat-meta">
                        <template v-if="m.type === 'me'">{{ m.time }} · {{ m.author }}</template>
                        <template v-else>{{ m.author }} · {{ m.time }}</template>
                    </div>
                    <div class="chat-bubble" :style="m.failed ? { borderColor: 'var(--destructive, #dc2626)' } : undefined">{{ m.text }}</div>
                </div>
            </template>
            <div ref="endRef" />
        </div>

        <div class="chat-input">
            <div class="field">
                <input v-model="draft" :placeholder="`Mensagem para ${patientFirstName}…`" @keydown.enter.exact.prevent="send" />
            </div>
            <button type="button" class="send" :disabled="!draft.trim()" title="Enviar" @click="send">
                <Send class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
