<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import * as doctorRoutes from '@/routes/doctor';
import * as integrationRoutes from '@/routes/doctor/integrations';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FlaskConical, Shield, Building2, Plus, MoreVertical, ChevronDown, Sparkles, Info, RefreshCw, Loader2, BookOpenText } from 'lucide-vue-next';

interface PartnerEvent {
    id: number;
    type: string;
    status: string;
    direction: string;
    created_at: string;
    error_message: string | null;
}

interface Partner {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
    base_url: string;
    capabilities: string[];
    fhir_version: string;
    last_sync_at: string | null;
    contact_email: string | null;
    stats: {
        sent: number;
        received: number;
        errors: number;
    };
    recentEvents: PartnerEvent[];
}

interface CriticalEvent {
    id: number;
    partner_name: string;
    event_type: string;
    error_message: string | null;
    created_at: string;
}

interface Props {
    partners: Partner[];
    criticalEvents: CriticalEvent[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Integrações', href: doctorRoutes.integrations().url },
    { title: 'Gerenciar Parceiros', href: integrationRoutes.partners().url },
];

// Tab ativa
type TabOption = 'all' | 'critical' | 'pending';
const activeTab = ref<TabOption>('all');

// Filtro de parceiros por tab
const filteredPartners = computed(() => {
    switch (activeTab.value) {
        case 'critical':
            return props.partners.filter((p) => p.status === 'error' || p.stats.errors > 0);
        case 'pending':
            return props.partners.filter((p) => p.status === 'pending');
        default:
            return props.partners;
    }
});

// Ícone por tipo
const getPartnerIcon = (type: string) => {
    switch (type) {
        case 'laboratory':
            return FlaskConical;
        case 'pharmacy':
            return Building2;
        case 'insurance':
            return Shield;
        default:
            return Building2;
    }
};

// Status display
const getStatusDisplay = (status: string) => {
    switch (status) {
        case 'active':
            return { label: 'CONEXÃO ATIVA', class: 'text-green-700' };
        case 'pending':
            return { label: 'PENDENTE', class: 'text-amber-600' };
        case 'error':
            return { label: 'AÇÃO NECESSÁRIA', class: 'text-red-600' };
        case 'inactive':
            return { label: 'INATIVO', class: 'text-muted-foreground' };
        case 'suspended':
            return { label: 'SUSPENSO', class: 'text-red-600' };
        default:
            return { label: status.toUpperCase(), class: 'text-muted-foreground' };
    }
};

// Health bar por parceiro
const getHealthPercent = (partner: Partner) => {
    const total = partner.stats.sent + partner.stats.received;
    if (total === 0) return 100;
    const errorRate = partner.stats.errors / total;
    return Math.max(0, Math.round((1 - errorRate) * 100));
};

const getHealthBarColor = (percent: number) => {
    if (percent >= 90) return 'bg-green-500';
    if (percent >= 70) return 'bg-amber-500';
    return 'bg-red-500';
};

// Format numbers
const formatCount = (n: number) => {
    if (n >= 1000) return (n / 1000).toFixed(1) + 'k';
    return String(n);
};

// Format relative time
const formatRelativeTime = (dateStr: string) => {
    const ts = new Date(dateStr).getTime();
    if (isNaN(ts)) return 'Data inválida';
    const diff = Date.now() - ts;
    const minutes = Math.floor(diff / 60000);
    if (minutes < 1) return 'Agora';
    if (minutes < 60) return `Há ${minutes} min`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `Há ${hours}h`;
    return `Há ${Math.floor(hours / 24)}d`;
};

// Format event type
const formatEventType = (type: string) => {
    const map: Record<string, string> = {
        exam_order_sent: 'Pedido de exame enviado',
        exam_result_received: 'Resultado de exame recebido',
        prescription_sent: 'Receita enviada',
        webhook_received: 'Webhook recebido',
    };
    return map[type] ?? type;
};

// Toggle de atividade recente por parceiro (objeto plano: Set em ref tem reatividade frágil com .has() no template)
const openActivities = ref<Record<number, boolean>>({});

const isActivityOpen = (id: number) => !!openActivities.value[id];

const toggleActivity = (id: number) => {
    openActivities.value = {
        ...openActivities.value,
        [id]: !openActivities.value[id],
    };
};

// Partner details dialog
const partnerForDetails = ref<Partner | null>(null);

const openPartnerDetails = (partner: Partner) => {
    partnerForDetails.value = partner;
};

const onDetailsOpenChange = (open: boolean) => {
    if (!open) {
        partnerForDetails.value = null;
    }
};

// Sync functionality
const syncingPartners = ref<Set<number>>(new Set());

const handleSync = async (partnerId: number) => {
    syncingPartners.value = new Set([...syncingPartners.value, partnerId]);

    router.post(
        `/doctor/integrations/${partnerId}/sync`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                syncingPartners.value = new Set([...syncingPartners.value].filter((id) => id !== partnerId));
            },
            onError: () => {
                syncingPartners.value = new Set([...syncingPartners.value].filter((id) => id !== partnerId));
            },
        },
    );
};

