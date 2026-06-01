<script setup lang="ts">
import type { ConsultChatMessage } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Paperclip, Send, Smile } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

const messages = defineModel<ConsultChatMessage[]>('messages', { required: true });

defineProps<{
    patientFirstName: string;
}>();
const draft = ref('');
const endRef = ref<HTMLElement | null>(null);

watch(
    () => messages.value.length,
    async () => {
        await nextTick();
        endRef.value?.parentElement?.scrollTo({ top: endRef.value.parentElement.scrollHeight, behavior: 'smooth' });
    },
);

const send = () => {
    const text = draft.value.trim();
    if (!text) return;
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    messages.value = [...messages.value, { id: Date.now(), type: 'me', author: 'Você', text, time }];
    draft.value = '';
};
</script>

<template>
    <div class="chat">
        <div class="chat-msgs">
            <template v-for="m in messages" :key="m.id">
                <div v-if="m.type === 'system'" class="chat-system">{{ m.text }}</div>
                <div v-else :class="'chat-msg ' + m.type">
                    <div class="chat-meta">
                        <template v-if="m.type === 'me'">{{ m.time }} · {{ m.author }}</template>
                        <template v-else>{{ m.author }} · {{ m.time }}</template>
                    </div>
                    <div class="chat-bubble">{{ m.text }}</div>
                </div>
            </template>
            <div ref="endRef" />
        </div>

        <div class="chat-input">
            <button type="button" class="rounded-[10px] text-[var(--muted-foreground)] hover:bg-[var(--muted)]" title="Anexar">
                <Paperclip class="h-4 w-4" />
            </button>
            <div class="field">
                <input v-model="draft" :placeholder="`Mensagem para ${patientFirstName}…`" @keydown.enter.exact.prevent="send" />
                <button type="button" class="text-[var(--muted-foreground)]" title="Emoji">
                    <Smile class="h-4 w-4" />
                </button>
            </div>
            <button type="button" class="send" :disabled="!draft.trim()" title="Enviar" @click="send">
                <Send class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
