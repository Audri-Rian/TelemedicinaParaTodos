<script setup lang="ts">
import { useFormatters } from '@/composables/useFormatters';
import type { AutoSaveStatus } from '@/types/consultation-detail';
import type { Component } from 'vue';
import { computed } from 'vue';

const props = defineProps<{
    backHref: string;
    patientName: string;
    patientAge?: number;
    patientGender?: string;
    scheduledDateFormatted: string;
    statusBadge: { label: string; icon: Component };
    mode: 'scheduled' | 'in_progress' | 'completed';
    elapsedTime?: number | null;
    elapsedTimeFormatted: string;
    autoSaveStatus: AutoSaveStatus;
    hasUnsavedChanges: boolean;
    isSaving: boolean;
    lastSaved: Date | null;
    msgCount?: number;
}>();

const emit = defineEmits<{
    start: [];
    save: [];
    finalize: [];
    pdf: [];
    messages: [];
    addComplement: [];
}>();

const { formatTime } = useFormatters();

const isScheduled = computed(() => props.mode === 'scheduled');
const isInProgress = computed(() => props.mode === 'in_progress');
const isCompleted = computed(() => props.mode === 'completed');

const genderLabel = computed(() => {
    if (!props.patientGender) return '';
    const g = props.patientGender.toLowerCase();
    if (g === 'male' || g === 'masculino' || g === 'm') return 'M';
    if (g === 'female' || g === 'feminino' || g === 'f') return 'F';
    return props.patientGender;
});
</script>

