<script setup lang="ts">
import { ref } from 'vue';

interface ComplementEntry {
    date: string;
    time: string;
    author: string;
    body: string;
}

defineProps<{
    entries: ComplementEntry[];
    doctorName: string;
    composerOpen: boolean;
}>();

const emit = defineEmits<{
    openComposer: [];
    closeComposer: [];
    submit: [body: string];
}>();

const draft = ref('');
const textareaRef = ref<HTMLTextAreaElement | null>(null);

function handleSubmit() {
    const trimmed = draft.value.trim();
    if (!trimmed) return;
    emit('submit', trimmed);
    draft.value = '';
    emit('closeComposer');
}
</script>

<template>
    <section class="cp-compl-card" aria-label="Nota complementar — adendo legal">
        <span class="cp-compl-stamp">Adendo legal</span>

        <h3 class="cp-compl-title">
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#A16207"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34" />
                <polygon points="18 2 22 6 12 16 8 16 8 12 18 2" />
            </svg>
            Notas complementares
        </h3>

        <p class="cp-compl-sub">
            Cada nota é um <b>adendo datado e assinado</b>, registrado <b>separadamente</b> do prontuário original. O conteúdo selado permanece
            imutável — esta seção é o local correto para observações pós-consulta, esclarecimentos ou correções factuais conforme Resolução CFM.
        </p>

        <!-- Timeline of existing entries -->
        <div v-if="entries.length > 0" class="cp-compl-timeline">
            <article v-for="(entry, i) in entries" :key="i" class="cp-compl-entry">
                <div class="cp-compl-entry-head">
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#A16207"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34" />
                        <polygon points="18 2 22 6 12 16 8 16 8 12 18 2" />
                    </svg>
                    <b>{{ entry.author }}</b>
                    <span>· {{ entry.date }} · {{ entry.time }}</span>
                    <span class="signed-badge">
                        <svg
                            width="10"
                            height="10"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#16A34A"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            style="display: inline; vertical-align: -1px; margin-right: 3px"
                        >
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Assinado digitalmente
                    </span>
                </div>
                <div class="cp-compl-entry-body">{{ entry.body }}</div>
            </article>
        </div>

        <!-- Composer -->
        <div v-if="composerOpen" class="cp-compl-composer">
            <textarea
                ref="textareaRef"
                v-model="draft"
                class="cp-compl-ta"
                placeholder="Descreva a observação complementar. Esta nota será assinada digitalmente e registrada como adendo permanente."
                rows="3"
            />
            <div class="cp-compl-composer-foot">
                <span class="cp-compl-signed">
                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Será assinado como <b>{{ doctorName }}</b>
                </span>
                <div class="cp-compl-actions">
                    <button class="cp-btn-ghost" @click="emit('closeComposer')">Cancelar</button>
                    <button class="cp-btn-compl" :disabled="!draft.trim()" @click="handleSubmit">
                        <svg
                            width="12"
                            height="12"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Assinar e adicionar
                    </button>
                </div>
            </div>
        </div>

        <button v-else class="cp-btn-compl" @click="emit('openComposer')">
            <svg
                width="12"
                height="12"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Adicionar nota complementar
        </button>
    </section>
</template>

<style scoped>
.cp-compl-card {
    background: linear-gradient(180deg, #fffbeb 0%, #fffef8 100%);
    border: 1.5px dashed #e2c580;
    border-radius: 14px;
    padding: 22px 24px;
    position: relative;
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}

.cp-compl-stamp {
    position: absolute;
    top: -10px;
    left: 20px;
    background: #fbbf24;
    color: #422006;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 3px 9px;
    border-radius: 4px;
    transform: rotate(-1.5deg);
}

.cp-compl-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--cp-ink-900, #0a1f1e);
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 4px;
}

.cp-compl-sub {
    font-size: 12.5px;
    color: var(--cp-ink-600, #355551);
    margin-bottom: 16px;
    line-height: 1.5;
    max-width: 60ch;
}
.cp-compl-sub b {
    color: var(--cp-ink-900, #0a1f1e);
}

/* Timeline */
.cp-compl-timeline {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: 14px;
}

.cp-compl-entry {
    position: relative;
    padding: 0 0 16px 26px;
    border-left: 1.5px dashed #e2c580;
}
.cp-compl-entry::before {
    content: '';
    position: absolute;
    left: -7px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #fbbf24;
    border: 2px solid #fffbeb;
}
.cp-compl-entry:last-child {
    border-left-color: transparent;
    padding-bottom: 4px;
}

.cp-compl-entry-head {
    font-size: 11.5px;
    color: var(--cp-ink-500, #5a726f);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.cp-compl-entry-head b {
    color: var(--cp-ink-900, #0a1f1e);
    font-weight: 600;
    font-size: 12.5px;
}
.signed-badge {
    margin-left: auto;
    font-family: var(--cp-font-mono, monospace);
    font-size: 10.5px;
    color: var(--cp-ink-400, #8fa2a0);
}

.cp-compl-entry-body {
    font-size: 13.5px;
    color: var(--cp-ink-800, #14302e);
    line-height: 1.55;
    letter-spacing: -0.005em;
}

/* Composer */
.cp-compl-composer {
    background: var(--cp-surface, #fff);
    border: 1px solid #e5d5a0;
    border-radius: 10px;
    padding: 12px 14px;
}
.cp-compl-ta {
    width: 100%;
    border: 0;
    outline: 0;
    resize: vertical;
    font-family: var(--cp-font-sans, sans-serif);
    font-size: 14px;
    color: var(--cp-ink-900, #0a1f1e);
    min-height: 64px;
    background: transparent;
    line-height: 1.55;
}
.cp-compl-ta::placeholder {
    color: var(--cp-ink-400, #8fa2a0);
    font-style: italic;
}

.cp-compl-composer-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px dashed #e5d5a0;
    padding-top: 10px;
    margin-top: 8px;
    gap: 8px;
}

.cp-compl-signed {
    font-size: 11.5px;
    color: var(--cp-ink-500, #5a726f);
    display: flex;
    align-items: center;
    gap: 6px;
}
.cp-compl-signed b {
    color: var(--cp-ink-800, #14302e);
}

.cp-compl-actions {
    display: flex;
    gap: 8px;
}

.cp-btn-ghost {
    appearance: none;
    background: transparent;
    border: 1px solid var(--cp-line-strong, #c8d4d2);
    color: var(--cp-ink-600, #355551);
    padding: 7px 12px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    font-family: var(--cp-font-sans, sans-serif);
    transition: all 120ms;
}
.cp-btn-ghost:hover {
    background: var(--cp-surface-2, #fafbfb);
}

.cp-btn-compl {
    appearance: none;
    background: #422006;
    border: 1px solid #422006;
    color: #fef3c7;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12.5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: var(--cp-font-sans, sans-serif);
    transition: all 120ms;
}
.cp-btn-compl:hover {
    background: #1f1305;
    border-color: #1f1305;
    color: white;
}
.cp-btn-compl:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
