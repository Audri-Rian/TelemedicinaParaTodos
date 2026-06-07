<script setup lang="ts">
import AppointmentStatusBadge from '@/components/AppointmentStatusBadge.vue';
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import UpcomingAppointmentDetailModal from '@/components/Patient/MedicalRecord/UpcomingAppointmentDetailModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/composables/useFormatters';
import { useInitials } from '@/composables/useInitials';
import type { Appointment } from '@/types/medical-records';
import { Calendar, CalendarClock, ChevronRight, Clock, Stethoscope } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    upcomingAppointments: Appointment[];
    emptyText: string;
    isDoctorMode?: boolean;
}>();

const { formatDate, formatTime } = useFormatters();
const { getInitials } = useInitials();

const selectedAppointment = ref<Appointment | null>(null);
const isModalOpen = ref(false);

function doctorName(appointment: Appointment): string {
    return appointment.doctor.user.name;
}

function doctorAvatar(appointment: Appointment): string | null {
    return appointment.doctor.user.avatar ?? null;
}

function specialization(appointment: Appointment): string {
    return appointment.doctor.specializations?.[0]?.name ?? 'Especialidade não informada';
}

function relativeLabel(scheduledAt?: string): string | null {
    if (!scheduledAt) return null;
    const date = new Date(scheduledAt);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const apptDay = new Date(date);
    apptDay.setHours(0, 0, 0, 0);
    const diffDays = Math.round((apptDay.getTime() - today.getTime()) / 86_400_000);
    if (diffDays === 0) return 'Hoje';
    if (diffDays === 1) return 'Amanhã';
    if (diffDays > 1 && diffDays <= 7) return `Em ${diffDays} dias`;
    return null;
}

function openDetails(appointment: Appointment) {
    selectedAppointment.value = appointment;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    selectedAppointment.value = null;
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article
            v-for="appointment in upcomingAppointments"
            :key="appointment.id"
            class="group cursor-pointer overflow-hidden rounded-lg border border-[#dde5ea] bg-white shadow-sm transition hover:border-[#0f6e78]/30 hover:shadow-md"
            role="button"
            tabindex="0"
            @click="openDetails(appointment)"
            @keydown.enter="openDetails(appointment)"
            @keydown.space.prevent="openDetails(appointment)"
        >
            <div class="border-b border-[#eef2f4] bg-gradient-to-r from-[#f7f8f9] to-white px-4 py-3">
                <div class="flex items-center justify-between gap-2">
                    <span
                        v-if="relativeLabel(appointment.scheduled_at)"
                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-black text-amber-800"
                    >
                        <CalendarClock class="h-3 w-3" />
                        {{ relativeLabel(appointment.scheduled_at) }}
                    </span>
                    <AppointmentStatusBadge :status="appointment.status" class="ml-auto" />
                </div>
            </div>

            <div class="p-4">
                <div class="flex gap-3">
                    <Avatar class="h-12 w-12 shrink-0 border border-[#dde5ea]">
                        <AvatarImage v-if="doctorAvatar(appointment)" :src="doctorAvatar(appointment) ?? undefined" :alt="doctorName(appointment)" />
                        <AvatarFallback class="bg-[#e5f1f2] text-sm font-black text-[#0f6e78]">
                            {{ getInitials(doctorName(appointment)) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-black text-gray-950">{{ doctorName(appointment) }}</h2>
                        <p class="mt-0.5 flex items-center gap-1 text-sm font-semibold text-gray-500">
                            <Stethoscope class="h-3.5 w-3.5 shrink-0 text-[#0f6e78]" />
                            <span class="truncate">{{ specialization(appointment) }}</span>
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-extrabold text-gray-600">
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#f7f8f9] px-2.5 py-1">
                                <Calendar class="h-3 w-3 text-[#0f6e78]" />
                                {{ formatDate(appointment.scheduled_at, true) }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#f7f8f9] px-2.5 py-1">
                                <Clock class="h-3 w-3 text-[#0f6e78]" />
                                {{ formatTime(new Date(appointment.scheduled_at ?? '')) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-[#eef2f4] pt-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 px-0 font-extrabold text-[#0f6e78] group-hover:underline hover:bg-transparent hover:text-[#0d5c64]"
                        @click.stop="openDetails(appointment)"
                    >
                        Ver detalhes
                        <ChevronRight class="ml-1 h-4 w-4" />
                    </Button>
                </div>
            </div>
        </article>

        <EmptyBlock v-if="upcomingAppointments.length === 0" :text="emptyText" />

        <UpcomingAppointmentDetailModal
            :is-open="isModalOpen"
            :appointment="selectedAppointment"
            :is-doctor-mode="isDoctorMode"
            @close="closeModal"
        />
    </div>
</template>
