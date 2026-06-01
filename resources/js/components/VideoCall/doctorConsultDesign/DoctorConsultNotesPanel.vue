<script setup lang="ts">
import {
    MOCK_INITIAL_NOTES,
    QUICK_TEMPLATES_O,
    QUICK_TEMPLATES_S,
    type ConsultSoapNotes,
} from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import { FlaskConical, Pill, Plus, Stamp } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const SOAP_DEFS = [
    { key: 'S' as const, title: 'Subjetivo', hint: 'Queixa principal, história referida pelo paciente.', placeholder: 'Refere…' },
    { key: 'O' as const, title: 'Objetivo', hint: 'Sinais vitais, exame físico, achados objetivos.', placeholder: 'Ao exame…' },
    { key: 'A' as const, title: 'Avaliação', hint: 'Hipóteses diagnósticas, CID, raciocínio clínico.', placeholder: 'Hipótese diagnóstica…' },
    { key: 'P' as const, title: 'Plano', hint: 'Conduta, prescrições, exames, retorno.', placeholder: 'Conduta…' },
];

const notes = ref<ConsultSoapNotes>({ ...MOCK_INITIAL_NOTES });
const savedAt = ref(new Date());
let saveTimer: ReturnType<typeof setTimeout> | null = null;

const tick = ref(0);
let tickId: ReturnType<typeof setInterval> | null = null;

const textareaEls: Partial<Record<keyof ConsultSoapNotes, HTMLTextAreaElement>> = {};

const setTextareaRef = (key: keyof ConsultSoapNotes, el: unknown) => {
    if (el instanceof HTMLTextAreaElement) {
        textareaEls[key] = el;
    } else {
        delete textareaEls[key];
    }
};

const resizeTextareas = () => {
    (['S', 'O', 'A', 'P'] as const).forEach((key) => {
        const el = textareaEls[key];
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    });
};

onMounted(() => {
    tickId = setInterval(() => {
        tick.value += 1;
    }, 1000);
    void nextTick(() => resizeTextareas());
});

onUnmounted(() => {
    if (tickId) clearInterval(tickId);
});

const update = (key: keyof ConsultSoapNotes, val: string) => {
    notes.value = { ...notes.value, [key]: val };
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        savedAt.value = new Date();
    }, 600);
    void nextTick(() => resizeTextareas());
};

const fmt = (d: Date) => {
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 5) return 'salvo agora';
    if (diff < 60) return `salvo há ${diff}s`;
    return `salvo há ${Math.floor(diff / 60)} min`;
};

const saveLabel = computed(() => {
    void tick.value;
    return fmt(savedAt.value);
});

watch(notes, () => void nextTick(() => resizeTextareas()), { deep: true });

const emit = defineEmits<{
    action: [kind: 'rx' | 'exam' | 'certificate'];
}>();

const templatesFor = (key: keyof ConsultSoapNotes) => {
    if (key === 'S') return QUICK_TEMPLATES_S;
    if (key === 'O') return QUICK_TEMPLATES_O;
    return null;
};

const insertTemplate = (key: keyof ConsultSoapNotes, text: string) => {
    const cur = notes.value[key] ?? '';
    const prefix = cur.replace(/\s+$/, '');
    const next = prefix ? `${prefix}\n${text}` : text;
    update(key, next);
};
</script>

<template>
    <div class="notes">
        <div class="notes-meta">
            <div>
                <div class="ttl">Anotações da consulta</div>
                <div style="font-size: 13px; color: var(--muted-foreground); margin-top: 2px">Modelo SOAP · vinculado ao prontuário</div>
            </div>
            <span class="save-indicator">
                <span class="blip" />
                {{ saveLabel }}
            </span>
        </div>

        <div class="soap">
            <div v-for="def in SOAP_DEFS" :key="def.key" class="soap-block">
                <div class="soap-head">
                    <span class="soap-letter">{{ def.key }}</span>
                    <div>
                        <div class="soap-title">{{ def.title }}</div>
                        <div class="soap-hint">{{ def.hint }}</div>
                    </div>
                </div>
                <textarea
                    :ref="(el) => setTextareaRef(def.key, el)"
                    class="soap-textarea"
                    :value="notes[def.key]"
                    :placeholder="def.placeholder"
                    @input="update(def.key, ($event.target as HTMLTextAreaElement).value)"
                />
                <div v-if="templatesFor(def.key)?.length" class="quick-templates" style="border-top: 1px solid var(--border)">
                    <div class="qt-row">
                        <button v-for="(t, i) in templatesFor(def.key)" :key="i" type="button" class="qt" @click="insertTemplate(def.key, t)">
                            <Plus class="h-3 w-3" />
                            {{ t }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions-row">
            <button type="button" class="act-btn" @click="emit('action', 'exam')">
                <FlaskConical class="h-3.5 w-3.5" />
                Solicitar exame
            </button>
            <button type="button" class="act-btn" @click="emit('action', 'certificate')">
                <Stamp class="h-3.5 w-3.5" />
                Emitir atestado
            </button>
            <button type="button" class="act-btn primary" style="grid-column: 1 / -1" @click="emit('action', 'rx')">
                <Pill class="h-3.5 w-3.5" />
                Prescrever medicamento
            </button>
        </div>
    </div>
</template>
