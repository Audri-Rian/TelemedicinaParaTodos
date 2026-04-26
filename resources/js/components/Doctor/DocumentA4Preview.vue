<script setup lang="ts">
import { FileText, Search } from 'lucide-vue-next';
import { computed } from 'vue';

export type DocumentKind = 'rx' | 'cert' | 'exams';

export type PreviewPatient = {
    name: string;
    cpf: string;
    age: number;
    sex: 'F' | 'M';
};

export type RxPreviewItem = {
    name: string;
    strength: string;
    form: string;
    controlled?: boolean;
    ctrl?: string;
    dose: string;
    via: string;
    freq: string;
    dur: string;
    extra: string;
};

export type CertPreviewData = {
    type: string;
    days: string;
    startDate: string;
    startTime: string;
    endTime: string;
    cid: string;
    body: string;
};

export type ExamPreviewItem = {
    code: string;
    name: string;
};

const props = defineProps<{
    docType: DocumentKind;
    patient: PreviewPatient | null;
    rxItems: RxPreviewItem[];
    certData: CertPreviewData;
    examItems: ExamPreviewItem[];
    urgency: string;
    indication: string;
    fasting: string;
}>();

const docTitle = computed(() => {
    if (props.docType === 'rx') {
        return 'Receituário';
    }
    if (props.docType === 'cert') {
        return 'Atestado médico';
    }
    return 'Solicitação de exames';
});

const docCode = computed(() => {
    const map = { rx: 'RX', cert: 'AT', exams: 'PE' } as const;
    return `${map[props.docType]}-2026-04821`;
});

