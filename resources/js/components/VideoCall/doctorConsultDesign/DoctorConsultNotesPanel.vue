<script setup lang="ts">
import { QUICK_TEMPLATES_O, QUICK_TEMPLATES_S, type ConsultSoapNotes } from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import { useForm } from '@inertiajs/vue3';
import { FlaskConical, Loader2, Pill, Plus, Save, Stamp } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps<{
    patientId?: string | null;
    appointmentId?: string | null;
}>();

const emit = defineEmits<{
    action: [kind: 'rx' | 'exam' | 'certificate'];
    saved: [];
}>();

const SOAP_DEFS = [
    { key: 'S' as const, title: 'Subjetivo', hint: 'Queixa principal, história referida pelo paciente.', placeholder: 'Refere…' },
    { key: 'O' as const, title: 'Objetivo', hint: 'Sinais vitais, exame físico, achados objetivos.', placeholder: 'Ao exame…' },
    { key: 'A' as const, title: 'Avaliação', hint: 'Hipóteses diagnósticas, CID, raciocínio clínico.', placeholder: 'Hipótese diagnóstica…' },
    { key: 'P' as const, title: 'Plano', hint: 'Conduta, prescrições, exames, retorno.', placeholder: 'Conduta…' },
];

const notes = ref<ConsultSoapNotes>({ S: '', O: '', A: '', P: '' });
const dirty = ref(false);
const savedOnce = ref(false);

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

onMounted(() => void nextTick(() => resizeTextareas()));

const update = (key: keyof ConsultSoapNotes, val: string) => {
    notes.value = { ...notes.value, [key]: val };
    dirty.value = true;
    void nextTick(() => resizeTextareas());
};

const hasContent = computed(() => (['S', 'O', 'A', 'P'] as const).some((key) => notes.value[key].trim() !== ''));
const canSave = computed(() => Boolean(props.patientId && props.appointmentId) && hasContent.value);

const form = useForm<{ appointment_id: string; title: string; content: string; category: string }>({
    appointment_id: '',
    title: '',
    content: '',
    category: 'general',
});

const buildContent = () =>
    SOAP_DEFS.map((def) => {
        const value = notes.value[def.key].trim();
        return value ? `${def.key} — ${def.title}:\n${value}` : null;
    })
        .filter(Boolean)
        .join('\n\n');

const save = () => {
    if (!props.patientId || !props.appointmentId || form.processing || !hasContent.value) return;

    form.appointment_id = props.appointmentId;
    form.title = 'Anotações da teleconsulta (SOAP)';
    form.content = buildContent();

    // preserveState mantém o overlay/streams vivos durante o redirect do back()
    form.post(`/doctor/patients/${props.patientId}/medical-record/notes`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            dirty.value = false;
            savedOnce.value = true;
            emit('saved');
        },
    });
};

const saveLabel = computed(() => {
    if (form.processing) return 'salvando…';
    if (dirty.value) return savedOnce.value ? 'alterações não salvas' : 'não salvo';
    return savedOnce.value ? 'salvo no prontuário' : 'novo registro';
});

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
            <button type="button" class="act-btn" style="grid-column: 1 / -1" @click="emit('action', 'rx')">
                <Pill class="h-3.5 w-3.5" />
                Prescrever medicamento
            </button>
            <button
                type="button"
                class="act-btn primary"
                style="grid-column: 1 / -1"
                :disabled="!canSave || form.processing"
                :title="canSave ? 'Salvar como nota clínica no prontuário' : 'Escreva uma anotação para salvar'"
                @click="save"
            >
                <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                <Save v-else class="h-3.5 w-3.5" />
                {{ form.processing ? 'Salvando…' : 'Salvar no prontuário' }}
            </button>
        </div>
    </div>
</template>
