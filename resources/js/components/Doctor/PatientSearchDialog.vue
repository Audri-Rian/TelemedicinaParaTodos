<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Check, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type Patient = {
    id: number;
    name: string;
    cpf?: string | null;
    age?: number | null;
    sex?: 'F' | 'M' | null;
};

interface Props {
    open: boolean;
    patients?: Patient[];
    selectedPatientId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    patients: () => [],
    selectedPatientId: null,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    select: [patient: Patient];
}>();

const patientSearch = ref('');
const selectedPatientIdLocal = ref<number | null>(null);
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');

const normalizedPatients = computed(() =>
    props.patients.map((patient, index) => ({
        ...patient,
        status: index % 4 === 3 ? 'inactive' : 'active',
        lastConsultation: index % 3 === 0 ? 'Há 5 dias' : index % 3 === 1 ? 'Há 1 sem.' : 'Há 4 sem.',
    })),
);

const filteredPatients = computed(() => {
    const q = patientSearch.value.trim().toLowerCase();
    const numericQuery = q.replace(/\D/g, '');
    const withStatus = normalizedPatients.value.filter((patient) => {
        if (statusFilter.value === 'all') return true;
        return patient.status === statusFilter.value;
    });

    if (!q) {
        return withStatus;
    }

    return withStatus.filter((p) => {
        const cpfDigits = (p.cpf ?? '').replace(/\D/g, '');
        return p.name.toLowerCase().includes(q) || (numericQuery.length > 0 && cpfDigits.includes(numericQuery));
    });
});

const totals = computed(() => ({
    all: normalizedPatients.value.length,
    active: normalizedPatients.value.filter((p) => p.status === 'active').length,
    inactive: normalizedPatients.value.filter((p) => p.status === 'inactive').length,
}));

const selectedPatient = computed(() => filteredPatients.value.find((patient) => patient.id === selectedPatientIdLocal.value) ?? null);

function maskCpf(cpf?: string | null): string {
    const digits = (cpf ?? '').replace(/\D/g, '');
    if (digits.length !== 11) {
        return 'CPF não informado';
    }

    return `***.${digits.slice(3, 6)}.***-${digits.slice(9, 11)}`;
}

function avatarLabel(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return 'PA';
    const first = parts[0]?.[0] ?? '';
    const second = parts.length > 1 ? (parts[1]?.[0] ?? '') : '';
    return `${first}${second}`.toUpperCase();
}

function confirmSelection() {
    if (!selectedPatient.value) {
        return;
    }

    emit('select', selectedPatient.value);
    emit('update:open', false);
}

function selectPatient(patient: Patient) {
    selectedPatientIdLocal.value = patient.id;
}