// Traffic data from real events
const trafficDays = computed(() => {
    const days = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];
    const today = new Date();
    const result = [];

    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        result.push({
            day: days[date.getDay()],
            inbound: 0,
            outbound: 0,
        });
    }

    // Aggregate events from all partners
    for (const partner of props.partners) {
        for (const event of partner.recentEvents) {
            const eventDate = new Date(event.created_at);
            const daysDiff = Math.floor((today.getTime() - eventDate.getTime()) / 86400000);
            if (daysDiff >= 0 && daysDiff < 7) {
                const idx = 6 - daysDiff;
                if (event.direction === 'inbound') {
                    result[idx].inbound++;
                } else {
                    result[idx].outbound++;
                }
            }
        }
    }

    return result;
});

const maxTraffic = computed(() => Math.max(1, ...trafficDays.value.flatMap((d) => [d.inbound, d.outbound])));

// Insights
const totalSent = computed(() => props.partners.reduce((sum, p) => sum + p.stats.sent, 0));
const totalReceived = computed(() => props.partners.reduce((sum, p) => sum + p.stats.received, 0));
const overallHealthPercent = computed(() => {
    const total = totalSent.value + totalReceived.value;
    if (total === 0) return 100;
    const totalErrors = props.partners.reduce((sum, p) => sum + p.stats.errors, 0);
    return Math.max(0, Math.round((1 - totalErrors / total) * 100));
});

const docsUrl = '/docs/interoperabilidade';
</script>

