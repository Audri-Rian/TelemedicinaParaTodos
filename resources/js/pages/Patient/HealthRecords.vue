<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Download, 
    Share2, 
    Info, 
    Calendar, 
    Plus, 
    Headphones, 
    User,
    ChevronDown,
    Eye,
    FileText,
    Upload
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const { canAccessPatientRoute } = useRouteGuard();

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Prontuário',
        href: patientRoutes.healthRecords().url,
    },
];

// Dados mock do paciente
const patient = ref({
    name: 'João da Silva',
    birthDate: '15/08/1985',
    lastUpdate: '20/07/2024'
});

// Estado dos filtros
const selectedYear = ref('2024');
const selectedSpecialty = ref('Todas');
const selectedExamTab = ref('Todos');

// Dados mock das consultas
const consultations = ref([
    {
        id: 1,
        date: '15/07/2024',
        doctor: 'Dr. Carlos Andrade',
        specialty: 'Cardiologia',
        status: 'Concluída'
    },
    {
        id: 2,
        date: '20/05/2024',
        doctor: 'Dra. Ana Pereira',
        specialty: 'Clínica Geral',
        status: 'Concluída'
    },
    {
        id: 3,
        date: '28/08/2024',
        doctor: 'Dr. Carlos Andrade',
        specialty: 'Cardiologia',
        status: 'Agendada'
    },
]);

// Dados mock dos exames
const exams = ref([
    {
        id: 1,
        name: 'Hemograma Completo',
        date: '18/07/2024',
        type: 'Sangue',
        isNew: true
    },
    {
        id: 2,
        name: 'Raio-X do Tórax',
        date: '10/06/2024',
        type: 'Imagem',
        isNew: false
    },
]);

// Dados mock das medicações ativas
const activeMedications = ref([
    'Losartana 50mg - 1 comprimido pela manhã',
    'AAS 100mg - 1 comprimido após o almoço'
]);

// Dados mock dos documentos
const documents = ref([
    {
        id: 1,
        name: 'Atestado Médico.pdf',
        sentDate: '05/03/2024'
    },
]);

// Funções
const exportPDF = () => {
    // TODO: Implementar exportação PDF
    console.log('Exportar PDF');
};

const share = () => {
    // TODO: Implementar compartilhamento
    console.log('Compartilhar');
};

const examTabs = ['Todos', 'Sangue', 'Imagem'];

// Funções para documentos
const viewDocument = (documentId: number) => {
    // TODO: Implementar visualização do documento
    console.log('Visualizar documento:', documentId);
};

const downloadDocument = (documentId: number) => {
    // TODO: Implementar download do documento
    console.log('Baixar documento:', documentId);
};

const uploadDocument = () => {
    // TODO: Implementar upload de novo documento
    console.log('Enviar novo documento');
};
</script>

