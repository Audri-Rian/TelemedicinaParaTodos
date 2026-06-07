<script setup lang="ts">
import AppointmentStatusBadge from '@/components/AppointmentStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useFormatters } from '@/composables/useFormatters';
import { useInitials } from '@/composables/useInitials';
import { detail as doctorConsultationDetail } from '@/routes/doctor/consultations';
import * as patientRoutes from '@/routes/patient';
import type { Appointment } from '@/types/medical-records';
import { Link } from '@inertiajs/vue3';
import { Calendar, CalendarClock, Clock, ExternalLink, Stethoscope, Video } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    appointment: Appointment | null;
    isDoctorMode?: boolean;
}>();

const emit = defineEmits<{ close: [] }>();

const { formatDatePortuguese, formatTime } = useFormatters();
const { getInitials } = useInitials();

const doctorName = computed(() => props.appointment?.doctor.user.name ?? '—');
const doctorAvatar = computed(() => props.appointment?.doctor.user.avatar ?? null);
const specialization = computed(() => props.appointment?.doctor.specializations?.[0]?.name ?? 'Especialidade não informada');

const formattedDateLong = computed(() => {
    if (!props.appointment?.scheduled_at) return '—';
    return formatDatePortuguese(props.appointment.scheduled_at);
});

const formattedTime = computed(() => {
    if (!props.appointment?.scheduled_at) return '—';
    return formatTime(new Date(props.appointment.scheduled_at));
});

const relativeLabel = computed(() => {
    if (!props.appointment?.scheduled_at) return null;
    const date = new Date(props.appointment.scheduled_at);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const apptDay = new Date(date);
    apptDay.setHours(0, 0, 0, 0);
    const diffDays = Math.round((apptDay.getTime() - today.getTime()) / 86_400_000);
    if (diffDays === 0) return 'Hoje';
    if (diffDays === 1) return 'Amanhã';
    if (diffDays > 1 && diffDays <= 7) return `Em ${diffDays} dias`;
    return null;
});

const detailsHref = computed(() => {
    if (!props.appointment) return '#';
    if (props.isDoctorMode) {
        return doctorConsultationDetail({ appointment: props.appointment.id }).url;
    }
    return patientRoutes.consultationDetails({ appointment: props.appointment.id }).url;
});

const canJoinVideo = computed(() => {
    if (props.isDoctorMode || !props.appointment) return false;
    return ['scheduled', 'rescheduled', 'in_progress'].includes(props.appointment.status);
});

const hasNotes = computed(() => Boolean(props.appointment?.notes?.trim()));
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (open) => {
                if (!open) emit('close');
            }
        "
    >
        <DialogContent v-if="appointment" class="flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg">
            <div class="border-b border-[#dde5ea] bg-gradient-to-br from-[#e5f1f2] via-white to-white px-6 pt-6 pr-12 pb-5">
                <DialogHeader class="space-y-3 text-left">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <Avatar class="h-14 w-14 shrink-0 border-2 border-white shadow-sm">
                                <AvatarImage v-if="doctorAvatar" :src="doctorAvatar" :alt="doctorName" />
                                <AvatarFallback class="bg-[#0f6e78]/10 text-base font-black text-[#0f6e78]">
                                    {{ getInitials(doctorName) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <DialogTitle class="text-lg font-black text-gray-950">{{ doctorName }}</DialogTitle>
                                <p class="mt-0.5 flex items-center gap-1.5 text-sm font-semibold text-gray-600">
                                    <Stethoscope class="h-4 w-4 shrink-0 text-[#0f6e78]" />
                                    <span class="truncate">{{ specialization }}</span>
                                </p>
                            </div>
                        </div>
                        <AppointmentStatusBadge :status="appointment.status" class="shrink-0" />
                    </div>
                    <p
                        v-if="relativeLabel"
                        class="inline-flex w-fit items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-800 ring-1 ring-amber-200"
                    >
                        <CalendarClock class="h-3.5 w-3.5" />
                        {{ relativeLabel }}
                    </p>
                </DialogHeader>
            </div>

            <div class="space-y-4 overflow-y-auto px-6 py-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-[#dde5ea] bg-[#f7f8f9] p-3">
                        <p class="text-xs font-black tracking-wide text-gray-500 uppercase">Data e hora</p>
                        <p class="mt-1.5 text-sm font-bold text-gray-950">{{ formattedDateLong }}</p>
                    </div>
                    <div class="rounded-lg border border-[#dde5ea] bg-[#f7f8f9] p-3">
                        <p class="text-xs font-black tracking-wide text-gray-500 uppercase">Horário</p>
                        <p class="mt-1.5 flex items-center gap-1.5 text-sm font-bold text-gray-950">
                            <Clock class="h-4 w-4 text-[#0f6e78]" />
                            {{ formattedTime }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-[#dde5ea] bg-white px-3 py-1.5 text-xs font-extrabold text-gray-700"
                    >
                        <Calendar class="h-3.5 w-3.5 text-[#0f6e78]" />
                        Consulta agendada
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-sky-100 bg-sky-50 px-3 py-1.5 text-xs font-extrabold text-sky-800"
                    >
                        <Video class="h-3.5 w-3.5" />
                        Teleconsulta
                    </span>
                </div>

                <div v-if="hasNotes" class="rounded-lg border border-[#dde5ea] bg-white p-3">
                    <p class="text-xs font-black tracking-wide text-gray-500 uppercase">Observações</p>
                    <p class="mt-1.5 text-sm leading-relaxed font-medium text-gray-700">{{ appointment.notes }}</p>
                </div>

                <p v-else class="text-sm font-medium text-gray-500">
                    O prontuário e demais informações clínicas estarão disponíveis após a realização da consulta.
                </p>
            </div>

            <DialogFooter class="flex w-full flex-col gap-3 border-t border-[#dde5ea] bg-[#fbfcfc] px-6 py-5 sm:flex-row">
                <Button v-if="canJoinVideo" as-child class="h-11 min-w-0 flex-1 bg-teal-500 font-black text-gray-950 hover:bg-teal-400">
                    <Link :href="patientRoutes.videoCall()" class="inline-flex h-11 w-full items-center justify-center">
                        <Video class="mr-2 h-4 w-4 shrink-0" />
                        Entrar na consulta
                    </Link>
                </Button>
                <Button as-child class="h-11 min-w-0 flex-1 bg-[#0f6e78] font-black text-white hover:bg-[#0d5c64]">
                    <Link :href="detailsHref" class="inline-flex h-11 w-full items-center justify-center" @click="emit('close')">
                        <ExternalLink class="mr-2 h-4 w-4 shrink-0" />
                        Ver detalhes completos
                    </Link>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