<template>
    <Head title="Gerenciar Parceiros · Integrações" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full space-y-6 p-6 pb-16">
            <!-- 1. Cabeçalho + Tabs -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Hub de Integrações</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Gerencie e monitore o fluxo de dados clínicos entre {{ partners.length }} parceiro(s).
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="docsUrl">
                            <BookOpenText class="mr-2 size-4" />
                            Documentação
                        </Link>
                    </Button>
                    <div class="flex items-center gap-1 rounded-lg border border-border bg-card p-1">
                        <button
                            v-for="tab in [
                                { key: 'all', label: 'Todos' },
                                { key: 'critical', label: 'Críticos' },
                                { key: 'pending', label: 'Pendentes' },
                            ] as { key: TabOption; label: string }[]"
                            :key="tab.key"
                            type="button"
                            @click="activeTab = tab.key"
                            :class="[
                                'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                activeTab === tab.key
                                    ? 'bg-foreground text-background shadow-sm'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Banner de Eventos Críticos -->
            <div v-if="criticalEvents.length" class="overflow-hidden rounded-xl border border-red-200/50 bg-card shadow-sm">
                <div class="flex">
                    <div class="w-1.5 shrink-0 rounded-l-xl bg-red-600" />
                    <div class="flex-1 space-y-3 px-5 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <img src="/images/icons/exclamation.svg" alt="" class="size-5" />
                                <span class="text-sm font-bold text-foreground">Eventos Críticos ({{ criticalEvents.length }})</span>
                            </div>
                            <span class="flex items-center gap-1.5 text-[10px] font-bold tracking-widest text-red-600 uppercase">
                                <span class="size-1.5 animate-pulse rounded-full bg-red-500" />
                                Últimas 24h
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="event in criticalEvents"
                                :key="event.id"
                                class="flex items-center justify-between rounded px-3 py-2.5 transition-colors duration-150 hover:brightness-95"
                                style="background: rgba(186, 26, 26, 0.05)"
                            >
                                <div class="flex items-center gap-3">
                                    <Badge class="rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase">
                                        FALHA
                                    </Badge>
                                    <span class="text-sm text-foreground/80">
                                        {{ event.partner_name }}: {{ event.error_message || event.event_type }}
                                    </span>
                                </div>
                                <span class="shrink-0 text-sm text-muted-foreground">{{ formatRelativeTime(event.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Grid de Parceiros -->
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Card
                    v-for="partner in filteredPartners"
                    :key="partner.id"
                    class="gap-0 py-0 shadow-sm transition-shadow duration-300 hover:shadow-lg"
                >
                    <CardContent class="space-y-5 px-5 py-5">
                        <!-- Cabeçalho do parceiro -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex size-9 items-center justify-center rounded-lg bg-muted text-foreground">
                                    <component :is="getPartnerIcon(partner.type)" class="size-4.5" stroke-width="1.75" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-foreground">{{ partner.name }}</h3>
                                    <span :class="getStatusDisplay(partner.status).class" class="text-[10px] font-bold tracking-widest uppercase">
                                        {{ getStatusDisplay(partner.status).label }}
                                    </span>
                                </div>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="size-8 p-0 text-muted-foreground transition-colors duration-150 hover:text-foreground"
                                        :aria-label="`Ações para ${partner.name}`"
                                    >
                                        <MoreVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-44">
                                    <DropdownMenuItem class="cursor-pointer gap-2" @select="openPartnerDetails(partner)">
                                        <Info class="size-4" />
                                        Detalhes
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="partner.status === 'active'"
                                        class="cursor-pointer gap-2"
                                        @select="handleSync(partner.id)"
                                        :disabled="syncingPartners.has(partner.id)"
                                    >
                                        <RefreshCw class="size-4" :class="{ 'animate-spin': syncingPartners.has(partner.id) }" />
                                        Sincronizar agora
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <!-- Stats: ENVIADOS / RECEBIDOS / ERROS -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="flex flex-col items-center gap-1 rounded bg-[#F2F4F5] px-3 pt-3 pb-3.5">
                                <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Enviados</p>
                                <p class="text-2xl font-bold text-foreground">{{ formatCount(partner.stats.sent) }}</p>
                            </div>
                            <div class="flex flex-col items-center gap-1 rounded bg-[#F2F4F5] px-3 pt-3 pb-3.5">
                                <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Recebidos</p>
                                <p class="text-2xl font-bold text-foreground">{{ formatCount(partner.stats.received) }}</p>
                            </div>
                            <div
                                class="flex flex-col items-center gap-1 rounded px-3 pt-3 pb-3.5"
                                :class="partner.stats.errors > 0 ? 'bg-red-50' : 'bg-[#F2F4F5]'"
                            >
                                <p
                                    class="text-[10px] font-bold tracking-widest uppercase"
                                    :class="partner.stats.errors > 0 ? 'text-red-600' : 'text-muted-foreground'"
                                >
                                    Erros
                                </p>
                                <p class="text-2xl font-bold" :class="partner.stats.errors > 0 ? 'text-red-600' : 'text-foreground'">
                                    {{ formatCount(partner.stats.errors) }}
                                </p>
                            </div>
                        </div>

                        <!-- Barra de saúde -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">Saúde dos Dados</span>
                                <span class="font-semibold text-foreground">{{ getHealthPercent(partner) }}%</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    :class="getHealthBarColor(getHealthPercent(partner))"
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: getHealthPercent(partner) + '%' }"
                                />
                            </div>
                        </div>

                        <!-- Botão Sincronizar -->
                        <Button
                            v-if="partner.status === 'active'"
                            variant="outline"
                            size="sm"
                            class="w-full gap-2"
                            :disabled="syncingPartners.has(partner.id)"
                            @click="handleSync(partner.id)"
                        >
                            <Loader2 v-if="syncingPartners.has(partner.id)" class="size-4 animate-spin" />
                            <RefreshCw v-else class="size-4" />
                            {{ syncingPartners.has(partner.id) ? 'Sincronizando...' : 'Sincronizar agora' }}
                        </Button>
                        <Button variant="ghost" size="sm" class="w-full gap-2 text-muted-foreground hover:text-primary" as-child>
                            <Link :href="docsUrl">
                                <BookOpenText class="size-4" />
                                Ver docs
                            </Link>
                        </Button>

                        <!-- Atividade Recente (collapsible com animação) -->
                        <div v-if="partner.recentEvents.length">
                            <button
                                @click="toggleActivity(partner.id)"
                                class="flex w-full items-center justify-between rounded-md px-1 py-1 text-xs font-bold tracking-widest text-foreground uppercase transition-colors duration-150 hover:bg-muted hover:text-primary"
                            >
                                Atividade Recente
                                <ChevronDown
                                    class="size-3.5 text-muted-foreground transition-transform duration-300 ease-in-out"
                                    :class="{ 'rotate-180': isActivityOpen(partner.id) }"
                                />
                            </button>
                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="max-h-0 opacity-0"
                                enter-to-class="max-h-40 opacity-100"
                                leave-active-class="transition-all duration-200 ease-in"
                                leave-from-class="max-h-40 opacity-100"
                                leave-to-class="max-h-0 opacity-0"
                            >
                                <div v-show="isActivityOpen(partner.id)" class="overflow-hidden">
                                    <div class="mt-2 space-y-1.5">
                                        <div
                                            v-for="event in partner.recentEvents"
                                            :key="event.id"
                                            class="flex items-center justify-between rounded px-1 py-1 text-xs transition-colors duration-150 hover:bg-muted/50"
                                        >
                                            <span class="text-muted-foreground italic">{{ formatEventType(event.type) }}</span>
                                            <span class="shrink-0 text-muted-foreground/70">{{ formatRelativeTime(event.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card: Nova Integração -->
                <Link
                    :href="integrationRoutes.connect()"
                    class="flex min-h-[280px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/20 bg-card px-6 py-10 text-center transition-all duration-300 hover:border-primary/40 hover:bg-muted/20 hover:shadow-lg"
                >
                    <div
                        class="mb-4 flex size-12 items-center justify-center rounded-xl border border-border bg-background text-muted-foreground shadow-sm transition-transform duration-300 hover:scale-110"
                    >
                        <Plus class="size-5" stroke-width="2" />
                    </div>
                    <span class="text-base font-bold text-foreground">Nova Integração</span>
                    <span class="mt-1 max-w-[200px] text-xs leading-relaxed text-muted-foreground">
                        Conecte rapidamente via HL7, FHIR ou endpoints customizados.
                    </span>
                </Link>
            </div>

            <!-- Estado vazio -->
            <div v-if="partners.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                <div class="mb-5 flex size-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    <Building2 class="size-7 opacity-70" stroke-width="1.75" />
                </div>
                <h3 class="text-lg font-semibold text-foreground">Nenhum parceiro conectado</h3>
                <p class="mt-2 max-w-md text-sm text-muted-foreground">
                    Conecte seu primeiro parceiro para começar a automatizar o fluxo de dados clínicos.
                </p>
                <Button class="mt-6" as-child>
                    <Link :href="integrationRoutes.connect()">Conectar primeiro parceiro</Link>
                </Button>
                <Button class="mt-3" variant="link" as-child>
                    <Link :href="docsUrl">
                        <BookOpenText class="mr-2 size-4" />
                        Ver documentação
                    </Link>
                </Button>
            </div>

            <!-- 4. Distribuição de Tráfego + Insights -->
            <div v-if="partners.length > 0" class="grid gap-5 lg:grid-cols-3">
                <!-- Gráfico de Tráfego -->
                <Card class="gap-0 py-0 shadow-sm transition-shadow duration-300 hover:shadow-lg lg:col-span-2">
                    <CardContent class="space-y-5 px-6 py-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-foreground">Distribuição de Tráfego</h3>
                            <div class="flex items-center gap-4 text-xs">
                                <span class="flex items-center gap-1.5">
                                    <span class="size-2.5 rounded-full bg-primary" />
                                    Entrada
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="size-2.5 rounded-full bg-primary/40" />
                                    Saída
                                </span>
                            </div>
                        </div>

                        <!-- Gráfico de barras -->
                        <div class="flex items-end justify-between gap-3 pt-2" style="height: 180px">
                            <div v-for="day in trafficDays" :key="day.day" class="group flex flex-1 flex-col items-center gap-1">
                                <div class="flex w-full items-end justify-center gap-1" style="height: 150px">
                                    <div
                                        class="w-[40%] rounded-t-sm bg-primary transition-all duration-500 group-hover:brightness-110"
                                        :style="{ height: (day.inbound / maxTraffic) * 100 + '%' }"
                                    />
                                    <div
                                        class="w-[40%] rounded-t-sm bg-primary/35 transition-all duration-500 group-hover:brightness-110"
                                        :style="{ height: (day.outbound / maxTraffic) * 100 + '%' }"
                                    />
                                </div>
                                <span
                                    class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase transition-colors duration-150 group-hover:text-foreground"
                                    >{{ day.day }}</span
                                >
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Insights de Integração -->
                <Card
                    class="gap-0 overflow-hidden border-0 py-0 shadow-sm transition-shadow duration-300 hover:shadow-lg"
                    style="background-color: #1a6b5a"
                >
                    <CardContent class="relative flex h-full flex-col justify-between px-6 py-6">
                        <!-- Ícone decorativo -->
                        <div class="absolute -top-3 -right-3 text-white/5">
                            <Sparkles class="size-28" stroke-width="1" />
                        </div>

                        <div class="relative space-y-3">
                            <h3 class="text-xl font-bold text-white">Insights de Integração</h3>
                            <p class="text-sm leading-relaxed text-white/70">
                                {{ partners.length }} parceiro(s) conectado(s) com {{ totalSent + totalReceived }} eventos processados.
                            </p>
                        </div>

                        <div class="relative mt-6 space-y-3">
                            <div class="rounded-lg bg-white/10 px-4 py-3 backdrop-blur-sm transition-colors duration-200 hover:bg-white/15">
                                <span class="text-2xl font-bold text-white">{{ overallHealthPercent }}%</span>
                                <div class="mt-0.5 text-[10px] font-bold tracking-widest text-white/60 uppercase">Taxa de Sucesso Global</div>
                            </div>
                            <div class="rounded-lg bg-white/10 px-4 py-3 backdrop-blur-sm transition-colors duration-200 hover:bg-white/15">
                                <span class="text-2xl font-bold text-white">{{ formatCount(totalReceived) }}</span>
                                <div class="mt-0.5 text-[10px] font-bold tracking-widest text-white/60 uppercase">Resultados Recebidos</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog :open="partnerForDetails !== null" @update:open="onDetailsOpenChange">
            <DialogContent v-if="partnerForDetails" class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-muted text-foreground">
                            <component :is="getPartnerIcon(partnerForDetails.type)" class="size-5" stroke-width="1.75" />
                        </div>
                        <div>
                            <DialogTitle class="text-left">{{ partnerForDetails.name }}</DialogTitle>
                            <DialogDescription class="text-left">
                                <span
                                    :class="getStatusDisplay(partnerForDetails.status).class"
                                    class="text-[10px] font-bold tracking-widest uppercase"
                                >
                                    {{ getStatusDisplay(partnerForDetails.status).label }}
                                </span>
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="space-y-4 pt-2">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="flex flex-col items-center gap-1 rounded bg-muted/60 px-2 py-3">
                            <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Enviados</p>
                            <p class="text-xl font-bold text-foreground">{{ formatCount(partnerForDetails.stats.sent) }}</p>
                        </div>
                        <div class="flex flex-col items-center gap-1 rounded bg-muted/60 px-2 py-3">
                            <p class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Recebidos</p>
                            <p class="text-xl font-bold text-foreground">{{ formatCount(partnerForDetails.stats.received) }}</p>
                        </div>
                        <div
                            class="flex flex-col items-center gap-1 rounded px-2 py-3"
                            :class="partnerForDetails.stats.errors > 0 ? 'bg-red-50' : 'bg-muted/60'"
                        >
                            <p
                                class="text-[10px] font-bold tracking-widest uppercase"
                                :class="partnerForDetails.stats.errors > 0 ? 'text-red-600' : 'text-muted-foreground'"
                            >
                                Erros
                            </p>
                            <p class="text-xl font-bold" :class="partnerForDetails.stats.errors > 0 ? 'text-red-600' : 'text-foreground'">
                                {{ formatCount(partnerForDetails.stats.errors) }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Saúde dos Dados</span>
                            <span class="font-semibold text-foreground">{{ getHealthPercent(partnerForDetails) }}%</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                :class="getHealthBarColor(getHealthPercent(partnerForDetails))"
                                class="h-full rounded-full"
                                :style="{ width: getHealthPercent(partnerForDetails) + '%' }"
                            />
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Tipo</span>
                            <span class="font-medium text-foreground capitalize">{{ partnerForDetails.type }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">FHIR</span>
                            <span class="font-medium text-foreground">{{ partnerForDetails.fhir_version }}</span>
                        </div>
                        <div v-if="partnerForDetails.last_sync_at" class="flex justify-between">
                            <span class="text-muted-foreground">Última sync</span>
                            <span class="font-medium text-foreground">{{ formatRelativeTime(partnerForDetails.last_sync_at) }}</span>
                        </div>
                    </div>

                    <div v-if="partnerForDetails.recentEvents.length">
                        <p class="mb-2 text-[10px] font-bold tracking-widest text-muted-foreground uppercase">Atividade recente</p>
                        <ul class="space-y-2">
                            <li
                                v-for="event in partnerForDetails.recentEvents"
                                :key="event.id"
                                class="flex items-center justify-between rounded-md border border-border/60 px-3 py-2 text-xs"
                            >
                                <span class="text-muted-foreground">{{ formatEventType(event.type) }}</span>
                                <span class="shrink-0 text-muted-foreground/80">{{ formatRelativeTime(event.created_at) }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <Button variant="default" size="sm" class="flex-1" as-child>
                            <Link :href="`/doctor/integrations/${partnerForDetails.id}`">Ver detalhes completos</Link>
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
