<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useMedicalRecordExport } from '@/composables/Patient/useMedicalRecordExport';
import { useFormatters } from '@/composables/useFormatters';
import { useInitials } from '@/composables/useInitials';
import type { FilterState, PatientProfile } from '@/types/medical-records';
import { AlertCircle, Download, Loader2, ShieldCheck } from 'lucide-vue-next';

const props = defineProps<{
    patient: PatientProfile;
    filtersState: FilterState;
}>();

const { getInitials } = useInitials();
const { formatDate, formatGender } = useFormatters();
const { isExporting, exportRecord, flashStatus, exportError } = useMedicalRecordExport(props.filtersState);
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-[#dde5ea] bg-white shadow-sm">
        <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1fr)_280px] xl:items-start">
            <div class="flex gap-4">
                <Avatar class="h-20 w-20 shrink-0 border border-[#dde5ea]">
                    <AvatarImage v-if="patient.user.avatar" :src="patient.user.avatar" :alt="patient.user.name" />
                    <AvatarFallback class="bg-[#e5f1f2] text-lg font-black text-[#0f6e78]">
                        {{ getInitials(patient.user.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="min-w-0">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-[#dde5ea] bg-[#f4f6f8] px-3 py-1 text-xs font-black text-gray-600"
                    >
                        <ShieldCheck class="h-3.5 w-3.5 text-[#0f6e78]" />
                        Acesso protegido pela LGPD
                    </div>
                    <h1 class="mt-2 text-3xl font-black text-gray-950">{{ patient.user.name }}</h1>
                    <div class="mt-2 flex flex-wrap gap-2 text-sm font-semibold text-gray-600">
                        <span v-if="patient.age" class="rounded-full bg-[#f4f6f8] px-3 py-1">Idade: {{ patient.age }}</span>
                        <span v-if="patient.date_of_birth" class="rounded-full bg-[#f4f6f8] px-3 py-1"
                            >DN: {{ formatDate(patient.date_of_birth) }}</span
                        >
                        <span class="rounded-full bg-[#f4f6f8] px-3 py-1">Sexo: {{ formatGender(patient.gender) }}</span>
                        <span v-if="patient.blood_type" class="rounded-full bg-[#f4f6f8] px-3 py-1">Sangue: {{ patient.blood_type }}</span>
                    </div>
                    <p class="mt-3 max-w-4xl text-sm leading-6 font-medium text-gray-600">
                        Histórico médico: {{ patient.medical_history || 'Não informado' }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <Button class="h-11 w-full bg-[#0f6e78] font-black text-white hover:bg-[#0a4f57]" :disabled="isExporting" @click="exportRecord">
                    <Loader2 v-if="isExporting" class="mr-2 h-4 w-4 animate-spin" />
                    <Download v-else class="mr-2 h-4 w-4" />
                    {{ isExporting ? 'Solicitando...' : 'Solicitar exportação PDF' }}
                </Button>
                <p v-if="flashStatus" class="text-xs font-semibold text-emerald-700">{{ flashStatus }}</p>
                <p v-if="exportError" class="flex items-center gap-1 text-xs font-semibold text-rose-700">
                    <AlertCircle class="h-4 w-4" />
                    {{ exportError }}
                </p>
            </div>
        </div>

        <slot name="metrics" />
    </section>
</template>
