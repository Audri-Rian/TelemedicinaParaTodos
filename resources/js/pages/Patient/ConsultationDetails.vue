<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import { Download, Send, FileText, Image, Star } from 'lucide-vue-next';

const { canAccessPatientRoute } = useRouteGuard();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Histórico de Consultas',
        href: patientRoutes.historyConsultations().url,
    },
    {
        title: 'Detalhes da Consulta',
        href: patientRoutes.consultationDetails().url,
    },
];

// Static consultation data for demonstration
const consultation = ref({
    id: 1,
    patientName: 'Maria da Silva',
    date: '25 de Julho, 2024',
    time: '14:30',
    status: 'completed',
    doctor: {
        name: 'Dr. Carlos Andrade',
        specialty: 'Cardiologia',
        avatar: null,
    },
    duration: '30 minutos',
    callType: 'Telemedicina',
    callId: '123-456-789',
    clinicalSummary:
        'Paciente apresenta quadro estável de hipertensão controlada. Exames de rotina mostram níveis de colesterol dentro da normalidade. Recomenda-se manter a medicação atual e monitorar a pressão arterial diariamente. Retorno em 3 meses para reavaliação. Foi discutida a importância da dieta com baixo teor de sódio e atividade física regular.',
    prescriptions: [
        { name: 'Losartana 50mg', dosage: '1 comprimido ao dia' },
        { name: 'AAS 100mg', dosage: '1 comprimido após almoço' },
    ],
    attachments: [
        { name: 'exames_laboratoriais.pdf', size: '2.1 MB', type: 'pdf' },
        { name: 'eletrocardiograma.jpg', size: '850 KB', type: 'image' },
    ],
    timeline: [
        { time: '1:30', event: 'Início da Chamada', description: 'Consulta iniciada com sucesso.' },
        { time: '1:42', event: 'Documento Compartilhado', description: 'Médico anexou exames.' },
        { time: '1:01', event: 'Fim da Chamada', description: 'Consulta finalizada.' },
    ],
    feedbackRating: 3,
});

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});
</script>

<template>
    <Head title="Detalhes da Consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-auto bg-white px-4 py-6">
            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Detalhes da Consulta</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Paciente: {{ consultation.patientName }} | {{ consultation.date }} às {{ consultation.time }}
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-0.5 text-sm font-medium text-green-700">
                    Concluída
                </span>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column (Main Content) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Informações Gerais Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Informações Gerais</h3>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Médico</p>
                                <p class="mt-1 text-base text-gray-900">
                                    {{ consultation.doctor.name }} ({{ consultation.doctor.specialty }})
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Tipo de Consulta</p>
                                <p class="mt-1 text-base text-gray-900">{{ consultation.callType }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Duração</p>
                                <p class="mt-1 text-base text-gray-900">{{ consultation.duration }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Data e Hora</p>
                                <p class="mt-1 text-base text-gray-900">
                                    {{ consultation.date }}, {{ consultation.time }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-sm font-medium text-gray-500">Local / ID da Chamada</p>
                                <p class="mt-1 text-base text-gray-900">Online (ID: {{ consultation.callId }})</p>
                            </div>
                        </div>
                    </div>

                    <!-- Resumo Clínico / Laudo Médico Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Resumo Clínico / Laudo Médico</h3>
                        <p class="text-base text-gray-700">
                            {{ consultation.clinicalSummary }}
                        </p>
                        <div class="mt-6">
                            <Button class="bg-primary hover:bg-primary/90 text-gray-900">
                                <Download class="mr-2 h-4 w-4" />
                                Baixar Laudo (PDF)
                            </Button>
                        </div>
                    </div>

                    <!-- Prescrições e Receitas Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Prescrições e Receitas</h3>
                        <div class="space-y-4">
                            <div
                                v-for="(prescription, index) in consultation.prescriptions"
                                :key="index"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <FileText class="h-5 w-5 text-primary shrink-0" />
                                    <div>
                                        <p class="text-base font-medium text-gray-900">{{ prescription.name }}</p>
                                        <p class="text-sm text-gray-600">{{ prescription.dosage }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <Button variant="outline" class="border border-gray-300 bg-gray-100 text-gray-900 hover:bg-gray-200">
                                        <Download class="mr-2 h-4 w-4" />
                                        Baixar
                                    </Button>
                                    <Button variant="outline" class="border border-gray-300 bg-gray-100 text-gray-900 hover:bg-gray-200">
                                        <Send class="mr-2 h-4 w-4" />
                                        Enviar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Anexos / Documentos Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Anexos / Documentos</h3>
                        <div class="space-y-4">
                            <div
                                v-for="(attachment, index) in consultation.attachments"
                                :key="index"
                                class="flex items-center gap-3"
                            >
                                <FileText v-if="attachment.type === 'pdf'" class="h-5 w-5 text-primary shrink-0" />
                                <Image v-else-if="attachment.type === 'image'" class="h-5 w-5 text-primary shrink-0" />
                                <a href="#" class="text-base text-blue-600 hover:underline">
                                    {{ attachment.name }}
                                </a>
                                <span class="text-sm text-gray-500">({{ attachment.size }})</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Sidebar) -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Linha do Tempo Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Linha do Tempo</h3>
                        <ol class="relative border-s border-gray-200">
                            <li v-for="(item, index) in consultation.timeline" :key="index" class="mb-6 ms-4 last:mb-0">
                                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-primary"></div>
                                <time class="mb-1 text-sm font-normal leading-none text-gray-500">
                                    {{ item.time }}
                                </time>
                                <h4 class="text-base font-semibold text-gray-900">{{ item.event }}</h4>
                                <p class="text-sm font-normal text-gray-700">{{ item.description }}</p>
                            </li>
                        </ol>
                    </div>

                    <!-- Feedback e Acompanhamento Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Feedback e Acompanhamento</h3>
                        <p class="text-base text-gray-700">Como você avalia esta consulta?</p>
                        <div class="mt-2 flex items-center gap-1">
                            <Star
                                v-for="n in 5"
                                :key="n"
                                :class="[
                                    n <= consultation.feedbackRating ? 'text-yellow-400 fill-current' : 'text-gray-300',
                                    'h-5 w-5',
                                ]"
                            />
                        </div>
                        <div class="mt-6">
                            <Button class="w-full bg-primary hover:bg-primary/90 text-gray-900">
                                Marcar Acompanhamento
                            </Button>
                        </div>
                    </div>

                    <!-- Ações Finais Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Ações Finais</h3>
                        <div class="space-y-3">
                            <Link :href="patientRoutes.historyConsultations()" class="block text-base text-blue-600 hover:underline">
                                Voltar ao Histórico de Consultas
                            </Link>
                            <a href="#" class="block text-base text-blue-600 hover:underline">
                                Abrir chat com a clínica
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

