<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import * as integrationRoutes from '@/routes/doctor/integrations';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BookOpenText,
    Check,
    CheckCheck,
    CheckCircle2,
    ChevronRight,
    ClipboardCheck,
    Copy,
    FileKey,
    FlaskConical,
    GitBranch,
    Globe,
    HelpCircle,
    Info,
    KeyRound,
    Loader2,
    Lock,
    Plug2,
    PlusCircle,
    RefreshCw,
    ScanEye,
    Server,
    Settings,
    ShieldCheck,
    Sparkles,
    Wifi,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Integrações', href: doctorRoutes.integrations().url },
    { title: 'Conectar Parceiro', href: integrationRoutes.connect().url },
];

// Wizard state
const currentStep = ref(1);
const isConnected = ref(false);
const connectionError = ref(false);

// Modo de integração
type IntegrationMode = 'full' | 'receive_only';
const integrationMode = ref<IntegrationMode | null>(null);

const integrationModes = [
    {
        key: 'full' as IntegrationMode,
        label: 'Enviar e receber dados',
        description: 'Envie pedidos de exame e receba resultados automaticamente.',
        icon: RefreshCw,
    },
    {
        key: 'receive_only' as IntegrationMode,
        label: 'Apenas receber resultados',
        description: 'O parceiro envia resultados via webhook. Você só precisa compartilhar a URL.',
        icon: ArrowRight,
    },
];

// Steps dinâmicos baseados no modo
const steps = computed(() => {
    if (integrationMode.value === 'receive_only') {
        return [
            { num: 1, label: 'Configuração', icon: Settings },
            { num: 2, label: 'Webhook', icon: GitBranch },
            { num: 3, label: 'Revisão', icon: ClipboardCheck },
        ];
    }
    return [
        { num: 1, label: 'Configuração', icon: Settings },
        { num: 2, label: 'Mapeamento', icon: GitBranch },
        { num: 3, label: 'Sincronização', icon: RefreshCw },
        { num: 4, label: 'Revisão', icon: ClipboardCheck },
    ];
});

const totalSteps = computed(() => steps.value.length);

// Step 1: Parceiros
const availablePartners = [
    {
        key: 'hermes-pardini',
        name: 'Hermes Pardini',
        description: 'Líder em medicina diagnóstica e preventiva no Brasil.',
        type: 'laboratory',
        available: true,
    },
    { key: 'fleury', name: 'Fleury', description: 'Excelência médica e técnica em análises clínicas.', type: 'laboratory', available: true },
    {
        key: 'a-plus-medicina',
        name: 'A+ Medicina',
        description: 'Atendimento humanizado e resultados precisos.',
        type: 'laboratory',
        available: true,
    },
    { key: 'custom', name: 'Outro', description: '(em breve)', type: 'other', available: false },
];

// Inertia form
const form = useForm({
    partner_name: '',
    partner_slug: '',
    partner_type: 'laboratory',
    integration_mode: '' as IntegrationMode | '',
    base_url: '',
    fhir_version: 'R4',
    contact_email: '',
    auth_method: '',
    client_id: '',
    client_secret: '',
    bearer_token: '',
    perm_send_orders: true,
    perm_receive_results: true,
    perm_webhook: true,
    perm_patient_data: false,
});

const selectedPartner = ref<string | null>(null);
const selectedPartnerName = computed(() => availablePartners.find((p) => p.key === selectedPartner.value)?.name ?? '—');

// Step 3
const authMethods = [
    { key: 'oauth2', label: 'OAuth2 Client Credentials', description: 'Autenticação máquina-a-máquina. Recomendado.', icon: Lock },
    { key: 'api_key', label: 'Chave de API', description: 'Chave fornecida pelo parceiro.', icon: FileKey },
    { key: 'bearer', label: 'Bearer Token', description: 'Token de acesso direto.', icon: KeyRound },
    { key: 'certificate', label: 'Certificado Digital', description: 'Via certificado ICP-Brasil (e-CNPJ).', icon: Server },
];

// Navegação
const progressPercent = computed(() => (currentStep.value / totalSteps.value) * 100);

const isReceiveOnly = computed(() => integrationMode.value === 'receive_only');