const issuedAt = computed(() =>
    new Intl.DateTimeFormat('pt-BR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date()),
);

const certCityDate = computed(() =>
    new Intl.DateTimeFormat('pt-BR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);

const urgencyLabel = computed(() => {
    const map: Record<string, string> = {
        rotina: 'Rotina',
        prioritario: 'Prioritário',
        urgente: 'Urgente',
    };
    return map[props.urgency] ?? 'Rotina';
});

const qrPattern = [
    '0011010001',
    '0100101110',
    '1011001011',
    '1110100100',
    '0001011010',
    '1010110100',
    '0110001101',
    '1101010011',
    '0010110110',
    '1100101010',
];
</script>

<template>
    <div class="w-full max-w-[600px]">
        <div class="mb-2.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-extrabold tracking-wide text-zinc-500 uppercase">Pré-visualização</span>
                <span class="text-[11.5px] font-semibold text-zinc-600">· {{ docTitle }}</span>
            </div>
            <div class="flex gap-1">
                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100"
                    aria-label="Ampliar"
                >
                    <Search class="size-4" />
                </button>
                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100"
                    aria-label="Baixar PDF"
                >
                    <FileText class="size-4" />
                </button>
            </div>
        </div>

        <div
            class="relative aspect-[210/297] w-full overflow-hidden rounded-md border border-zinc-300 bg-white text-[#1c1b18] shadow-[0_4px_20px_rgba(28,27,24,0.08),0_1px_0_rgba(28,27,24,0.04)]"
        >
            <div
                class="pointer-events-none absolute inset-0 flex [transform:rotate(-22deg)] items-center justify-center text-[64px] font-black tracking-[0.35em] whitespace-nowrap text-[rgba(13,138,125,0.06)] select-none [-webkit-user-select:none]"
            >
                PRÉ-VISUALIZAÇÃO
            </div>

            <div class="relative flex h-full flex-col px-[7%] pt-[6.5%] pb-[5%]">
                <header class="flex items-center gap-3 border-b-[1.5px] border-zinc-900 pb-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-[10px] bg-primary text-lg font-black text-zinc-900">+</div>
                    <div class="min-w-0 flex-1 leading-tight">
                        <div class="text-[13px] font-extrabold">Dra. Ana Vargas Botelho</div>
                        <div class="text-[9.5px] font-semibold text-zinc-600">Clínica geral · CRM/SP 138.402 · RQE 28.901</div>
                        <div class="mt-0.5 text-[9px] font-medium text-zinc-500">
                            Telemedicina Para Todos · Av. Paulista 1230, sala 802 · São Paulo/SP
                        </div>
                    </div>
                    <div class="shrink-0 text-right text-[9px] font-semibold text-zinc-600">
                        <div class="font-mono text-[10px] font-bold text-zinc-900">{{ docCode }}</div>
                        <div class="mt-0.5">{{ issuedAt }}</div>
                    </div>
                </header>

                <h1 class="my-3.5 mb-1.5 text-center text-sm font-extrabold tracking-[0.28em] uppercase">
                    {{ docType === 'rx' ? 'Receituário' : docType === 'cert' ? 'Atestado médico' : 'Solicitação de exames' }}
                </h1>

                <div v-if="patient" class="mb-3 rounded bg-[#f8f7f2] px-2.5 py-2 text-[9.5px] text-zinc-800">
                    <div><strong>Paciente:</strong> {{ patient.name }}</div>
                    <div class="mt-0.5">
                        <strong>CPF:</strong> <span class="font-mono">{{ patient.cpf }}</span>
                        <span> · </span>
                        <strong>Idade:</strong> {{ patient.age }} anos · <strong>Sexo:</strong>
                        {{ patient.sex === 'F' ? 'Feminino' : 'Masculino' }}
                    </div>
                </div>
                <div
                    v-else
                    class="mb-3 rounded-md border-[1.5px] border-dashed border-zinc-300 px-3 py-3.5 text-center text-[10px] font-semibold text-zinc-500"
                >
                    Selecione um paciente para preencher o cabeçalho
                </div>

                <div class="min-h-0 flex-1 overflow-hidden text-[10px] leading-relaxed">
                    <!-- Receita -->
                    <template v-if="docType === 'rx'">
                        <div v-if="!rxItems.length" class="px-5 py-10 text-center text-[11px] font-semibold text-zinc-500">
                            Os medicamentos aparecerão aqui conforme você adicioná-los.
                        </div>
                        <ol v-else class="my-2 flex list-decimal flex-col gap-2.5 pl-5 marker:font-normal">
                            <li v-for="(it, i) in rxItems" :key="i" class="pl-1">
                                <div class="text-[11px] font-extrabold">
                                    {{ it.name }} · {{ it.strength }} · {{ it.form }}
                                    <span
                                        v-if="it.controlled"
                                        class="ml-2 inline-block rounded bg-[#fbeeda] px-1.5 py-px text-[8px] font-extrabold tracking-wide text-amber-900"
                                    >
                                        LISTA {{ it.ctrl }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-[10px] text-zinc-800">
                                    <strong>Tomar</strong> {{ it.dose }} · <strong>Via</strong> {{ it.via }} · <strong>Frequência</strong>
                                    {{ it.freq.toLowerCase() }}.
                                </div>
                                <div class="text-[10px] text-zinc-800">
                                    <strong>Duração:</strong> {{ it.dur }}.<template v-if="it.extra"> {{ it.extra }}.</template>
                                </div>
                            </li>
                        </ol>
                    </template>

                    <!-- Atestado -->
                    <template v-else-if="docType === 'cert'">
                        <div v-if="!certData.body" class="px-5 py-10 text-center text-[11px] font-semibold text-zinc-500">
                            O texto do atestado aparecerá aqui.
                        </div>
                        <div v-else class="px-0 py-2 text-justify text-[10.5px] leading-[1.7]">
                            <p class="mb-3">
                                Atesto, para os devidos fins, que o(a) paciente
                                <strong>{{ patient?.name ?? '_______' }}</strong>
                                esteve sob meus cuidados médicos no dia de hoje, devendo permanecer afastado(a) de suas atividades laborais por um
                                período de
                                <strong>{{ certData.days || '__' }} ({{ Number(certData.days || 0) > 0 ? certData.days : '__' }}) dias</strong>, a
                                partir de <strong>{{ certData.startDate || '__/__/____' }}</strong
                                >.
                            </p>
                            <p v-if="certData.body" class="mb-3">{{ certData.body }}</p>
                            <p v-if="certData.cid" class="mb-3 text-[10px]">
                                <strong>CID-10:</strong> <span class="font-mono">{{ certData.cid }}</span>
                                <span class="text-zinc-600"> · informado com consentimento expresso do paciente.</span>
                            </p>
                            <p class="mt-6 text-right text-[10px]">São Paulo, {{ certCityDate }}.</p>
                        </div>
                    </template>

                    <!-- Exames -->
                    <template v-else>
                        <div v-if="!examItems.length" class="px-5 py-10 text-center text-[11px] font-semibold text-zinc-500">
                            Os exames solicitados aparecerão aqui.
                        </div>
                        <div v-else class="text-[10.5px] leading-relaxed">
                            <div
                                class="mb-2.5 inline-block rounded px-2 py-0.5 text-[9px] font-extrabold tracking-wide"
                                :class="urgency === 'urgente' ? 'bg-red-100 text-red-900' : 'bg-teal-100 text-teal-900'"
                            >
                                URGÊNCIA · {{ urgencyLabel.toUpperCase() }}
                            </div>
                            <div class="mb-1.5 font-extrabold">Solicito a realização dos seguintes exames:</div>
                            <ol class="mb-3 flex list-decimal flex-col gap-1 pl-5">
                                <li v-for="(e, i) in examItems" :key="i">
                                    <span class="mr-1.5 font-mono text-[9px] text-zinc-600">{{ e.code }}</span>
                                    {{ e.name }}
                                </li>
                            </ol>
                            <div v-if="indication" class="mt-2.5"><strong>Indicação clínica:</strong> {{ indication }}</div>
                            <div v-if="fasting" class="mt-1.5 text-[10px]"><strong>Preparo:</strong> {{ fasting }}</div>
                        </div>
                    </template>
                </div>

                <footer class="mt-auto flex items-center gap-3 border-t border-zinc-300 pt-2.5 text-[8px] text-zinc-600">
                    <div class="relative size-11 shrink-0 overflow-hidden rounded bg-zinc-900">
                        <svg viewBox="0 0 10 10" class="block size-11" aria-hidden="true">
                            <template v-for="(row, y) in qrPattern" :key="y">
                                <template v-for="(c, x) in row.split('')" :key="`${y}-${x}`">
                                    <rect v-if="c === '1'" :x="x" :y="y" width="1.05" height="1.05" fill="#fff" />
                                </template>
                            </template>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1 leading-snug">
                        <div class="text-[9px] font-bold text-zinc-900">Validação online</div>
                        <div>Confira a autenticidade em telemedicinaparatodos.com.br/v</div>
                        <div class="mt-0.5 font-mono">Código: 7F4A · 9C12 · BD03 · 218E</div>
                    </div>
                    <div class="shrink-0 border-l border-zinc-300 pl-2.5 text-right text-[8.5px] leading-snug">
                        <div class="text-[8px] font-bold tracking-wide text-zinc-500">ASSINATURA DIGITAL</div>
                        <div class="mt-0.5 text-zinc-500 italic">Aguardando integração ICP-Brasil</div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>
