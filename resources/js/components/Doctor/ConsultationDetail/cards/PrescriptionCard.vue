<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';

const props = defineProps<{
    collapsed: boolean;
    prescriptions: Array<Record<string, unknown>>;
    mode: 'scheduled' | 'in_progress' | 'completed';
}>();

const emit = defineEmits<{ toggle: [] }>();

function getMedicationNames(medications: unknown): string {
    const meds = medications as Array<Record<string, string>> | undefined;
    return meds?.map((m) => m.name).join(', ') ?? '';
}
</script>

<template>
    <CollapsibleCard
        id="prescription-card"
        num="4"
        title="Prescrição"
        :hint="props.mode === 'in_progress' ? 'Gerencie pelo módulo de receitas' : undefined"
        :collapsed="collapsed"
        @toggle="emit('toggle')"
    >
        <div v-if="prescriptions.length === 0" class="cp-field-empty">— Nenhuma prescrição registrada —</div>
        <div v-else class="cp-rx-list">
            <div v-for="(rx, i) in prescriptions" :key="String(rx.id)" class="cp-rx-item">
                <span class="cp-rx-num">{{ String(i + 1).padStart(2, '0') }}</span>
                <div class="cp-rx-main">
                    <div class="cp-rx-name">{{ getMedicationNames(rx.medications) }}</div>
                    <div v-if="rx.instructions" class="cp-rx-posol">{{ rx.instructions }}</div>
                </div>
            </div>
        </div>
        <div v-if="props.mode !== 'completed'" class="cp-card-footer-action">
            <span class="info">
                <svg
                    width="11"
                    height="11"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    style="display: inline; vertical-align: -1px; margin-right: 4px"
                >
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12.01" y2="8" />
                </svg>
                Prescrições são gerenciadas no módulo de receitas para garantir assinatura digital.
            </span>
        </div>
    </CollapsibleCard>
</template>

<style scoped>
.cp-field-empty {
    font-style: italic;
    color: var(--cp-ink-400, #8fa2a0);
    padding: 4px 0;
    font-family: var(--cp-font-sans, sans-serif);
}

.cp-rx-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.cp-rx-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 11px 14px;
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: 10px;
    background: var(--cp-surface, #fff);
    font-family: var(--cp-font-sans, sans-serif);
}
.cp-rx-num {
    font-family: var(--cp-font-mono, monospace);
    font-size: 11px;
    color: var(--cp-ink-400, #8fa2a0);
    background: var(--cp-surface-2, #fafbfb);
    border-radius: 5px;
    padding: 2px 6px;
    flex-shrink: 0;
    margin-top: 1px;
}
.cp-rx-main {
    flex: 1;
    min-width: 0;
}
.cp-rx-name {
    font-weight: 600;
    color: var(--cp-ink-900, #0a1f1e);
    font-size: 13.5px;
    letter-spacing: -0.005em;
}
.cp-rx-posol {
    font-size: 12.5px;
    color: var(--cp-ink-500, #5a726f);
    margin-top: 2px;
}

.cp-card-footer-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 8px;
    border-top: 1px dashed var(--cp-line, #e3eae9);
    margin-top: 2px;
    font-family: var(--cp-font-sans, sans-serif);
}
.info {
    font-size: 12px;
    color: var(--cp-ink-500, #5a726f);
}
</style>