const stepTitles = computed<Record<number, { label: string; title: string; subtitle: string }>>(() => {
    if (isReceiveOnly.value) {
        return {
            1: { label: 'ESCOLHER PARCEIRO', title: 'Qual parceiro deseja conectar?', subtitle: 'Selecione o parceiro e o modo de integração.' },
            2: {
                label: 'WEBHOOK',
                title: 'Dados de conexão',
                subtitle: 'Compartilhe a URL de webhook com o parceiro para que ele envie resultados.',
            },
            3: { label: 'REVISÃO', title: 'Revisar e confirmar', subtitle: 'Verifique as configurações antes de ativar.' },
        };
    }
    return {
        1: { label: 'ESCOLHER PARCEIRO', title: 'Qual parceiro deseja conectar?', subtitle: 'Selecione o parceiro e o modo de integração.' },
        2: {
            label: 'MAPEAMENTO',
            title: 'Configurar conexão',
            subtitle: 'Defina os parâmetros de comunicação e mapeamento de dados com o parceiro.',
        },
        3: { label: 'SINCRONIZAÇÃO', title: 'Método de autenticação', subtitle: 'Como o parceiro vai se autenticar com o nosso sistema?' },
        4: { label: 'REVISÃO', title: 'Revisar e confirmar', subtitle: 'Verifique todas as configurações antes de ativar a integração.' },
    };
});

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return selectedPartner.value !== null && integrationMode.value !== null;
    }

    if (isReceiveOnly.value) {
        // Receive only: Step 2 = webhook (sempre ok), Step 3 = revisão
        return true;
    }

    // Full mode
    switch (currentStep.value) {
        case 2:
            return form.base_url.trim() !== '';
        case 3: {
            if (!form.auth_method) return false;
            if (form.auth_method === 'oauth2') return form.client_id.trim() !== '' && form.client_secret.trim() !== '';
            if (form.auth_method === 'api_key') return form.client_id.trim() !== '';
            if (form.auth_method === 'bearer') return form.bearer_token.trim() !== '';
            return true;
        }
        case 4:
            return true;
        default:
            return false;
    }
});

const selectPartner = (key: string) => {
    selectedPartner.value = key;
    const partner = availablePartners.find((p) => p.key === key);
    if (partner) {
        form.partner_name = partner.name;
        form.partner_slug = partner.key;
        form.partner_type = partner.type;
    }
};

const selectMode = (mode: IntegrationMode) => {
    integrationMode.value = mode;
    form.integration_mode = mode;
    if (mode === 'receive_only') {
        form.perm_send_orders = false;
        form.perm_receive_results = true;
        form.perm_webhook = true;
        form.auth_method = '';
        form.base_url = '';
    } else {
        form.perm_send_orders = true;
    }
};

const nextStep = () => {
    if (currentStep.value < totalSteps.value && canProceed.value) currentStep.value++;
};
const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

// Verifica se o step atual é o último (revisão)
const isLastStep = computed(() => currentStep.value === totalSteps.value);

const handleConnect = () => {
    form.post('/doctor/integrations/connect', {
        onSuccess: () => {
            isConnected.value = true;
            connectionError.value = false;
        },
        onError: () => {
            connectionError.value = true;
        },
    });
};

// Clipboard
const copiedField = ref<string | null>(null);
const copyToClipboard = async (text: string, field: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedField.value = field;
        setTimeout(() => {
            copiedField.value = null;
        }, 2000);
    } catch {
        // Fallback para navegadores sem Clipboard API
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        copiedField.value = field;
        setTimeout(() => {
            copiedField.value = null;
        }, 2000);
    }
};

const webhookUrl = computed(() => {
    if (!selectedPartner.value) return '';
    return `${window.location.origin}/api/v1/public/webhooks/lab/${selectedPartner.value}`;
});
const docsUrl = '/docs/interoperabilidade';
</script>

