<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar, CheckCircle2, ChevronLeft, ChevronRight, Clock, Info } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface AvailableDate {
    date: string;
    available_slots: string[];
}

const props = withDefaults(
    defineProps<{
        availableDates: AvailableDate[];
        selectedDate?: string | null;
        selectedTime?: string | null;
        timezoneNotice?: string;
        disabled?: boolean;
    }>(),
    {
        selectedDate: null,
        selectedTime: null,
        timezoneNotice: 'Todos os horários consideram seu fuso horário local.',
        disabled: false,
    },
);

const emit = defineEmits<{
    (event: 'update:selectedDate', value: string | null): void;
    (event: 'update:selectedTime', value: string | null): void;
}>();

const today = new Date();
today.setHours(0, 0, 0, 0);

const createDate = (dateString: string): Date => new Date(`${dateString}T00:00:00`);
const toDateKey = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const startOfMonth = (date: Date): Date => new Date(date.getFullYear(), date.getMonth(), 1);

const initialMonth = () => {
    if (props.selectedDate) {
        return startOfMonth(createDate(props.selectedDate));
    }

    if (props.availableDates[0]?.date) {
        return startOfMonth(createDate(props.availableDates[0].date));
    }

    return startOfMonth(new Date());
};

const monthAnchor = ref(initialMonth());

const availableByDate = computed(() => {
    return new Map(props.availableDates.map((item) => [item.date, item.available_slots]));
});

const availableDateKeys = computed(() => new Set(props.availableDates.map((item) => item.date)));

const currentDateSlots = computed(() => {
    if (!props.selectedDate) {
        return [];
    }

    return props.availableDates.find((item) => item.date === props.selectedDate)?.available_slots ?? [];
});

const monthLabel = computed(() => {
    return monthAnchor.value.toLocaleDateString('pt-BR', {
        month: 'long',
        year: 'numeric',
    });
});

const selectedDateLabel = computed(() => {
    if (!props.selectedDate) {
        return null;
    }

    return createDate(props.selectedDate).toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
    });
});

const calendarDays = computed(() => {
    const firstDay = startOfMonth(monthAnchor.value);
    const start = new Date(firstDay);
    start.setDate(firstDay.getDate() - firstDay.getDay());

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const key = toDateKey(date);
        const isPast = date < today;

        return {
            key,
            date,
            day: date.getDate(),
            inMonth: date.getMonth() === monthAnchor.value.getMonth(),
            isToday: key === toDateKey(today),
            isAvailable: availableDateKeys.value.has(key) && !isPast,
            isSelected: key === props.selectedDate,
            isPast,
            slotsCount: availableByDate.value.get(key)?.length ?? 0,
        };
    });
});

const moveMonth = (amount: number) => {
    monthAnchor.value = new Date(monthAnchor.value.getFullYear(), monthAnchor.value.getMonth() + amount, 1);
};

watch(
    () => props.availableDates,
    (dates) => {
        if (!dates.length) {
            emit('update:selectedDate', null);
            emit('update:selectedTime', null);
            return;
        }

        const currentDateExists = props.selectedDate ? dates.some((item) => item.date === props.selectedDate) : false;

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

            const slotExists = props.selectedTime ? selected.available_slots.includes(props.selectedTime) : false;

            if (!slotExists) {
                emit('update:selectedTime', selected.available_slots[0]);
            }
        }
    },
    {
        immediate: true,
        deep: true,
    },
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

watch(
    () => props.selectedDate,
    (date) => {
        if (date) {
            monthAnchor.value = startOfMonth(createDate(date));
        }
    },
);
</script>

<template>
    <div class="space-y-4">
        <div v-if="availableDates.length" class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
                        :disabled="disabled"
                        aria-label="Mês anterior"
                        @click="moveMonth(-1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <p class="text-sm font-semibold text-slate-950 capitalize">{{ monthLabel }}</p>
                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
                        :disabled="disabled"
                        aria-label="Próximo mês"
                        @click="moveMonth(1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-2">
                    <div
                        v-for="(dayName, index) in ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']"
                        :key="`${dayName}-${index}`"
                        class="grid h-7 place-items-center text-xs font-semibold text-slate-400"
                    >
                        {{ dayName }}
                    </div>
                    <button
                        v-for="day in calendarDays"
                        :key="day.key"
                        type="button"
                        :disabled="disabled || !day.isAvailable"
                        @click="handleDateSelect(day.key)"
                        :class="[
                            'relative grid aspect-square min-h-10 place-items-center rounded-lg border text-sm font-semibold transition',
                            !day.inMonth ? 'text-slate-300' : 'text-slate-700',
                            day.isAvailable
                                ? 'border-teal-100 bg-white hover:border-teal-400 hover:bg-teal-50'
                                : 'border-transparent bg-slate-50 text-slate-300',
                            day.isToday && !day.isSelected ? 'ring-1 ring-slate-300' : '',
                            day.isSelected ? 'border-teal-600 bg-teal-600 text-white shadow-sm hover:bg-teal-600' : '',
                        ]"
                    >
                        {{ day.day }}
                        <span
                            v-if="day.isAvailable"
                            :class="['absolute bottom-1.5 h-1.5 w-1.5 rounded-full', day.isSelected ? 'bg-white' : 'bg-teal-500']"
                        ></span>
                    </button>
                </div>

                <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-teal-500"></span>Vagas disponíveis</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Indisponível</span>
                </div>
            </div>

            <div class="space-y-3">
                <div v-if="selectedDate" class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-950 capitalize">{{ selectedDateLabel }}</p>
                        <p class="text-xs text-slate-500">{{ currentDateSlots.length }} horários disponíveis</p>
                    </div>
                    <Clock class="h-4 w-4 text-slate-400" />
                </div>

                <div v-if="selectedDate && currentDateSlots.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <Button
                        v-for="slot in currentDateSlots"
                        :key="slot"
                        variant="outline"
                        :disabled="disabled"
                        @click="handleTimeSelect(slot)"
                        :class="[
                            'h-11 border-slate-200 text-sm font-semibold transition',
                            slot === selectedTime
                                ? 'border-teal-600 bg-teal-600 text-white hover:bg-teal-700'
                                : 'bg-white text-slate-700 hover:border-teal-300 hover:bg-teal-50',
                        ]"
                    >
                        {{ slot }}
                    </Button>
                </div>

                <div
                    v-else-if="selectedDate"
                    class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500"
                >
                    Nenhum horário disponível para a data selecionada.
                </div>

                <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    <Calendar class="mx-auto mb-3 h-7 w-7 text-slate-300" />
                    <p class="font-semibold text-slate-800">Selecione uma data</p>
                    <p class="mt-1 text-xs">Os horários disponíveis aparecerão aqui.</p>
                </div>

                <div
                    v-if="selectedDate && currentDateSlots.length <= 2"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800"
                >
                    <Info class="h-3.5 w-3.5" />
                    Poucos horários disponíveis neste dia
                </div>

                <div
                    v-if="selectedDate && selectedTime"
                    class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-3 text-sm font-semibold text-teal-900"
                >
                    <div class="flex gap-2">
                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-teal-700" />
                        <span class="capitalize">Consulta selecionada para {{ selectedDateLabel }} às {{ selectedTime }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 py-10 text-center text-sm text-slate-500">
            Nenhuma disponibilidade encontrada.
        </div>

        <p v-if="timezoneNotice" class="text-xs text-slate-500">
            {{ timezoneNotice }}
        </p>
    </div>
</template>
