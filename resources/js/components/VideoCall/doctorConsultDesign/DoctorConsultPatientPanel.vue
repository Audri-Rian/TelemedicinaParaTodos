<script setup lang="ts">
import type { ConsultPatient } from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import { Activity, AlertTriangle, ChevronRight, Droplets, FlaskConical, HeartPulse, Thermometer } from 'lucide-vue-next';

defineProps<{
    patient: ConsultPatient;
}>();

const emit = defineEmits<{
    openHistory: [];
}>();

const vitalIcon = (key: string) => {
    if (key === 'pa') return HeartPulse;
    if (key === 'fc') return Activity;
    if (key === 'tax') return Thermometer;
    return Droplets;
};
</script>

<template>
    <div class="patient">
        <div class="patient-hero">
            <div class="patient-av">{{ patient.initials }}</div>
            <div style="min-width: 0">
                <h3 class="patient-name">{{ patient.name }}</h3>
                <div class="patient-meta">{{ patient.age }} anos · {{ patient.gender }} · {{ patient.pronoun }}</div>
                <div class="patient-meta" style="margin-top: 2px">
                    Tipo sanguíneo <strong style="color: var(--foreground)">{{ patient.bloodType }}</strong> · CPF
                    {{ patient.cpf }}
                </div>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title" style="color: var(--destructive)">
                    <AlertTriangle class="mr-1 inline h-3 w-3" />
                    Alergias
                </span>
                <button type="button" class="pat-add">Editar</button>
            </div>
            <div class="chip-list">
                <span v-for="a in patient.allergies" :key="a" class="alert-chip">
                    <AlertTriangle class="h-3 w-3" />
                    {{ a }}
                </span>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Condições</span>
                <button type="button" class="pat-add">Editar</button>
            </div>
            <div class="chip-list">
                <span v-for="c in patient.conditions" :key="c" class="neutral-chip">{{ c }}</span>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Medicações em uso</span>
                <button type="button" class="pat-add">+ Adicionar</button>
            </div>
            <div>
                <div v-for="(m, i) in patient.medications" :key="i" class="med-row">
                    <div class="med-dot" />
                    <div style="min-width: 0">
                        <div class="med-name">{{ m.name }}</div>
                        <div class="med-dose">{{ m.dose }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Sinais vitais · informados</span>
                <button type="button" class="pat-add">Atualizar</button>
            </div>
            <div class="vitals">
                <div v-for="key in ['pa', 'fc', 'tax', 'sat'] as const" :key="key" class="vital">
                    <div class="vital-lbl" style="display: flex; align-items: center; gap: 5px; color: var(--muted-foreground)">
                        <component :is="vitalIcon(key)" class="h-3.5 w-3.5" />
                        {{ patient.vitals[key].label }}
                    </div>
                    <div class="vital-val">
                        {{ patient.vitals[key].value }}
                        <span class="unit">{{ patient.vitals[key].unit }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Queixa principal</span>
            </div>
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
                {{ patient.chiefComplaint }}
            </div>
        </div>

        <div class="pat-section" style="border-bottom: 0">
            <div class="pat-head">
                <span class="pat-title">Histórico recente</span>
                <button type="button" class="pat-add" @click="emit('openHistory')">Ver tudo</button>
            </div>
            <div>
                <div v-for="h in patient.history" :key="h.id" class="history-row">
                    <div class="ico">
                        <FlaskConical v-if="h.icon === 'flask'" class="h-3.5 w-3.5" />
                        <Activity v-else class="h-3.5 w-3.5" />
                    </div>
                    <div style="min-width: 0; flex: 1">
                        <div class="h-title">{{ h.title }}</div>
                        <div class="h-meta">{{ h.date }} · {{ h.who }}</div>
                    </div>
                    <span class="h-chev"><ChevronRight class="h-3.5 w-3.5" /></span>
                </div>
            </div>
        </div>
    </div>
</template>
