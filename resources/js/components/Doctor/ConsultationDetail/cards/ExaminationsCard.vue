<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';

const props = defineProps<{
    collapsed: boolean;
    examinations: Array<Record<string, unknown>>;
    mode: 'scheduled' | 'in_progress' | 'completed';
}>();

const emit = defineEmits<{ toggle: [] }>();

function getPartnerName(partner: unknown): string {
    return (partner as Record<string, string>)?.name ?? '';
}

function isResultReady(exam: Record<string, unknown>): boolean {
    return (exam.status as string) === 'completed' || (exam.results as unknown[])?.length > 0;
}
</script>

<template>
    <CollapsibleCard
        id="examinations-card"
        num="5"
        title="Exames solicitados"
        :hint="props.mode === 'in_progress' ? 'Resultados integrados automaticamente' : undefined"
        :collapsed="collapsed"
        @toggle="emit('toggle')"
    >
        <div v-if="examinations.length === 0" class="cp-field-empty">— Nenhum exame solicitado —</div>
        <div v-else class="cp-exm-list">
            <div v-for="(exam, i) in examinations" :key="String(exam.id)" class="cp-rx-item">
                <span class="cp-rx-num">{{ String(i + 1).padStart(2, '0') }}</span>
                <div class="cp-rx-main">
                    <div class="cp-rx-name">{{ exam.name }}</div>
                    <div class="cp-exm-result" :class="{ 'is-ready': isResultReady(exam) }">
                        <template v-if="isResultReady(exam)">
                            <svg
                                width="11"
                                height="11"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                style="display: inline; vertical-align: -1px; margin-right: 4px"
                            >
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            Resultado recebido
                        </template>
                        <template v-else>
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
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            {{ exam.status }} · {{ exam.priority }}
                        </template>
                    </div>
                </div>
                <div v-if="exam.source === 'integration' && getPartnerName(exam.partner)" class="cp-rx-tag is-partner">
                    Parceiro · {{ getPartnerName(exam.partner) }}
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
                Resultados de parceiros integrados são anexados automaticamente.
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

.cp-exm-list {
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
.cp-exm-result {
    font-size: 11.5px;
    color: var(--cp-ink-400, #8fa2a0);
    margin-top: 2px;
}
.cp-exm-result.is-ready {
    color: var(--cp-green-600, #16a34a);
    font-weight: 600;
}

.cp-rx-tag {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 2px 7px;
    border-radius: 4px;
    background: var(--cp-teal-50, #ecfdf8);
    color: var(--cp-teal-800, #0b5953);
    border: 1px solid var(--cp-teal-100, #ccfbf1);
    white-space: nowrap;
    flex-shrink: 0;
}
.cp-rx-tag.is-partner {
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}

.cp-card-footer-action {
    display: flex;
    align-items: center;
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
