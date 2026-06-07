<script setup lang="ts">
import type { ConsultPatient } from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import { Activity, AlertTriangle, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    patient: ConsultPatient;
}>();

const emit = defineEmits<{
    openHistory: [];
}>();

const GENDER_LABELS: Record<string, string> = {
    male: 'Masculino',
    female: 'Feminino',
    other: 'Outro',
};

const genderLabel = () => (props.patient.gender ? (GENDER_LABELS[props.patient.gender] ?? props.patient.gender) : null);

const heroMeta = () =>
    [props.patient.age !== null ? `${props.patient.age} anos` : null, genderLabel()].filter(Boolean).join(' · ') || 'Dados não informados';
</script>

<template>
    <div class="patient">
        <div class="patient-hero">
            <div class="patient-av">{{ patient.initials }}</div>
            <div style="min-width: 0">
                <h3 class="patient-name">{{ patient.name }}</h3>
                <div class="patient-meta">{{ heroMeta() }}</div>
                <div v-if="patient.bloodType" class="patient-meta" style="margin-top: 2px">
                    Tipo sanguíneo <strong style="color: var(--foreground)">{{ patient.bloodType }}</strong>
                </div>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title" style="color: var(--destructive)">
                    <AlertTriangle class="mr-1 inline h-3 w-3" />
                    Alergias
                </span>
            </div>
            <div v-if="patient.allergies.length" class="chip-list">
                <span v-for="a in patient.allergies" :key="a" class="alert-chip">
                    <AlertTriangle class="h-3 w-3" />
                    {{ a }}
                </span>
            </div>
            <div v-else style="font-size: 12.5px; color: var(--muted-foreground)">Nenhuma alergia registrada.</div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Histórico médico</span>
            </div>
            <div v-if="patient.conditions" style="font-size: 13px; line-height: 1.5; color: var(--foreground)">
                {{ patient.conditions }}
            </div>
            <div v-else style="font-size: 12.5px; color: var(--muted-foreground)">Nenhuma condição registrada.</div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Medicações em uso</span>
            </div>
            <div v-if="patient.medications.length">
                <div v-for="(m, i) in patient.medications" :key="i" class="med-row">
                    <div class="med-dot" />
                    <div style="min-width: 0">
                        <div class="med-name">{{ m }}</div>
                    </div>
                </div>
            </div>
            <div v-else style="font-size: 12.5px; color: var(--muted-foreground)">Nenhuma medicação registrada.</div>
        </div>

        <div class="pat-section">
            <div class="pat-head">
                <span class="pat-title">Motivo da consulta</span>
            </div>
            <div
                v-if="patient.chiefComplaint"
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
            <div v-else style="font-size: 12.5px; color: var(--muted-foreground)">Sem registro de queixa para esta consulta.</div>
        </div>

        <div class="pat-section" style="border-bottom: 0">
            <div class="pat-head">
                <span class="pat-title">Histórico recente</span>
                <button type="button" class="pat-add" @click="emit('openHistory')">Ver prontuário</button>
            </div>
            <div v-if="patient.history.length">
                <div v-for="h in patient.history" :key="h.id" class="history-row">
                    <div class="ico">
                        <Activity class="h-3.5 w-3.5" />
                    </div>
                    <div style="min-width: 0; flex: 1">
                        <div class="h-title">{{ h.title }}</div>
                        <div class="h-meta">
                            {{ h.date }}<template v-if="h.summary"> · {{ h.summary }}</template>
                        </div>
                    </div>
                    <span class="h-chev"><ChevronRight class="h-3.5 w-3.5" /></span>
                </div>
            </div>
            <div v-else style="font-size: 12.5px; color: var(--muted-foreground)">Nenhuma consulta anterior com você.</div>
        </div>
    </div>
</template>
