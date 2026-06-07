<script setup lang="ts">
import type { PatientConsultChecklistItem, PatientConsultSharedItem } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Check, Download, FlaskConical, Hand, MessageSquare, Pill, Plus, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    items: PatientConsultSharedItem[];
    notes: PatientConsultChecklistItem[];
    complaint: string;
}>();

const emit = defineEmits<{
    'update:notes': [value: PatientConsultChecklistItem[]];
    action: [kind: 'download' | 'view' | 'repeat' | 'doubt', item?: PatientConsultSharedItem];
}>();

const draft = ref('');

const addNote = () => {
    const text = draft.value.trim();
    if (!text) return;
    emit('update:notes', [...props.notes, { id: Date.now(), text, done: false }]);
    draft.value = '';
};

const toggle = (id: number) => {
    emit(
        'update:notes',
        props.notes.map((n) => (n.id === id ? { ...n, done: !n.done } : n)),
    );
};

const remove = (id: number) => {
    emit(
        'update:notes',
        props.notes.filter((n) => n.id !== id),
    );
};
</script>

<template>
    <div style="padding: 16px 18px 18px; display: flex; flex-direction: column; gap: 18px">
        <div>
            <div class="pat-title" style="margin-bottom: 8px">Motivo da consulta</div>
            <div
                style="
                    padding: 12px 14px;
                    border-radius: 10px;
                    background: var(--primary-50);
                    border: 1px solid #99f6e4;
                    font-size: 13.5px;
                    line-height: 1.5;
                    color: var(--primary-800);
                "
            >
                {{ complaint }}
            </div>
        </div>

        <div>
            <div class="pat-head" style="margin-bottom: 10px">
                <span class="pat-title">Recebido na consulta</span>
                <span
                    style="
                        font-size: 11px;
                        font-weight: 500;
                        padding: 2px 8px;
                        border-radius: 999px;
                        background: var(--primary-50);
                        color: var(--primary-800);
                        border: 1px solid #99f6e4;
                    "
                >
                    {{ items.length }}
                </span>
            </div>
            <div
                v-if="items.length === 0"
                style="
                    padding: 16px;
                    text-align: center;
                    border: 1.5px dashed var(--border-strong);
                    border-radius: 12px;
                    color: var(--muted-foreground);
                    font-size: 12.5px;
                "
            >
                Documentos que o médico compartilhar durante a consulta aparecem aqui.
            </div>
            <div v-else style="display: flex; flex-direction: column; gap: 10px">
                <div
                    v-for="it in items"
                    :key="it.id"
                    style="
                        display: flex;
                        gap: 12px;
                        align-items: flex-start;
                        padding: 14px;
                        border-radius: 12px;
                        border: 1px solid var(--border);
                        background: white;
                    "
                >
                    <div
                        :style="{
                            width: '38px',
                            height: '38px',
                            borderRadius: '10px',
                            background: it.icon === 'flask' ? 'var(--info-50)' : 'var(--primary-50)',
                            color: it.icon === 'flask' ? 'var(--info)' : 'var(--primary-800)',
                            border: it.icon === 'flask' ? '1px solid var(--info-100)' : '1px solid #99f6e4',
                            display: 'grid',
                            placeItems: 'center',
                            flexShrink: '0',
                        }"
                    >
                        <FlaskConical v-if="it.icon === 'flask'" class="h-4 w-4" />
                        <Pill v-else class="h-4 w-4" />
                    </div>
                    <div style="min-width: 0; flex: 1">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px">
                            <span style="font-size: 13px; font-weight: 600">{{ it.title }}</span>
                            <span style="font-size: 11px; color: var(--muted-foreground)">· {{ it.issuedAt }}</span>
                        </div>
                        <div style="font-size: 13px; color: var(--foreground); line-height: 1.45">{{ it.summary }}</div>
                        <div style="font-size: 11.5px; color: var(--muted-foreground); margin-top: 6px">{{ it.status }}</div>
                        <div style="display: flex; gap: 6px; margin-top: 10px">
                            <button
                                type="button"
                                class="act-btn"
                                style="height: 30px; font-size: 12px; padding: 0 10px"
                                @click="emit('action', 'view', it)"
                            >
                                Visualizar
                            </button>
                            <button
                                type="button"
                                class="act-btn primary"
                                style="height: 30px; font-size: 12px; padding: 0 10px"
                                @click="emit('action', 'download', it)"
                            >
                                <Download class="h-3 w-3" />
                                Baixar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="pat-head" style="margin-bottom: 10px">
                <span class="pat-title">Minhas anotações</span>
                <span style="font-size: 11px; color: var(--muted-foreground)">privado · só você vê</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px">
                <div
                    v-for="n in notes"
                    :key="n.id"
                    style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 8px 10px;
                        border-radius: 9px;
                        border: 1px solid var(--border);
                        background: white;
                    "
                >
                    <button
                        type="button"
                        :style="{
                            width: '18px',
                            height: '18px',
                            borderRadius: '5px',
                            border: `1.5px solid ${n.done ? 'var(--primary)' : 'var(--border-strong)'}`,
                            background: n.done ? 'var(--primary)' : 'white',
                            color: 'white',
                            display: 'grid',
                            placeItems: 'center',
                            flexShrink: '0',
                        }"
                        @click="toggle(n.id)"
                    >
                        <Check v-if="n.done" class="h-2.5 w-2.5" :stroke-width="3" />
                    </button>
                    <span
                        :style="{
                            fontSize: '13px',
                            flex: 1,
                            textDecoration: n.done ? 'line-through' : 'none',
                            color: n.done ? 'var(--muted-foreground)' : 'var(--foreground)',
                        }"
                    >
                        {{ n.text }}
                    </span>
                    <button type="button" style="color: var(--muted-foreground); padding: 2px; opacity: 0.6" @click="remove(n.id)">
                        <X class="h-3 w-3" />
                    </button>
                </div>
            </div>

            <div style="display: flex; gap: 6px">
                <input
                    v-model="draft"
                    placeholder="Lembrete ou pergunta para o médico…"
                    style="
                        flex: 1;
                        height: 36px;
                        padding: 0 12px;
                        border: 1px solid var(--border-strong);
                        border-radius: 9px;
                        font-size: 13px;
                        outline: none;
                        background: white;
                    "
                    @keydown.enter.exact.prevent="addNote"
                />
                <button type="button" class="act-btn primary" :disabled="!draft.trim()" style="height: 36px; padding: 0 14px" @click="addNote">
                    <Plus class="h-3 w-3" />
                </button>
            </div>
        </div>

        <div style="padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); background: var(--muted)">
            <div style="font-size: 12px; color: var(--muted-foreground); margin-bottom: 8px">Ações rápidas</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px">
                <button type="button" class="act-btn" @click="emit('action', 'repeat')">
                    <MessageSquare class="h-3 w-3" />
                    Pedir para repetir
                </button>
                <button type="button" class="act-btn" @click="emit('action', 'doubt')">
                    <Hand class="h-3 w-3" />
                    Levantar dúvida
                </button>
            </div>
        </div>
    </div>
</template>
