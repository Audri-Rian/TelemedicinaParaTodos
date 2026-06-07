<script setup lang="ts">
import CID10Autocomplete from '@/components/CID10Autocomplete.vue';
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';

const diagnosisModel = defineModel<string>('diagnosis', { required: true });
const cid10Model = defineModel<string>('cid10', { required: true });

defineProps<{ collapsed: boolean; locked?: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard
        id="diagnosis-card"
        num="3"
        title="Diagnóstico"
        kbd="⌘3"
        :hint="!locked ? 'CID-10 + impressão clínica' : undefined"
        :locked="locked"
        :collapsed="collapsed"
        @toggle="emit('toggle')"
    >
        <template #title-extra>
            <span v-if="cid10Model" class="cp-cid-tag" :class="{ 'is-locked': locked }">{{ cid10Model }}</span>
        </template>

        <!-- Locked view -->
        <div v-if="locked" class="cp-diag-locked">
            <div v-if="cid10Model || diagnosisModel" class="cp-diag-locked-row">
                <span v-if="cid10Model" class="cp-cid-tag is-locked">{{ cid10Model }}</span>
                <span v-if="diagnosisModel" class="cp-diag-desc-locked">{{ diagnosisModel }}</span>
            </div>
            <div v-if="!cid10Model && !diagnosisModel" class="cp-field-empty">— Sem CID registrado —</div>
        </div>

        <!-- Edit view -->
        <template v-else>
            <div class="cp-diag-row">
                <div class="cp-cid-search">
                    <CID10Autocomplete v-model="cid10Model" placeholder="CID-10…" @select="emit('change')" />
                </div>
                <div class="cp-diag-desc-wrap">
                    <textarea
                        v-model="diagnosisModel"
                        class="cp-field"
                        placeholder="Impressão clínica, hipóteses, raciocínio…"
                        rows="2"
                        maxlength="500"
                        @input="emit('change')"
                    />
                </div>
            </div>
            <div class="cp-field-foot">
                <span />
                <span class="char-count" :class="{ 'is-warn': diagnosisModel.length / 500 > 0.9 }"> {{ diagnosisModel.length }} / 500 </span>
            </div>
        </template>
    </CollapsibleCard>
</template>

<style scoped>
.cp-diag-row {
    display: flex;
    gap: 10px;
    align-items: stretch;
}
.cp-cid-search {
    flex: 0 0 200px;
}
.cp-diag-desc-wrap {
    flex: 1;
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: 10px;
    padding: 10px 14px;
    transition:
        border-color 150ms,
        box-shadow 150ms;
}
.cp-diag-desc-wrap:focus-within {
    border-color: var(--cp-teal-500, #14b8a6);
    box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14);
}
.cp-field {
    display: block;
    width: 100%;
    background: transparent;
    border: 0;
    resize: vertical;
    font-size: 14.5px;
    color: var(--cp-ink-900, #0a1f1e);
    line-height: 1.55;
    padding: 0;
    outline: 0;
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
    min-height: 0;
}
.cp-field::placeholder {
    color: var(--cp-ink-300, #b7c5c3);
    font-style: italic;
}

.cp-cid-tag {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 6px;
    background: var(--cp-teal-50, #ecfdf8);
    color: var(--cp-teal-800, #0b5953);
    font-family: var(--cp-font-mono, monospace);
    font-size: 12px;
    font-weight: 600;
    border: 1px solid var(--cp-teal-100, #ccfbf1);
}

.cp-diag-locked {
    padding: 2px 0;
}
.cp-diag-locked-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.cp-diag-desc-locked {
    font-size: 14px;
    color: var(--cp-ink-700, #1f3a38);
    font-weight: 500;
    white-space: pre-wrap;
}
.cp-field-empty {
    font-style: italic;
    color: var(--cp-ink-400, #8fa2a0);
    padding: 4px 0;
}

.cp-field-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    color: var(--cp-ink-400, #8fa2a0);
}
.char-count {
    font-variant-numeric: tabular-nums;
}
.char-count.is-warn {
    color: var(--cp-amber-700, #b45309);
}
</style>