<template>
    <Head title="Conectar Parceiro · Integrações" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Estado: Conectado com sucesso -->
        <div v-if="isConnected && !connectionError" class="w-full px-8 py-12">
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <!-- Ícone de sucesso -->
                <div class="relative mb-8">
                    <div class="relative z-10 flex size-24 items-center justify-center rounded-full bg-primary/10">
                        <CheckCircle2 class="size-14 text-primary" stroke-width="1.5" />
                    </div>
                    <div class="absolute inset-0 scale-150 rounded-full bg-primary/5 blur-3xl" />
                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-foreground md:text-5xl">Integração conectada com sucesso!</h1>
                <p class="mt-4 max-w-2xl text-lg text-muted-foreground">
                    A conexão com o parceiro <span class="font-semibold text-primary">{{ selectedPartnerName }}</span> foi estabelecida com segurança.
                    O fluxo de dados diagnósticos agora está ativo e monitorado.
                </p>

                <!-- Botões de ação -->
                <div class="mt-12 flex flex-col gap-4 sm:flex-row">
                    <Button as-child size="lg" class="gap-2 shadow-sm">
                        <Link :href="doctorRoutes.integrations()">
                            Ir para Integrações
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                    <Button as-child variant="secondary" size="lg" class="gap-2">
                        <Link :href="integrationRoutes.partners()"> Gerenciar integração </Link>
                    </Button>
                </div>

                <!-- Credenciais de conexão para compartilhar -->
                <Card class="mt-12 w-full max-w-2xl gap-0 py-0 text-left shadow-sm">
                    <CardContent class="space-y-4 px-6 py-6">
                        <div class="flex items-center gap-2">
                            <KeyRound class="size-5 text-primary" stroke-width="2" />
                            <h3 class="text-base font-bold text-foreground">Credenciais de Conexão</h3>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Compartilhe essas informações com a equipe técnica do parceiro para completar a integração.
                        </p>

                        <!-- Webhook URL -->
                        <div class="rounded-lg border border-border bg-muted/30 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Webhook URL</p>
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    @click="copyToClipboard(webhookUrl, 'success-webhook')"
                                >
                                    <CheckCheck v-if="copiedField === 'success-webhook'" class="size-3.5 text-green-600" />
                                    <Copy v-else class="size-3.5" />
                                    {{ copiedField === 'success-webhook' ? 'Copiado!' : 'Copiar' }}
                                </button>
                            </div>
                            <p class="mt-1 font-mono text-sm break-all text-foreground">{{ webhookUrl }}</p>
                        </div>

                        <!-- Client ID / API Key -->
                        <div v-if="form.client_id && form.auth_method !== 'bearer'" class="rounded-lg border border-border bg-muted/30 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">
                                    {{ form.auth_method === 'api_key' ? 'Chave de API' : 'Client ID' }}
                                </p>
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    @click="copyToClipboard(form.client_id, 'success-client-id')"
                                >
                                    <CheckCheck v-if="copiedField === 'success-client-id'" class="size-3.5 text-green-600" />
                                    <Copy v-else class="size-3.5" />
                                    {{ copiedField === 'success-client-id' ? 'Copiado!' : 'Copiar' }}
                                </button>
                            </div>
                            <p class="mt-1 font-mono text-sm text-foreground">{{ form.client_id }}</p>
                        </div>

                        <!-- Auth Method -->
                        <div class="rounded-lg border border-border bg-muted/30 px-4 py-3">
                            <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Método de autenticação</p>
                            <p class="mt-1 text-sm font-medium text-foreground">
                                {{ authMethods.find((m) => m.key === form.auth_method)?.label ?? form.auth_method }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            <Lock class="size-3.5 shrink-0" />
                            Secrets e tokens não são exibidos após esta tela por segurança. Salve-os agora.
                        </div>
                    </CardContent>
                </Card>

                <!-- Bento Grid de status -->
                <div class="mt-20 grid w-full grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Latência -->
                    <Card class="gap-0 py-0 text-left shadow-sm">
                        <CardContent class="flex flex-col gap-4 px-6 py-6">
                            <div class="flex items-center justify-between">
                                <Wifi class="size-5 text-primary" stroke-width="2" />
                                <Badge class="rounded-full bg-green-600 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase">
                                    Online
                                </Badge>
                            </div>
                            <div>
                                <h3 class="font-bold text-foreground">Latência</h3>
                                <p class="text-sm text-muted-foreground">24ms (Excelente)</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Certificado -->
                    <Card class="gap-0 py-0 text-left shadow-sm">
                        <CardContent class="flex flex-col gap-4 px-6 py-6">
                            <div class="flex items-center justify-between">
                                <ShieldCheck class="size-5 text-primary" stroke-width="2" />
                                <Badge class="rounded-full bg-green-600 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase">
                                    Criptografado
                                </Badge>
                            </div>
                            <div>
                                <h3 class="font-bold text-foreground">Certificado TLS</h3>
                                <p class="text-sm text-muted-foreground">Válido por 365 dias</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Sync -->
                    <Card class="gap-0 py-0 text-left shadow-sm">
                        <CardContent class="flex flex-col gap-4 px-6 py-6">
                            <div class="flex items-center justify-between">
                                <RefreshCw class="size-5 text-primary" stroke-width="2" />
                                <Badge class="rounded-full bg-green-600 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase">
                                    Ativo
                                </Badge>
                            </div>
                            <div>
                                <h3 class="font-bold text-foreground">Última Sincronização</h3>
                                <p class="text-sm text-muted-foreground">Agora mesmo</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Seção informativa: Processamento Seguro -->
                <div class="mt-20 grid w-full grid-cols-1 items-center gap-12 text-left md:grid-cols-2">
                    <div class="relative aspect-video overflow-hidden rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 shadow-lg">
                        <div class="flex h-full flex-col items-center justify-center gap-4 p-8">
                            <ShieldCheck class="size-16 text-primary/40" stroke-width="1" />
                            <p class="text-center text-sm font-semibold text-primary/60">Protocolo FHIR R4 Ativo</p>
                        </div>
                    </div>
                    <div class="pr-0 md:pr-12">
                        <span class="mb-4 block text-xs font-bold tracking-widest text-primary uppercase">Processamento Seguro</span>
                        <h2 class="mb-6 text-3xl leading-tight font-extrabold tracking-tight text-foreground">
                            Como seus dados são curados e integrados.
                        </h2>
                        <p class="mb-8 leading-relaxed text-muted-foreground">
                            Utilizamos protocolos de criptografia de ponta a ponta e camadas de abstração FHIR para garantir que cada registro médico
                            mantenha sua integridade durante o trânsito entre sistemas.
                        </p>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Check class="size-5 text-primary" stroke-width="2.5" />
                                <span class="text-sm font-medium">Conformidade LGPD</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Check class="size-5 text-primary" stroke-width="2.5" />
                                <span class="text-sm font-medium">Auditoria em tempo real</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Check class="size-5 text-primary" stroke-width="2.5" />
                                <span class="text-sm font-medium">Backup redundante em nuvem</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado: Wizard (não conectado ainda) -->
        <div v-else class="w-full px-8 py-12">
            <div class="flex w-full flex-col gap-8 md:flex-row">
                <!-- Sidebar do Wizard -->
                <aside class="hidden w-64 shrink-0 md:block">
                    <div class="rounded-xl border border-border/60 bg-muted/40 py-8 shadow-sm">
                        <div class="mb-8 px-8">
                            <h2 class="text-lg font-black tracking-tight text-primary">Wizard de Integração</h2>
                            <p class="mt-1 text-xs font-medium text-muted-foreground">Passo {{ currentStep }} de {{ totalSteps }}</p>
                        </div>

                        <nav class="flex flex-col gap-1">
                            <button
                                v-for="step in steps"
                                :key="step.num"
                                type="button"
                                @click="step.num < currentStep ? (currentStep = step.num) : undefined"
                                :class="[
                                    'flex items-center gap-3 py-3 text-sm font-semibold transition-all duration-200',
                                    currentStep === step.num
                                        ? 'ml-4 rounded-l-full bg-card pl-4 text-primary shadow-sm'
                                        : step.num < currentStep
                                          ? 'cursor-pointer px-8 text-primary/70 hover:bg-muted/60'
                                          : 'cursor-not-allowed px-8 text-muted-foreground',
                                ]"
                            >
                                <component
                                    :is="step.icon"
                                    class="size-5"
                                    :class="currentStep === step.num ? 'text-primary' : ''"
                                    stroke-width="1.75"
                                />
                                {{ step.label }}
                            </button>
                        </nav>

                        <div class="mt-12 border-t border-border px-8 pt-8">
                            <Link
                                :href="docsUrl"
                                class="mb-4 flex items-center gap-3 text-sm font-semibold text-muted-foreground transition-colors duration-150 hover:text-primary"
                            >
                                <BookOpenText class="size-5" stroke-width="1.75" />
                                Documentação
                            </Link>
                            <button
                                class="flex items-center gap-3 text-sm font-semibold text-muted-foreground transition-colors duration-150 hover:text-primary"
                            >
                                <HelpCircle class="size-5" stroke-width="1.75" />
                                Suporte
                            </button>
                            <Link
                                :href="integrationRoutes.partners()"
                                class="mt-4 block text-sm font-semibold text-red-500 transition-colors duration-150 hover:text-red-600 hover:underline"
                            >
                                Cancelar
                            </Link>
                        </div>
                    </div>
                </aside>

                <!-- Wizard Canvas -->
                <section class="flex-grow">
                    <div class="flex min-h-[600px] flex-col rounded-xl border border-border/60 bg-muted/30 p-8 shadow-sm md:p-12">
                        <!-- Barra de progresso -->
                        <div class="mb-10">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-sm font-bold tracking-wide text-primary">
                                    PASSO {{ currentStep }}: {{ stepTitles[currentStep].label }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ progressPercent.toFixed(0) }}% Completo</span>
                            </div>
                            <div
                                class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                :aria-valuenow="Math.round(progressPercent)"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <div
                                    class="h-full rounded-full bg-primary transition-all duration-500 ease-out"
                                    :style="{ width: progressPercent + '%' }"
                                />
                            </div>
                        </div>

                        <!-- Título dinâmico -->
                        <div class="mb-10">
                            <h1 class="text-3xl font-extrabold tracking-tight text-foreground">{{ stepTitles[currentStep].title }}</h1>
                            <p class="mt-2 max-w-lg text-muted-foreground">{{ stepTitles[currentStep].subtitle }}</p>
                            <Button variant="link" class="mt-3 h-auto px-0 text-primary" as-child>
                                <Link :href="docsUrl">
                                    <BookOpenText class="mr-2 size-4" />
                                    Ver documentação de integração
                                </Link>
                            </Button>
                        </div>

                        <!-- Erros de validação -->
                        <div
                            v-if="Object.keys(form.errors).length > 0 && connectionError"
                            class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3"
                        >
                            <p class="text-sm font-semibold text-red-800">Erro ao conectar parceiro:</p>
                            <ul class="mt-1 space-y-1">
                                <li v-for="(error, field) in form.errors" :key="field" class="text-sm text-red-700">{{ error }}</li>
                            </ul>
                        </div>

                        <!-- Conteúdo dos Steps -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="translate-x-4 opacity-0"
                            enter-to-class="translate-x-0 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="translate-x-0 opacity-100"
                            leave-to-class="-translate-x-4 opacity-0"
                            mode="out-in"
                        >
                            <!-- Step 1: Escolher parceiro + modo -->
                            <div v-if="currentStep === 1" key="step1" class="flex-grow space-y-8">
                                <!-- Parceiros -->
                                <div>
                                    <p class="mb-3 text-sm font-semibold text-foreground">Parceiro</p>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <button
                                            v-for="partner in availablePartners.filter((p) => p.available)"
                                            :key="partner.key"
                                            type="button"
                                            @click="selectPartner(partner.key)"
                                            :class="[
                                                'group relative flex flex-col items-center rounded-xl border bg-card p-6 text-center transition-all duration-200',
                                                selectedPartner === partner.key
                                                    ? 'border-primary/40 shadow-lg ring-2 ring-primary/20'
                                                    : 'border-border/60 hover:border-primary/20 hover:shadow-lg',
                                            ]"
                                        >
                                            <div class="mb-4 flex size-16 items-center justify-center rounded-lg bg-muted">
                                                <FlaskConical class="size-7 text-muted-foreground" stroke-width="1.5" />
                                            </div>
                                            <h3 class="font-bold text-foreground">{{ partner.name }}</h3>
                                            <p class="mt-1 text-xs text-muted-foreground">{{ partner.description }}</p>
                                            <div
                                                class="mt-4 flex items-center gap-1 text-xs font-bold text-primary opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                                            >
                                                Selecionar <ChevronRight class="size-3.5" />
                                            </div>
                                            <div
                                                v-if="selectedPartner === partner.key"
                                                class="absolute top-3 right-3 flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground"
                                            >
                                                <Check class="size-3.5" stroke-width="3" />
                                            </div>
                                        </button>

                                        <div
                                            class="flex cursor-not-allowed flex-col items-center rounded-xl border border-dashed border-border bg-muted/20 p-6 text-center opacity-50 grayscale"
                                        >
                                            <div class="mb-4 flex size-16 items-center justify-center rounded-lg bg-muted">
                                                <PlusCircle class="size-7 text-muted-foreground" stroke-width="1.5" />
                                            </div>
                                            <h3 class="font-bold text-muted-foreground">Outro</h3>
                                            <p class="mt-1 text-xs text-muted-foreground">(em breve)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modo de integração -->
                                <div v-if="selectedPartner">
                                    <p class="mb-3 text-sm font-semibold text-foreground">Como deseja integrar?</p>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <button
                                            v-for="mode in integrationModes"
                                            :key="mode.key"
                                            type="button"
                                            @click="selectMode(mode.key)"
                                            :class="[
                                                'flex items-start gap-3 rounded-xl border-2 p-5 text-left transition-all duration-200',
                                                integrationMode === mode.key
                                                    ? 'border-primary bg-primary/5 shadow-md'
                                                    : 'border-border bg-card hover:border-primary/40 hover:shadow-sm',
                                            ]"
                                        >
                                            <div
                                                :class="[
                                                    'flex size-10 shrink-0 items-center justify-center rounded-lg transition-colors duration-200',
                                                    integrationMode === mode.key
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'bg-muted text-muted-foreground',
                                                ]"
                                            >
                                                <component :is="mode.icon" class="size-5" stroke-width="2" />
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-bold text-foreground">{{ mode.label }}</h3>
                                                <p class="mt-0.5 text-xs leading-relaxed text-muted-foreground">{{ mode.description }}</p>
                                            </div>
                                            <div
                                                v-if="integrationMode === mode.key"
                                                class="ml-auto flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
                                            >
                                                <Check class="size-3.5" stroke-width="3" />
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Modo receive_only = Webhook -->
                            <div v-else-if="currentStep === 2 && isReceiveOnly" key="step2-receive" class="flex-grow space-y-6">
                                <Card class="gap-0 py-0 shadow-sm">
                                    <CardContent class="space-y-6 px-6 py-6">
                                        <p class="text-sm text-muted-foreground">
                                            Compartilhe a URL abaixo com a equipe técnica do
                                            <strong class="text-foreground">{{ selectedPartnerName }}</strong> para que eles enviem resultados de
                                            exame automaticamente.
                                        </p>

                                        <!-- Webhook URL -->
                                        <div class="rounded-lg border border-primary/20 bg-primary/5 px-5 py-4">
                                            <div class="flex items-center justify-between">
                                                <p class="text-[11px] font-bold tracking-widest text-primary uppercase">Webhook URL</p>
                                                <button
                                                    type="button"
                                                    class="flex items-center gap-1.5 rounded-md bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:bg-primary/20"
                                                    @click="copyToClipboard(webhookUrl, 'webhook-url')"
                                                >
                                                    <CheckCheck v-if="copiedField === 'webhook-url'" class="size-3.5 text-green-600" />
                                                    <Copy v-else class="size-3.5" />
                                                    {{ copiedField === 'webhook-url' ? 'Copiado!' : 'Copiar URL' }}
                                                </button>
                                            </div>
                                            <p class="mt-2 font-mono text-sm break-all text-foreground">
                                                {{ webhookUrl }}
                                            </p>
                                        </div>

                                        <!-- Contato -->
                                        <div class="space-y-2">
                                            <Label for="contact-email-receive" class="text-sm font-semibold"
                                                >E-mail de contato técnico do parceiro</Label
                                            >
                                            <Input
                                                id="contact-email-receive"
                                                v-model="form.contact_email"
                                                type="email"
                                                placeholder="suporte@parceiro.com.br"
                                            />
                                            <p class="text-[11px] text-muted-foreground">Para comunicação sobre a integração.</p>
                                        </div>

                                        <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            <Info class="size-3.5 shrink-0" />
                                            O parceiro precisará configurar a URL acima no sistema deles para enviar resultados via POST.
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Step 2: Modo full = Mapeamento -->
                            <div v-else-if="currentStep === 2 && !isReceiveOnly" key="step2-full" class="flex-grow space-y-6">
                                <Card class="gap-0 py-0 shadow-sm">
                                    <CardContent class="space-y-6 px-6 py-6">
                                        <div class="space-y-2">
                                            <Label for="partner-url" class="text-sm font-semibold">URL base da API *</Label>
                                            <div class="relative">
                                                <Globe class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input
                                                    id="partner-url"
                                                    v-model="form.base_url"
                                                    placeholder="https://api.parceiro.com.br/fhir/r4"
                                                    class="pl-10"
                                                />
                                            </div>
                                            <p v-if="form.errors.base_url" class="text-xs text-red-600">{{ form.errors.base_url }}</p>
                                            <p class="text-[11px] text-muted-foreground">Endpoint FHIR R4 ou URL base do serviço.</p>
                                        </div>
                                        <div class="grid gap-6 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="fhir-version" class="text-sm font-semibold">Versão FHIR</Label>
                                                <Input id="fhir-version" v-model="form.fhir_version" placeholder="R4" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="contact-email" class="text-sm font-semibold">E-mail de contato técnico</Label>
                                                <Input
                                                    id="contact-email"
                                                    v-model="form.contact_email"
                                                    type="email"
                                                    placeholder="suporte@parceiro.com.br"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="selectedPartner" class="rounded-lg bg-muted/50 px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <p class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase">
                                                    Webhook URL (envie ao parceiro)
                                                </p>
                                                <button
                                                    type="button"
                                                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                    @click="copyToClipboard(webhookUrl, 'webhook-url')"
                                                >
                                                    <CheckCheck v-if="copiedField === 'webhook-url'" class="size-3.5 text-green-600" />
                                                    <Copy v-else class="size-3.5" />
                                                    {{ copiedField === 'webhook-url' ? 'Copiado!' : 'Copiar' }}
                                                </button>
                                            </div>
                                            <p class="mt-1 font-mono text-sm text-foreground">
                                                {{ webhookUrl }}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Step 3: Auth (apenas modo full) -->
                            <div v-else-if="currentStep === 3 && !isReceiveOnly" key="step3" class="flex-grow space-y-6">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <button
                                        v-for="method in authMethods"
                                        :key="method.key"
                                        @click="form.auth_method = method.key"
                                        :class="[
                                            'flex items-start gap-3 rounded-xl border-2 p-4 text-left transition-all duration-200',
                                            form.auth_method === method.key
                                                ? 'border-primary bg-primary/5 shadow-md'
                                                : 'border-border bg-card hover:border-primary/40 hover:shadow-sm',
                                        ]"
                                    >
                                        <div
                                            :class="[
                                                'flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors duration-200',
                                                form.auth_method === method.key
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <component :is="method.icon" class="size-4" stroke-width="2" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-foreground">{{ method.label }}</h3>
                                            <p class="mt-0.5 text-xs leading-relaxed text-muted-foreground">{{ method.description }}</p>
                                        </div>
                                    </button>
                                </div>
                                <Transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="max-h-0 opacity-0"
                                    enter-to-class="max-h-96 opacity-100"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="max-h-96 opacity-100"
                                    leave-to-class="max-h-0 opacity-0"
                                >
                                    <Card v-if="form.auth_method" class="gap-0 overflow-hidden py-0 shadow-sm">
                                        <CardContent class="space-y-4 px-6 py-5">
                                            <h3 class="text-sm font-bold text-foreground">Credenciais</h3>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div v-if="form.auth_method === 'bearer'" class="space-y-2 sm:col-span-2">
                                                    <Label for="bearer-token" class="text-sm font-semibold">Bearer Token</Label>
                                                    <div class="flex gap-2">
                                                        <Input
                                                            id="bearer-token"
                                                            v-model="form.bearer_token"
                                                            type="password"
                                                            class="font-mono text-sm"
                                                            placeholder="Insira o token de acesso..."
                                                        />
                                                        <button
                                                            v-if="form.bearer_token"
                                                            type="button"
                                                            class="flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                            @click="copyToClipboard(form.bearer_token, 'bearer')"
                                                        >
                                                            <CheckCheck v-if="copiedField === 'bearer'" class="size-3.5 text-green-600" />
                                                            <Copy v-else class="size-3.5" />
                                                        </button>
                                                    </div>
                                                </div>
                                                <template v-else>
                                                    <div class="space-y-2">
                                                        <Label for="client-id" class="text-sm font-semibold">
                                                            {{ form.auth_method === 'api_key' ? 'Chave de API' : 'Client ID' }}
                                                        </Label>
                                                        <div class="flex gap-2">
                                                            <Input
                                                                id="client-id"
                                                                v-model="form.client_id"
                                                                class="font-mono text-sm"
                                                                placeholder="Insira aqui..."
                                                            />
                                                            <button
                                                                v-if="form.client_id"
                                                                type="button"
                                                                class="flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                                @click="copyToClipboard(form.client_id, 'client-id')"
                                                            >
                                                                <CheckCheck v-if="copiedField === 'client-id'" class="size-3.5 text-green-600" />
                                                                <Copy v-else class="size-3.5" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div v-if="form.auth_method === 'oauth2'" class="space-y-2">
                                                        <Label for="client-secret" class="text-sm font-semibold">Client Secret</Label>
                                                        <div class="flex gap-2">
                                                            <Input
                                                                id="client-secret"
                                                                v-model="form.client_secret"
                                                                type="password"
                                                                class="font-mono text-sm"
                                                                placeholder="••••••••••••"
                                                            />
                                                            <button
                                                                v-if="form.client_secret"
                                                                type="button"
                                                                class="flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                                @click="copyToClipboard(form.client_secret, 'client-secret')"
                                                            >
                                                                <CheckCheck v-if="copiedField === 'client-secret'" class="size-3.5 text-green-600" />
                                                                <Copy v-else class="size-3.5" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                                <Lock class="size-3.5 shrink-0" />
                                                Credenciais armazenadas com criptografia AES-256.
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Transition>
                            </div>

                            <!-- Step final: Revisão (step 4 no full, step 3 no receive_only) -->
                            <div v-else-if="isLastStep" key="step-review" class="flex-grow space-y-6">
                                <Card class="gap-0 py-0 shadow-sm">
                                    <CardContent class="divide-y divide-border/60 px-0 py-0">
                                        <label
                                            class="flex cursor-pointer items-center justify-between px-6 py-4 transition-colors duration-150 hover:bg-muted/30"
                                        >
                                            <div class="flex items-center gap-4">
                                                <div class="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                                    <ScanEye class="size-4" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-foreground">Enviar pedidos de exame</p>
                                                    <p class="text-xs text-muted-foreground">Permite enviar solicitações ao parceiro.</p>
                                                </div>
                                            </div>
                                            <Checkbox :checked="form.perm_send_orders" @update:checked="form.perm_send_orders = $event" />
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center justify-between px-6 py-4 transition-colors duration-150 hover:bg-muted/30"
                                        >
                                            <div class="flex items-center gap-4">
                                                <div class="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                                    <ScanEye class="size-4" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-foreground">Receber resultados</p>
                                                    <p class="text-xs text-muted-foreground">Permite que resultados cheguem ao prontuário.</p>
                                                </div>
                                            </div>
                                            <Checkbox :checked="form.perm_receive_results" @update:checked="form.perm_receive_results = $event" />
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center justify-between px-6 py-4 transition-colors duration-150 hover:bg-muted/30"
                                        >
                                            <div class="flex items-center gap-4">
                                                <div class="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                                    <ScanEye class="size-4" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-foreground">Receber webhooks</p>
                                                    <p class="text-xs text-muted-foreground">Notificações em tempo real do parceiro.</p>
                                                </div>
                                            </div>
                                            <Checkbox :checked="form.perm_webhook" @update:checked="form.perm_webhook = $event" />
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center justify-between px-6 py-4 transition-colors duration-150 hover:bg-muted/30"
                                        >
                                            <div class="flex items-center gap-4">
                                                <div class="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                                    <ScanEye class="size-4" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-foreground">Acessar dados do paciente</p>
                                                    <p class="text-xs text-muted-foreground">Dados básicos para identificação.</p>
                                                </div>
                                            </div>
                                            <Checkbox :checked="form.perm_patient_data" @update:checked="form.perm_patient_data = $event" />
                                        </label>
                                    </CardContent>
                                </Card>
                                <Card class="gap-0 border-0 py-0 shadow-sm" style="background-color: #c4e4e4">
                                    <CardContent class="flex items-start gap-4 px-6 py-5">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/60 text-primary">
                                            <Sparkles class="size-5" />
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-sm font-bold text-foreground">Resumo da conexão</h3>
                                            <p v-if="isReceiveOnly" class="text-sm text-foreground/70">
                                                <strong>{{ selectedPartnerName }}</strong> será conectado no modo
                                                <strong>apenas receber resultados</strong> via webhook.
                                            </p>
                                            <p v-else class="text-sm text-foreground/70">
                                                <strong>{{ selectedPartnerName }}</strong> será conectado via
                                                <strong>{{ authMethods.find((m) => m.key === form.auth_method)?.label ?? '—' }}</strong> com
                                                {{
                                                    [
                                                        form.perm_send_orders,
                                                        form.perm_receive_results,
                                                        form.perm_webhook,
                                                        form.perm_patient_data,
                                                    ].filter(Boolean).length
                                                }}
                                                permissão(ões) ativa(s).
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </Transition>

                        <!-- Footer Actions -->
                        <div class="mt-12 flex items-center justify-between gap-4 border-t border-border pt-8">
                            <Button
                                v-if="currentStep > 1"
                                variant="ghost"
                                @click="prevStep"
                                class="text-muted-foreground transition-all duration-200 hover:text-foreground"
                            >
                                <ArrowLeft class="mr-2 size-4" /> Voltar
                            </Button>
                            <Link
                                v-else
                                :href="integrationRoutes.partners()"
                                class="px-4 py-2 text-sm font-semibold text-primary transition-colors duration-150 hover:bg-primary/5"
                            >
                                Cancelar
                            </Link>
                            <div class="flex items-center gap-4">
                                <Button v-if="currentStep < totalSteps" @click="nextStep" :disabled="!canProceed" class="transition-all duration-200">
                                    Continuar <ArrowRight class="ml-2 size-4" />
                                </Button>
                                <Button
                                    v-else
                                    @click="handleConnect"
                                    :disabled="!canProceed || form.processing"
                                    class="bg-green-600 transition-all duration-200 hover:bg-green-700 hover:shadow-sm"
                                >
                                    <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                                    <Plug2 v-else class="mr-2 size-4" />
                                    {{ form.processing ? 'Conectando...' : 'Conectar Parceiro' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Dica de segurança -->
                    <div class="relative mt-8 overflow-hidden rounded-xl border border-border/60 bg-muted/50 p-6 backdrop-blur-md">
                        <div class="absolute top-0 bottom-0 left-0 w-1 bg-primary" />
                        <div class="flex items-start gap-4 pl-4">
                            <Info class="size-5 shrink-0 text-primary" stroke-width="2" />
                            <div>
                                <h4 class="text-sm font-bold text-primary">Dica de Segurança</h4>
                                <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                    A conexão é criptografada de ponta a ponta. Seus dados de login nunca são armazenados em texto plano, garantindo
                                    total privacidade do paciente.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
