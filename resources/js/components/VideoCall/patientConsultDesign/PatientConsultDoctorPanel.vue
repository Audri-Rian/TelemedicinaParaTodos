<script setup lang="ts">
import type { PatientConsultDoctor } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Calendar, Languages, MapPin, Shield, Star } from 'lucide-vue-next';

defineProps<{
    doctor: PatientConsultDoctor;
}>();

const emit = defineEmits<{
    book: [];
}>();
</script>

<template>
    <div class="patient">
        <div class="patient-hero">
            <div class="patient-av">{{ doctor.initials }}</div>
            <div style="min-width: 0">
                <h3 class="patient-name">{{ doctor.name }}</h3>
                <div class="patient-meta">{{ doctor.specialty }} · {{ doctor.yearsActive }} anos de atuação</div>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px">
                    <span
                        style="
                            font-size: 11px;
                            font-weight: 500;
                            padding: 2px 8px;
                            border-radius: 999px;
                            background: var(--primary-50);
                            color: var(--primary-800);
                            border: 1px solid #99f6e4;
                            display: inline-flex;
                            align-items: center;
                            gap: 4px;
                        "
                    >
                        <Shield class="h-3 w-3" />
                        Verificado
                    </span>
                    <span style="font-size: 12px; color: var(--muted-foreground); display: inline-flex; align-items: center; gap: 4px">
                        <Star class="h-3 w-3 text-amber-500" />
                        {{ doctor.rating.toFixed(1) }} · {{ doctor.reviews }} avaliações
                    </span>
                </div>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-title" style="margin-bottom: 8px">Registro profissional</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px">
                <div style="padding: 10px 12px; border-radius: 10px; background: var(--muted); border: 1px solid var(--border)">
                    <div class="vital-lbl">CRM</div>
                    <div style="font-size: 13.5px; font-weight: 500; margin-top: 2px">{{ doctor.crm }}</div>
                </div>
                <div style="padding: 10px 12px; border-radius: 10px; background: var(--muted); border: 1px solid var(--border)">
                    <div class="vital-lbl">RQE</div>
                    <div style="font-size: 13.5px; font-weight: 500; margin-top: 2px">{{ doctor.rqe }}</div>
                </div>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-title" style="margin-bottom: 8px">Sobre</div>
            <div style="font-size: 13.5px; line-height: 1.55; color: var(--foreground)">{{ doctor.bio }}</div>
        </div>

        <div class="pat-section">
            <div class="pat-title" style="margin-bottom: 8px">Idiomas</div>
            <div class="chip-list">
                <span v-for="lang in doctor.languages" :key="lang" class="neutral-chip">
                    <Languages class="h-3 w-3" />
                    {{ lang }}
                </span>
            </div>
        </div>

        <div class="pat-section">
            <div class="pat-title" style="margin-bottom: 8px">Local de atendimento presencial</div>
            <div style="display: flex; align-items: center; gap: 10px">
                <div
                    style="
                        width: 32px;
                        height: 32px;
                        border-radius: 8px;
                        background: var(--primary-50);
                        color: var(--primary-800);
                        display: grid;
                        place-items: center;
                        flex-shrink: 0;
                    "
                >
                    <MapPin class="h-3.5 w-3.5" />
                </div>
                <div style="font-size: 13px">{{ doctor.clinic }}</div>
            </div>
        </div>

        <div class="pat-section" style="border-bottom: 0">
            <div class="pat-title" style="margin-bottom: 8px">Agendar retorno</div>
            <div
                style="
                    padding: 14px;
                    border-radius: 12px;
                    border: 1px solid var(--border);
                    background: white;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                "
            >
                <div
                    style="
                        width: 38px;
                        height: 38px;
                        border-radius: 10px;
                        background: var(--primary-50);
                        color: var(--primary-800);
                        display: grid;
                        place-items: center;
                        flex-shrink: 0;
                    "
                >
                    <Calendar class="h-4 w-4" />
                </div>
                <div style="min-width: 0; flex: 1">
                    <div style="font-size: 13px; font-weight: 500">Próximo horário</div>
                    <div style="font-size: 12px; color: var(--muted-foreground)">{{ doctor.nextSlot }}</div>
                </div>
                <button type="button" class="act-btn primary" style="height: 32px; padding: 0 12px; font-size: 12px" @click="emit('book')">
                    Agendar
                </button>
            </div>
        </div>
    </div>
</template>
