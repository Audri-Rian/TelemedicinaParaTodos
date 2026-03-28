<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import * as doctorRoutes from '@/routes/doctor';
import * as integrationRoutes from '@/routes/doctor/integrations';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    FlaskConical, Building2, Shield, ArrowLeft, RefreshCw,
    CheckCircle2, XCircle, Clock, ArrowUpRight, ArrowDownLeft,
    Loader2, Globe, KeyRound, Calendar, Mail,
} from 'lucide-vue-next';

interface PartnerEvent {
    id: number;
    type: string;
    status: string;
    direction: string;
    resource_type: string | null;
    resource_id: string | null;
    external_id: string | null;
    error_message: string | null;
    duration_ms: number | null;
    created_at: string;
}

interface Props {
    partner: {
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
        created_at: string;
    };
    events: PartnerEvent[];
    stats: {
        sent: number;
        received: number;
        errors: number;
        success_rate: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Integrações', href: doctorRoutes.integrations().url },
    { title: 'Parceiros', href: integrationRoutes.partners().url },
    { title: props.partner.name, href: `/doctor/integrations/${props.partner.id}` },
];

const getPartnerIcon = (type: string) => {
    switch (type) {
        case 'laboratory': return FlaskConical;
        case 'pharmacy': return Building2;
        case 'insurance': return Shield;
        default: return Building2;
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active': return { label: 'Ativo', class: 'border-green-200 bg-green-50 text-green-800' };
        case 'pending': return { label: 'Pendente', class: 'border-amber-200 bg-amber-50 text-amber-800' };
        case 'error': return { label: 'Erro', class: 'border-red-200 bg-red-50 text-red-800' };
        case 'inactive': return { label: 'Inativo', class: 'border-border bg-muted text-muted-foreground' };
        default: return { label: status, class: 'border-border bg-muted text-muted-foreground' };
    }
};

const getEventStatusIcon = (status: string) => {
    switch (status) {
        case 'success': return { icon: CheckCircle2, class: 'text-green-600' };
        case 'failed': return { icon: XCircle, class: 'text-red-600' };
        case 'processing': return { icon: Loader2, class: 'text-amber-600 animate-spin' };
        default: return { icon: Clock, class: 'text-muted-foreground' };
    }
};

const formatEventType = (type: string) => {
    const map: Record<string, string> = {
        'exam_order_sent': 'Pedido de exame enviado',
        'exam_result_received': 'Resultado de exame recebido',
        'prescription_sent': 'Receita enviada',
        'webhook_received': 'Webhook recebido',
    };
    return map[type] ?? type;
};

const formatRelativeTime = (dateStr: string) => {
    const diff = Date.now() - new Date(dateStr).getTime();
    const minutes = Math.floor(diff / 60000);
    if (minutes < 1) return 'Agora';
    if (minutes < 60) return `Há ${minutes} min`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `Há ${hours}h`;
    return `Há ${Math.floor(hours / 24)}d`;
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const syncing = ref(false);
const syncError = ref<string | null>(null);

const handleSync = () => {
    syncing.value = true;
    syncError.value = null;
    router.post(`/doctor/integrations/${props.partner.id}/sync`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            syncing.value = false;
        },
        onError: () => {
            syncing.value = false;
            syncError.value = 'Falha ao sincronizar. Tente novamente em alguns instantes.';
        },
        onFinish: () => {
            syncing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`${partner.name} · Integrações`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full space-y-8 p-6 pb-16">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="integrationRoutes.partners()">
                            <ArrowLeft class="size-4" />
                        </Link>
                    </Button>
                    <div class="flex size-12 items-center justify-center rounded-xl bg-muted text-foreground">
                        <component :is="getPartnerIcon(partner.type)" class="size-6" stroke-width="1.75" />
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-foreground">{{ partner.name }}</h1>
                            <Badge variant="outline" :class="getStatusBadge(partner.status).class" class="text-[10px] font-semibold uppercase tracking-wide">
                                {{ getStatusBadge(partner.status).label }}
                            </Badge>
                        </div>
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ partner.slug }} · FHIR {{ partner.fhir_version }}</p>
                    </div>
                </div>
                <Button
                    v-if="partner.status === 'active'"
                    :disabled="syncing"
                    @click="handleSync"
                    class="gap-2"
                >
                    <Loader2 v-if="syncing" class="size-4 animate-spin" />
                    <RefreshCw v-else class="size-4" />
                    {{ syncing ? 'Sincronizando...' : 'Sincronizar agora' }}
                </Button>
            </div>

            <!-- Sync error banner -->
            <div v-if="syncError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm text-red-800">{{ syncError }}</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-2 px-5 py-5">
                        <span class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Enviados</span>
                        <p class="text-3xl font-bold text-foreground">{{ stats.sent }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-2 px-5 py-5">
                        <span class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Recebidos</span>
                        <p class="text-3xl font-bold text-foreground">{{ stats.received }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-2 px-5 py-5">
                        <span class="text-xs font-bold uppercase tracking-widest" :class="stats.errors > 0 ? 'text-red-600' : 'text-muted-foreground'">Erros</span>
                        <p class="text-3xl font-bold" :class="stats.errors > 0 ? 'text-red-600' : 'text-foreground'">{{ stats.errors }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-2 px-5 py-5">
                        <span class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Taxa de sucesso</span>
                        <p class="text-3xl font-bold text-foreground">{{ stats.success_rate }}%</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Info + Events -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Info do parceiro -->
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-4 px-6 py-6">
                        <h3 class="text-lg font-bold text-foreground">Informações</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <Globe class="size-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">URL base</p>
                                    <p class="font-mono text-xs text-foreground break-all">{{ partner.base_url }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <KeyRound class="size-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Tipo</p>
                                    <p class="font-medium text-foreground capitalize">{{ partner.type }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Calendar class="size-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Conectado em</p>
                                    <p class="font-medium text-foreground">{{ formatDate(partner.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="partner.last_sync_at" class="flex items-center gap-3">
                                <RefreshCw class="size-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Última sync</p>
                                    <p class="font-medium text-foreground">{{ formatDate(partner.last_sync_at) }}</p>
                                </div>
                            </div>
                            <div v-if="partner.contact_email" class="flex items-center gap-3">
                                <Mail class="size-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Contato</p>
                                    <p class="font-medium text-foreground">{{ partner.contact_email }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="partner.capabilities.length" class="pt-2">
                            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-muted-foreground">Capabilities</p>
                            <div class="flex flex-wrap gap-1.5">
                                <Badge v-for="cap in partner.capabilities" :key="cap" variant="secondary" class="text-[10px]">
                                    {{ cap }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Log de eventos -->
                <Card class="gap-0 py-0 shadow-sm lg:col-span-2">
                    <CardContent class="space-y-4 px-6 py-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-foreground">Log de Eventos</h3>
                            <span class="text-xs text-muted-foreground">Últimos 50 eventos</span>
                        </div>

                        <div v-if="events.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                            <Clock class="mb-3 size-8 text-muted-foreground/50" />
                            <p class="text-sm text-muted-foreground">Nenhum evento registrado ainda.</p>
                        </div>

                        <div v-else class="max-h-[500px] space-y-2 overflow-y-auto">
                            <div
                                v-for="event in events"
                                :key="event.id"
                                class="flex items-start gap-3 rounded-lg border border-border/60 px-4 py-3 transition-colors hover:bg-muted/30"
                            >
                                <component
                                    :is="getEventStatusIcon(event.status).icon"
                                    class="mt-0.5 size-4 shrink-0"
                                    :class="getEventStatusIcon(event.status).class"
                                    stroke-width="2"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-foreground">{{ formatEventType(event.type) }}</span>
                                        <component
                                            :is="event.direction === 'outbound' ? ArrowUpRight : ArrowDownLeft"
                                            class="size-3 text-muted-foreground"
                                        />
                                        <Badge v-if="event.status === 'failed'" variant="destructive" class="text-[9px] px-1.5 py-0">
                                            FALHA
                                        </Badge>
                                    </div>
                                    <div class="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                                        <span>{{ formatRelativeTime(event.created_at) }}</span>
                                        <span v-if="event.duration_ms">{{ event.duration_ms }}ms</span>
                                        <span v-if="event.external_id" class="font-mono">ID: {{ event.external_id }}</span>
                                    </div>
                                    <p v-if="event.error_message" class="mt-1 text-xs text-red-600">
                                        {{ event.error_message }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