<template>
    <Head title="Prontuário" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-y-auto bg-gray-50 p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Conteúdo Principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Top Section: Título e Informações -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div>
                                    <CardTitle class="text-3xl font-bold text-gray-900 mb-2">
                                        Meu Prontuário
                                    </CardTitle>
                                    <p class="text-sm text-gray-600">
                                        {{ patient.name }}, Nascido em {{ patient.birthDate }}. 
                                        Última atualização: {{ patient.lastUpdate }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <Button 
                                        @click="exportPDF" 
                                        class="bg-primary hover:bg-primary/90 text-gray-900"
                                    >
                                        <Download class="h-4 w-4 mr-2" />
                                        Exportar PDF
                                    </Button>
                                    <Button 
                                        @click="share" 
                                        variant="outline" 
                                        class="bg-white hover:bg-gray-50"
                                    >
                                        <Share2 class="h-4 w-4 mr-2" />
                                        Compartilhar
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <!-- Banner de Alerta -->
                            <div class="bg-primary/20 border border-primary/30 rounded-lg p-4 flex items-center gap-3">
                                <Info class="h-5 w-5 text-primary flex-shrink-0" />
                                <p class="text-sm text-gray-900 flex-1">
                                    Você tem 3 novos resultados de exames!
                                </p>
                                <div class="h-2 w-2 bg-green-500 rounded-full"></div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Seção: Consultas & Tratamentos -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-bold text-gray-900">
                                    Consultas & Tratamentos
                                </CardTitle>
                                <div class="flex gap-3">
                                    <div class="relative">
                                        <select 
                                            v-model="selectedYear"
                                            class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer"
                                        >
                                            <option value="2024">Ano: 2024</option>
                                            <option value="2023">Ano: 2023</option>
                                            <option value="2022">Ano: 2022</option>
                                        </select>
                                        <ChevronDown class="absolute right-2 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500 pointer-events-none" />
                                    </div>
                                    <div class="relative">
                                        <select 
                                            v-model="selectedSpecialty"
                                            class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer"
                                        >
                                            <option value="Todas">Especialidade</option>
                                            <option value="Cardiologia">Cardiologia</option>
                                            <option value="Clínica Geral">Clínica Geral</option>
                                            <option value="Dermatologia">Dermatologia</option>
                                        </select>
                                        <ChevronDown class="absolute right-2 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500 pointer-events-none" />
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900">DATA</th>
                                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900">MÉDICO</th>
                                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900">STATUS</th>
                                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900">AÇÕES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr 
                                            v-for="consultation in consultations" 
                                            :key="consultation.id"
                                            class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                                        >
                                            <td class="py-4 px-4 text-sm text-gray-900">{{ consultation.date }}</td>
                                            <td class="py-4 px-4 text-sm text-gray-900">
                                                {{ consultation.doctor }} ({{ consultation.specialty }})
                                            </td>
                                            <td class="py-4 px-4">
                                                <span 
                                                    :class="[
                                                        'px-3 py-1 rounded-full text-xs font-medium',
                                                        consultation.status === 'Concluída' 
                                                            ? 'bg-green-100 text-green-700' 
                                                            : 'bg-blue-100 text-blue-700'
                                                    ]"
                                                >
                                                    {{ consultation.status }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-4">
                                                <Link 
                                                    :href="patientRoutes.consultationDetails().url"
                                                    class="text-primary hover:text-primary/80 text-sm font-medium"
                                                >
                                                    Ver detalhes
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Seção: Exames e Resultados -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-bold text-gray-900">
                                    Exames e Resultados
                                </CardTitle>
                                <div class="flex gap-2">
                                    <button
                                        v-for="tab in examTabs"
                                        :key="tab"
                                        @click="selectedExamTab = tab"
                                        :class="[
                                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                            selectedExamTab === tab
                                                ? 'bg-primary text-gray-900'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ tab }}
                                    </button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div 
                                    v-for="exam in exams" 
                                    :key="exam.id"
                                    class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 mb-1">{{ exam.name }}</h4>
                                            <p class="text-xs text-gray-600 mb-1">Data: {{ exam.date }}</p>
                                            <p class="text-xs text-gray-600">Tipo: {{ exam.type }}</p>
                                        </div>
                                        <span 
                                            v-if="exam.isNew"
                                            class="px-2 py-1 bg-primary text-gray-900 text-xs font-medium rounded-full"
                                        >
                                            Novo
                                        </span>
                                    </div>
                                    <div class="flex gap-2 mt-4">
                                        <Button 
                                            class="flex-1 bg-primary hover:bg-primary/90 text-gray-900"
                                            size="sm"
                                        >
                                            Ver Resultado
                                        </Button>
                                        <Button 
                                            variant="outline" 
                                            class="flex-1 bg-white hover:bg-gray-50"
                                            size="sm"
                                        >
                                            <Download class="h-4 w-4 mr-2" />
                                            Baixar PDF
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Seção: Medicações e Intervenções -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-xl font-bold text-gray-900">
                                Medicações e Intervenções
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Medicações Ativas -->
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-3">Medicações Ativas</h3>
                                <ul class="space-y-2" v-if="activeMedications.length > 0">
                                    <li 
                                        v-for="(medication, index) in activeMedications" 
                                        :key="index"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="text-primary mt-1">•</span>
                                        <span class="text-sm text-gray-700">{{ medication }}</span>
                                    </li>
                                </ul>
                                <p v-else class="text-sm text-gray-500">Nenhuma medicação ativa registrada.</p>
                            </div>

                            <!-- Histórico de Medicações -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="font-semibold text-gray-900 mb-3">Histórico de Medicações</h3>
                                <p class="text-sm text-gray-500">Nenhum histórico registrado.</p>
                            </div>

                            <!-- Procedimentos/Cirurgias -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="font-semibold text-gray-900 mb-3">Procedimentos/Cirurgias</h3>
                                <p class="text-sm text-gray-500">Nenhum procedimento registrado.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Seção: Documentos & Laudos -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-bold text-gray-900">
                                    Documentos & Laudos
                                </CardTitle>
                                <Button 
                                    @click="uploadDocument"
                                    class="bg-primary hover:bg-primary/90 text-gray-900"
                                >
                                    <Upload class="h-4 w-4 mr-2" />
                                    Enviar novo documento
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="documents.length > 0" class="space-y-3">
                                <div 
                                    v-for="document in documents" 
                                    :key="document.id"
                                    class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-center gap-3 flex-1">
                                        <FileText class="h-5 w-5 text-gray-400" />
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ document.name }}</h4>
                                            <p class="text-xs text-gray-500">Enviado em: {{ document.sentDate }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button 
                                            @click="viewDocument(document.id)"
                                            variant="outline"
                                            size="icon"
                                            class="h-9 w-9 bg-gray-100 hover:bg-gray-200"
                                        >
                                            <Eye class="h-4 w-4 text-gray-700" />
                                        </Button>
                                        <Button 
                                            @click="downloadDocument(document.id)"
                                            variant="outline"
                                            size="icon"
                                            class="h-9 w-9 bg-gray-100 hover:bg-gray-200"
                                        >
                                            <Download class="h-4 w-4 text-gray-700" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <FileText class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                                <p class="text-sm text-gray-500">Nenhum documento encontrado.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar: Ações Rápidas -->
                <div class="lg:col-span-1">
                    <Card class="bg-white">
                        <CardHeader>
                            <CardTitle class="text-xl font-bold text-gray-900">
                                Ações Rápidas
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button 
                                as-child
                                variant="outline" 
                                class="w-full justify-start bg-white hover:bg-gray-50 h-auto py-3 px-4"
                            >
                                <Link :href="patientRoutes.scheduleConsultation().url">
                                    <Calendar class="h-5 w-5 mr-3 text-gray-700" />
                                    <span class="text-left">
                                        <span class="block font-medium text-gray-900">Agendar nova consulta</span>
                                    </span>
                                </Link>
                            </Button>
                            <Button 
                                variant="outline" 
                                class="w-full justify-start bg-white hover:bg-gray-50 h-auto py-3 px-4"
                            >
                                <Plus class="h-5 w-5 mr-3 text-gray-700" />
                                <span class="text-left">
                                    <span class="block font-medium text-gray-900">Solicitar receita</span>
                                </span>
                            </Button>
                            <Button 
                                variant="outline" 
                                class="w-full justify-start bg-white hover:bg-gray-50 h-auto py-3 px-4"
                            >
                                <Headphones class="h-5 w-5 mr-3 text-gray-700" />
                                <span class="text-left">
                                    <span class="block font-medium text-gray-900">Falar com o suporte</span>
                                </span>
                            </Button>
                            <Button 
                                variant="outline" 
                                class="w-full justify-start bg-white hover:bg-gray-50 h-auto py-3 px-4"
                            >
                                <User class="h-5 w-5 mr-3 text-gray-700" />
                                <span class="text-left">
                                    <span class="block font-medium text-gray-900">Meus dados cadastrais</span>
                                </span>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
