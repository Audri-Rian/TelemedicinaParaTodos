<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';

const model = defineModel<string>({ required: true });

defineProps<{ collapsed: boolean; locked?: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard
        id="instructions-card"
        num="7"
        title="Orientações ao paciente"
        kbd="⌘7"
        :hint="!locked ? 'Vai para o paciente no PDF' : undefined"
        :locked="locked"
        :collapsed="collapsed"
        @toggle="emit('toggle')"
    >
        <div v-if="locked" class="cp-field-locked">
            <div v-if="model" class="cp-field-text">{{ model }}</div>
            <div v-else class="cp-field-empty">— Sem registro —</div>
        </div>
        <template v-else>
            <div class="cp-field-wrap">
                <textarea
                    v-model="model"
                    class="cp-field"
                    placeholder="Em linguagem simples: o que fazer, quando retornar, sinais de alerta…"
                    rows="4"
                    maxlength="2000"
                    @input="emit('change')"
                />
            </div>
            <div class="cp-field-foot">
                <span />
                <span class="char-count" :class="{ 'is-warn': model.length / 2000 > 0.9 }"> {{ model.length.toLocaleString('pt-BR') }} / 2.000 </span>
            </div>
        </template>
    </CollapsibleCard>
</template>

<style scoped>
.cp-field-wrap {
    background: var(--cp-surface, #fff);
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: 10px;
    padding: 14px 16px;
    transition:
        border-color 150ms,
        box-shadow 150ms;
}
.cp-field-wrap:focus-within {
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
    min-height: 70px;
    letter-spacing: -0.005em;
}
.cp-field::placeholder {
    color: var(--cp-ink-300, #b7c5c3);
    font-style: italic;
}

.cp-field-locked {
    padding: 2px 0;
}
.cp-field-text {
    font-size: 14.5px;
    color: var(--cp-ink-700, #1f3a38);
    line-height: 1.55;
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
