<script setup lang="ts">
import type { ConsultationMode } from '@/types/consultation-detail';
import { computed } from 'vue';

const props = defineProps<{ mode: ConsultationMode; patientName: string }>();

const content = computed(() => {
    if (props.mode === 'scheduled') {
        return {
            icon: 'clock',
            title: 'Consulta agendada',
            body: `Revise alergias e medicações antes de iniciar o atendimento de ${props.patientName}.`,
        };
    }
    if (props.mode === 'in_progress') {
        return {
            icon: 'activity',
            title: 'Consulta em andamento',
            body: 'Todos os campos são salvos automaticamente. Finalize quando o atendimento estiver concluído.',
        };
    }
    return {
        icon: 'lock',
        title: 'Prontuário selado',
        body: 'Este prontuário está finalizado e é imutável. Use a seção de notas complementares para adendos pós-consulta.',
    };
});
</script>

<template>
    <div class="cp-state-banner-wrap">
        <div class="cp-state-banner">
            <!-- clock icon -->
            <svg
                v-if="content.icon === 'clock'"
                class="icn"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
            <!-- activity icon -->
            <svg
                v-else-if="content.icon === 'activity'"
                class="icn"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
            <!-- lock icon -->
            <svg
                v-else
                class="icn"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            <span class="body"
                ><b>{{ content.title }}</b> — {{ content.body }}</span
            >
        </div>
    </div>
</template>

<style scoped>
.cp-state-banner-wrap {
    max-width: 1480px;
    margin: 0 auto;
    padding: 14px 32px 0;
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}

.cp-state-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    background: var(--cp-accent-soft, #ecfdf8);
    border: 1px solid var(--cp-accent-line, #b5e6dc);
    border-radius: 10px;
    font-size: 13px;
    color: var(--cp-accent, #0f766e);
}

.icn {
    flex-shrink: 0;
}

.body {
    color: var(--cp-ink-700, #1f3a38);
    font-weight: 500;
    letter-spacing: -0.005em;
}
.body b {
    color: var(--cp-accent, #0f766e);
    font-weight: 700;
}

@media (max-width: 1100px) {
    .cp-state-banner-wrap {
        padding: 12px 16px 0;
    }
}
</style>