<template>
    <header class="cp-hdr">
        <div class="cp-hdr-accent-bar" :class="{ 'is-ativa': isInProgress }" />
        <div class="cp-hdr-inner">
            <!-- Row 1: breadcrumb -->
            <div class="cp-hdr-row1">
                <a :href="backHref" class="cp-hdr-back" :aria-label="'Voltar'">
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
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>
                <nav class="cp-crumbs" aria-label="Breadcrumb">
                    <a :href="backHref">{{ isCompleted ? 'Histórico' : 'Consultas' }}</a>
                    <span class="sep">/</span>
                    <span class="now">{{ patientName }}</span>
                </nav>
            </div>

            <!-- Row 2: identity + actions -->
            <div class="cp-hdr-row2">
                <div class="cp-hdr-id">
                    <h1>{{ patientName }}</h1>
                    <div class="cp-hdr-meta">
                        <span v-if="patientAge">{{ patientAge }} anos</span>
                        <span v-if="patientAge && patientGender" class="dot" />
                        <span v-if="patientGender">{{ genderLabel }}</span>
                        <span class="dot" />
                        <span>{{ scheduledDateFormatted }}</span>
                    </div>
                    <div class="cp-hdr-status">
                        <span class="pulse" :class="{ 'is-ativa': isInProgress }" />
                        {{ statusBadge.label }}
                    </div>
                </div>

                <span class="cp-hdr-spacer" />

                <!-- Timer (only in_progress) -->
                <div v-if="isInProgress && elapsedTime" class="cp-hdr-timer">
                    <span class="lbl">Tempo</span>
                    {{ elapsedTimeFormatted }}
                </div>

                <!-- Autosave indicator -->
                <div
                    v-if="isInProgress"
                    class="cp-hdr-save"
                    :class="{ 'is-saving': autoSaveStatus === 'saving', 'is-saved': autoSaveStatus === 'saved' }"
                >
                    <template v-if="autoSaveStatus === 'saving'">
                        <svg class="sv-icon animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                        </svg>
                        Salvando…
                    </template>
                    <template v-else-if="autoSaveStatus === 'saved'">
                        <svg class="sv-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Salvo
                    </template>
                    <template v-else-if="autoSaveStatus === 'error'">
                        <svg class="sv-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                        Erro ao salvar
                    </template>
                    <template v-else-if="hasUnsavedChanges">
                        <svg class="sv-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        Não salvo
                    </template>
                    <template v-else-if="lastSaved">
                        <svg class="sv-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Salvo às {{ formatTime(lastSaved) }}
                    </template>
                </div>

                <!-- Actions -->
                <div class="cp-hdr-actions">
                    <button v-if="isScheduled" class="cp-btn cp-btn-primary" @click="emit('start')">
                        Iniciar consulta
                        <kbd class="cp-kbd">⌘↵</kbd>
                    </button>

                    <button v-if="isInProgress" class="cp-btn" :disabled="isSaving" @click="emit('save')">
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        Salvar
                        <kbd class="cp-kbd">⌘S</kbd>
                    </button>

                    <button v-if="isInProgress" class="cp-btn cp-btn-ativa" @click="emit('finalize')">
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Finalizar consulta
                        <kbd class="cp-kbd">⌘↵</kbd>
                    </button>

                    <button v-if="isCompleted" class="cp-btn" @click="emit('pdf')">
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Gerar PDF
                    </button>

                    <button v-if="isCompleted" class="cp-btn" @click="emit('addComplement')">
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34" />
                            <polygon points="18 2 22 6 12 16 8 16 8 12 18 2" />
                        </svg>
                        Complementar
                    </button>

                    <button v-if="isInProgress || isCompleted" class="cp-btn cp-btn-icon" title="Mensagens com o paciente" @click="emit('messages')">
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <span v-if="msgCount && msgCount > 0" class="cp-msg-badge">{{ msgCount }}</span>
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.cp-hdr {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(18px) saturate(160%);
    -webkit-backdrop-filter: blur(18px) saturate(160%);
    border-bottom: 1px solid var(--cp-line, #e3eae9);
    box-shadow: 0 4px 16px -8px rgba(10, 31, 30, 0.08);
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}

.cp-hdr-accent-bar {
    height: 3px;
    background: var(--cp-accent, var(--cp-state-ativa-ink));
    transition: background 200ms ease;
}
.cp-hdr-accent-bar.is-ativa {
    background: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #0d9488 100%);
    background-size: 200% 100%;
    animation: cp-shimmer 4s ease-in-out infinite;
}

.cp-hdr-inner {
    max-width: 1480px;
    margin: 0 auto;
    padding: 8px 32px 12px;
}

.cp-hdr-row1 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 12.5px;
    color: var(--cp-ink-500, #5a726f);
}

.cp-hdr-back {
    appearance: none;
    background: transparent;
    border: 1px solid var(--cp-line, #e3eae9);
    color: var(--cp-ink-700, #1f3a38);
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    transition: all 120ms;
    flex-shrink: 0;
}
.cp-hdr-back:hover {
    background: var(--cp-ink-900, #0a1f1e);
    color: white;
    border-color: var(--cp-ink-900, #0a1f1e);
}

.cp-crumbs {
    display: flex;
    align-items: center;
    gap: 6px;
}
.cp-crumbs a {
    color: var(--cp-ink-500, #5a726f);
    text-decoration: none;
}
.cp-crumbs a:hover {
    color: var(--cp-ink-800, #14302e);
}
.cp-crumbs .sep {
    color: var(--cp-ink-300, #b7c5c3);
}
.cp-crumbs .now {
    color: var(--cp-ink-800, #14302e);
    font-weight: 600;
}

.cp-hdr-row2 {
    display: flex;
    align-items: center;
    gap: 16px;
}

.cp-hdr-id {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.cp-hdr-id h1 {
    margin: 0;
    font-size: 21px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--cp-ink-900, #0a1f1e);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cp-hdr-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--cp-ink-500, #5a726f);
}
.cp-hdr-meta .dot {
    width: 3px;
    height: 3px;
    background: var(--cp-ink-300, #b7c5c3);
    border-radius: 50%;
}

.cp-hdr-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px 5px 10px;
    border-radius: 999px;
    background: var(--cp-accent-soft, #ecfdf8);
    border: 1px solid var(--cp-accent-line, #b5e6dc);
    color: var(--cp-accent, #0f766e);
    font-weight: 600;
    font-size: 12.5px;
    white-space: nowrap;
}

.pulse {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
}
.pulse.is-ativa {
    background: #0d9488;
    animation: cp-pulse 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.cp-hdr-spacer {
    flex: 1;
}

.cp-hdr-timer {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    padding: 6px 12px;
    background: var(--cp-ink-900, #0a1f1e);
    color: white;
    border-radius: 8px;
    font-family: var(--cp-font-mono, monospace);
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.02em;
    font-variant-numeric: tabular-nums;
}
.cp-hdr-timer .lbl {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-family: var(--cp-font-sans, sans-serif);
}

.cp-hdr-save {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12.5px;
    color: var(--cp-ink-400, #8fa2a0);
    min-width: 100px;
}
.cp-hdr-save .sv-icon {
    width: 13px;
    height: 13px;
}
.cp-hdr-save.is-saving {
    color: var(--cp-amber-700, #b45309);
}
.cp-hdr-save.is-saved {
    color: var(--cp-green-600, #16a34a);
}

.cp-hdr-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.cp-btn {
    appearance: none;
    border: 1px solid var(--cp-line-strong, #c8d4d2);
    background: var(--cp-surface, #fff);
    color: var(--cp-ink-800, #14302e);
    padding: 8px 13px;
    border-radius: 9px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 120ms ease;
    letter-spacing: -0.005em;
    line-height: 1;
    font-family: var(--cp-font-sans, sans-serif);
}
.cp-btn:hover {
    border-color: var(--cp-ink-700, #1f3a38);
    background: var(--cp-ink-900, #0a1f1e);
    color: white;
}
.cp-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.cp-btn-primary {
    background: var(--cp-ink-900, #0a1f1e);
    border-color: var(--cp-ink-900, #0a1f1e);
    color: white;
}
.cp-btn-primary:hover {
    background: var(--cp-teal-700, #0f766e);
    border-color: var(--cp-teal-700, #0f766e);
}

.cp-btn-ativa {
    background: var(--cp-teal-700, #0f766e);
    border-color: var(--cp-teal-700, #0f766e);
    color: white;
}
.cp-btn-ativa:hover {
    background: var(--cp-teal-800, #0b5953);
    border-color: var(--cp-teal-800, #0b5953);
}

.cp-btn-icon {
    padding: 0;
    width: 34px;
    height: 34px;
    justify-content: center;
    position: relative;
}

.cp-msg-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #dc2626;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 999px;
    border: 2px solid white;
    font-family: var(--cp-font-sans, sans-serif);
}

.cp-kbd {
    font-family: var(--cp-font-mono, monospace);
    font-size: 10.5px;
    padding: 2px 5px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 4px;
    color: rgba(255, 255, 255, 0.7);
    background: rgba(255, 255, 255, 0.1);
    letter-spacing: 0;
}
.cp-btn:not(.cp-btn-primary):not(.cp-btn-ativa) .cp-kbd {
    border-color: var(--cp-line, #e3eae9);
    color: var(--cp-ink-400, #8fa2a0);
    background: var(--cp-surface-2, #fafbfb);
}
.cp-btn:hover .cp-kbd {
    border-color: rgba(255, 255, 255, 0.25);
    color: rgba(255, 255, 255, 0.7);
    background: rgba(255, 255, 255, 0.1);
}

@media (max-width: 1100px) {
    .cp-hdr-inner {
        padding: 8px 16px 10px;
    }
    .cp-hdr-meta {
        display: none;
    }
}
</style>
