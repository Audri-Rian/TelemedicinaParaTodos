<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Video, Circle, Clock, Calendar } from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';

type DoctorSpecialization = {
    id: string
    name: string
};

type DoctorUser = {
    name: string
    email?: string
    avatar?: string | null
};

type Doctor = {
    id: string
    crm?: string | null
    consultation_fee?: number | null
    biography?: string | null
    user: DoctorUser
    specializations: DoctorSpecialization[]
    available_slots_for_day?: string[] | null
};

const props = withDefaults(defineProps<{
    doctor: Doctor;
    selectedDate?: string | null;
    showOnlineBadge?: boolean;
    showAvailabilityBadge?: boolean;
    class?: string;
}>(), {
    selectedDate: null,
    showOnlineBadge: true,
    showAvailabilityBadge: true,
    class: '',
});

const { getInitials } = useInitials();

const primarySpecialization = computed(() => props.doctor.specializations?.[0]?.name ?? 'Especialista');

const formattedConsultationFee = computed(() => {
    if (props.doctor.consultation_fee == null) {
        return null;
    }

    return Number(props.doctor.consultation_fee).toFixed(2).replace('.', ',');
});

const formattedSelectedDate = computed(() => {
    if (!props.selectedDate) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${props.selectedDate}T00:00:00`));
    } catch (error) {
        return props.selectedDate;
    }
});
</script>

<template>
    <div :class="['bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-lg transition-shadow', props.class]">
        <div class="flex items-start gap-4 mb-4">
            <Avatar class="h-16 w-16">
                <AvatarImage v-if="doctor.user.avatar" :src="doctor.user.avatar" />
                <AvatarFallback class="bg-primary/10 text-primary text-lg font-semibold">
                    {{ getInitials(doctor.user.name) }}
                </AvatarFallback>
            </Avatar>

            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-lg text-gray-900 mb-1 truncate">{{ doctor.user.name }}</h3>
                <p class="text-sm text-gray-600 mb-1">
                    {{ primarySpecialization }}
                </p>
                <p v-if="doctor.crm" class="text-xs text-gray-500">CRM {{ doctor.crm }}</p>

                <div class="mt-2">
                    <span v-if="formattedConsultationFee" class="text-sm font-medium text-gray-900">
                        R$ {{ formattedConsultationFee }}
                    </span>
                    <span v-else class="text-sm text-gray-500">
                        Valor a consultar
                    </span>
                </div>
            </div>
        </div>

        <p v-if="doctor.biography" class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ doctor.biography }}
        </p>

        <div v-if="selectedDate" class="mb-4">
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">
                <Calendar class="h-3 w-3" />
                Horários em {{ formattedSelectedDate }}
            </div>
            <div v-if="doctor.available_slots_for_day && doctor.available_slots_for_day.length" class="flex flex-wrap gap-2">
                <span
                    v-for="slot in doctor.available_slots_for_day"
                    :key="slot"
                    class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                >
                    <Clock class="mr-1 h-3 w-3" />
                    {{ slot }}
                </span>
            </div>
            <p v-else class="text-xs text-gray-500">
                Nenhum horário disponível na data selecionada.
            </p>
        </div>

        <div class="flex flex-wrap gap-2 mb-4" v-if="showOnlineBadge || showAvailabilityBadge">
            <span v-if="showOnlineBadge" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                <Video class="h-3 w-3" />
                Atende Online
            </span>
            <span v-if="showAvailabilityBadge" class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium">
                <Circle class="h-3 w-3 fill-green-500 text-green-500" />
                Disponível
            </span>
        </div>

        <div class="flex flex-wrap gap-2">
            <slot name="actions" :doctor="doctor" />
        </div>
    </div>
</template>