function quickSelect(patient: Patient) {
    emit('select', patient);
    emit('update:open', false);
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            patientSearch.value = '';
            selectedPatientIdLocal.value = props.selectedPatientId;
            statusFilter.value = 'all';
        }
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="h-[78vh] max-h-[78vh] overflow-hidden p-0 sm:max-w-4xl">
            <DialogHeader class="space-y-0 border-b border-zinc-200 px-6 pt-4 pb-1">
                <DialogTitle class="text-3xl font-bold tracking-tight">Selecionar paciente</DialogTitle>
                <DialogDescription class="text-sm font-medium text-zinc-500">
                    Busque por nome ou CPF · {{ totals.all }} de {{ totals.all }} pacientes
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-1.5 border-b border-zinc-200 bg-zinc-50 px-6 pt-1 pb-2">
                <div class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-1.5">
                    <Search class="size-4 text-zinc-400" />
                    <input
                        v-model="patientSearch"
                        type="search"
                        placeholder="Buscar por nome ou CPF (somente números)"
                        class="w-full border-none bg-transparent text-sm outline-none placeholder:text-zinc-400"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-xl border border-zinc-200 bg-white p-1">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1 text-sm font-semibold"
                            :class="statusFilter === 'all' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100'"
                            @click="statusFilter = 'all'"
                        >
                            Todos <span class="ml-1 text-xs opacity-80">{{ totals.all }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1 text-sm font-semibold"
                            :class="statusFilter === 'active' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100'"
                            @click="statusFilter = 'active'"
                        >
                            Ativos <span class="ml-1 text-xs opacity-80">{{ totals.active }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1 text-sm font-semibold"
                            :class="statusFilter === 'inactive' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100'"
                            @click="statusFilter = 'inactive'"
                        >
                            Inativos <span class="ml-1 text-xs opacity-80">{{ totals.inactive }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <ul v-if="filteredPatients.length" class="divide-y divide-zinc-200">
                    <li
                        v-for="patient in filteredPatients"
                        :key="patient.id"
                        class="flex items-center justify-between gap-3 px-6 py-2.5 transition"
                        :class="selectedPatientIdLocal === patient.id ? 'border-l-4 border-l-teal-500 bg-teal-50/70' : 'hover:bg-zinc-50'"
                    >
                        <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left" @click="selectPatient(patient)">
                            <span
                                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-700"
                            >
                                {{ avatarLabel(patient.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xl font-bold text-zinc-900">
                                    {{ patient.name }}
                                    <span
                                        class="ml-2 inline-flex items-center gap-1 text-sm font-semibold"
                                        :class="patient.status === 'active' ? 'text-emerald-700' : 'text-zinc-500'"
                                    >
                                        <span class="size-2 rounded-full" :class="patient.status === 'active' ? 'bg-emerald-500' : 'bg-zinc-400'" />
                                        {{ patient.status === 'active' ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </p>
                                <p class="mt-0.5 text-sm font-medium text-zinc-500">
                                    {{ maskCpf(patient.cpf) }} · {{ patient.age ?? '—' }} anos · {{ patient.sex ?? '—' }}
                                </p>
                            </div>
                        </button>

                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-extrabold tracking-wider text-zinc-400 uppercase">Última consulta</p>
                            <p class="text-base font-semibold text-zinc-900">{{ patient.lastConsultation }}</p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            class="min-w-[120px]"
                            :class="
                                selectedPatientIdLocal === patient.id
                                    ? 'border-teal-500 bg-teal-500 text-white hover:bg-teal-600 hover:text-white'
                                    : ''
                            "
                            @click="quickSelect(patient)"
                        >
                            <Check v-if="selectedPatientIdLocal === patient.id" class="mr-1 size-4" />
                            Selecionar
                        </Button>
                    </li>
                </ul>
                <div v-else class="flex h-full min-h-[260px] flex-col items-center justify-center px-6 text-center">
                    <p class="text-lg font-semibold text-zinc-900">Nenhum paciente encontrado</p>
                    <p class="mt-1 text-sm text-zinc-500">
                        Não localizamos pacientes para "{{ patientSearch || 'sua busca' }}". Verifique a grafia ou busque pelo CPF.
                    </p>
                    <Button type="button" variant="outline" class="mt-4" @click="patientSearch = ''">Limpar busca</Button>
                </div>
            </div>

            <DialogFooter class="flex w-full items-center justify-between border-t border-zinc-200 bg-zinc-50 px-6 py-4 sm:justify-between">
                <div class="hidden items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-500 md:flex">
                    <span class="rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-xs font-semibold text-zinc-600">↑↓</span>
                    <span>navegar</span>
                    <span class="rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-xs font-semibold text-zinc-600">Enter</span>
                    <span>selecionar</span>
                    <span class="rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-xs font-semibold text-zinc-600">Esc</span>
                    <span>cancelar</span>
                </div>
                <div class="flex w-full justify-end gap-2 sm:w-auto">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancelar</Button>
                    <Button type="button" class="bg-teal-500 text-white hover:bg-teal-600" :disabled="!selectedPatient" @click="confirmSelection">
                        Confirmar seleção
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
