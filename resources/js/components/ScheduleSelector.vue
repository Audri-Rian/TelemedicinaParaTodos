<script setup lang="ts">
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';

interface AvailableDate {
    date: string;
    available_slots: string[];
}

const props = withDefaults(defineProps<{
    availableDates: AvailableDate[];
    selectedDate?: string | null;
    selectedTime?: string | null;
    timezoneNotice?: string;
    disabled?: boolean;
}>(), {
    selectedDate: null,
    selectedTime: null,
    timezoneNotice: 'Todos os horários consideram seu fuso horário local.',
    disabled: false,
});

const emit = defineEmits<{
    (event: 'update:selectedDate', value: string | null): void;
    (event: 'update:selectedTime', value: string | null): void;
}>();

const formattedDates = computed(() => {
    return props.availableDates.map((item) => {
        let label = item.date;

        try {
            label = new Intl.DateTimeFormat('pt-BR', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
            }).format(new Date(`${item.date}T00:00:00`));
        } catch (error) {
            // manter label padrão
        }

        return {
            ...item,
            label,
        };
    });
});

const currentDateSlots = computed(() => {
    if (!props.selectedDate) {
        return [];
    }

    return props.availableDates.find((item) => item.date === props.selectedDate)?.available_slots ?? [];
});

watch(
    () => props.availableDates,
    (dates) => {
        if (!dates.length) {
            emit('update:selectedDate', null);
            emit('update:selectedTime', null);
            return;
        }

        const currentDateExists = props.selectedDate
            ? dates.some((item) => item.date === props.selectedDate)
            : false;

        if (!currentDateExists) {
            const firstDate = dates[0];
            emit('update:selectedDate', firstDate.date);
            emit('update:selectedTime', firstDate.available_slots?.[0] ?? null);
            return;
        }

        if (props.selectedDate) {
            const selected = dates.find((item) => item.date === props.selectedDate);
            if (!selected) {
                return;
            }

            if (!selected.available_slots.length) {
                emit('update:selectedTime', null);
                return;
            }

            const slotExists = props.selectedTime
                ? selected.available_slots.includes(props.selectedTime)
                : false;

            if (!slotExists) {
                emit('update:selectedTime', selected.available_slots[0]);
            }
        }
    },
    {
        immediate: true,
        deep: true,
    }
);

const handleDateSelect = (date: string) => {
    if (props.disabled || date === props.selectedDate) {
        return;
    }

    emit('update:selectedDate', date);
};

const handleTimeSelect = (time: string) => {
    if (props.disabled || time === props.selectedTime) {
        return;
    }

    emit('update:selectedTime', time);
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="formattedDates.length" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="flex flex-col gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Datas disponíveis</p>
                <div class="flex flex-col gap-2 max-h-64 overflow-y-auto pr-2">
                    <button
                        v-for="item in formattedDates"
                        :key="item.date"
                        type="button"
                        @click="handleDateSelect(item.date)"
                        :disabled="disabled"
                        :class="[
                            'rounded-lg border px-3 py-2 text-left text-sm transition-all',
                            item.date === selectedDate
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-gray-200 hover:border-primary/50 hover:bg-primary/5'
                        ]"
                    >
                        <div class="font-medium capitalize">{{ item.label }}</div>
                        <div class="text-xs text-gray-500">{{ item.available_slots.length }} horário(s)</div>
                    </button>
                </div>
            </div>

            <div class="md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Horários</p>

                <div v-if="currentDateSlots.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                    <Button
                        v-for="slot in currentDateSlots"
                        :key="slot"
                        variant="outline"
                        :disabled="disabled"
                        @click="handleTimeSelect(slot)"
                        :class="[
                            'text-sm font-medium',
                            slot === selectedTime
                                ? 'border-primary bg-primary text-gray-900 hover:bg-primary'
                                : 'hover:border-primary'
                        ]"
                    >
                        {{ slot }}
                    </Button>
                </div>

                <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-6 text-center text-sm text-gray-500">
                    Nenhum horário disponível para a data selecionada.
                </div>
            </div>
        </div>

        <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-6 text-center text-sm text-gray-500">
            Nenhuma disponibilidade encontrada.
        </div>

        <p v-if="timezoneNotice" class="text-xs text-gray-500">
            {{ timezoneNotice }}
        </p>
    </div>
</template>
