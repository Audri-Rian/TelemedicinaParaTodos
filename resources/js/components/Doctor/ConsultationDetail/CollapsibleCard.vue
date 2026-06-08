<script setup lang="ts">
defineProps<{
    id?: string;
    num: string;
    title: string;
    icon?: string;
    kbd?: string;
    hint?: string;
    locked?: boolean;
    collapsed: boolean;
}>();

const emit = defineEmits<{ toggle: [] }>();
</script>

<template>
    <section :id="id" class="cp-card" :class="{ 'is-locked': locked, 'is-collapsed': collapsed }">
        <header class="cp-card-head" @click="emit('toggle')">
            <svg
                class="cp-card-chevron"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <polyline points="6 9 12 15 18 9" />
            </svg>
            <h3 class="cp-card-title">
                <span class="num">{{ num }}</span>
                {{ title }}
                <slot name="title-extra" />
            </h3>
            <div class="cp-card-meta">
                <span v-if="hint && !locked" class="hint">{{ hint }}</span>
                <span v-if="kbd && !locked" class="kbd-hint" :title="`Atalho: ${kbd}`">{{ kbd }}</span>
                <span v-if="locked" class="lock-label">
                    <svg
                        width="11"
                        height="11"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    somente leitura
                </span>
            </div>
        </header>
        <div v-if="!collapsed" class="cp-card-body">
            <slot />
        </div>
    </section>
</template>

<style scoped>
.cp-card {
    background: var(--cp-surface, #fff);
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: var(--cp-r-lg, 14px);
    box-shadow: var(--cp-shadow-card, 0 1px 2px rgba(15, 41, 39, 0.04));
    overflow: hidden;
    transition: border-color 200ms;
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}

.cp-card.is-locked {
    background: var(--cp-surface-locked, #f1f4f4);
}

.cp-card-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 22px;
    cursor: pointer;
    user-select: none;
}
.cp-card-head:hover .cp-card-title {
    color: var(--cp-teal-700, #0f766e);
}

.cp-card-chevron {
    color: var(--cp-ink-400, #8fa2a0);
    transition: transform 180ms ease;
    flex-shrink: 0;
}
.cp-card.is-collapsed .cp-card-chevron {
    transform: rotate(-90deg);
}

.cp-card-title {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--cp-ink-900, #0a1f1e);
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 120ms;
}
.cp-card-title .num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 6px;
    background: var(--cp-surface-2, #fafbfb);
    color: var(--cp-ink-500, #5a726f);
    font-size: 11px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}

.cp-card-meta {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11.5px;
    color: var(--cp-ink-400, #8fa2a0);
}
.cp-card-meta .hint {
    color: var(--cp-ink-400, #8fa2a0);
}
.cp-card-meta .kbd-hint {
    font-family: var(--cp-font-mono, monospace);
    font-size: 10.5px;
    padding: 2px 6px;
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: 5px;
    color: var(--cp-ink-500, #5a726f);
    background: var(--cp-surface-2, #fafbfb);
    letter-spacing: 0;
}
.lock-label {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    color: var(--cp-ink-400, #8fa2a0);
    font-weight: 500;
}

.cp-card-body {
    padding: 6px 22px 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>
