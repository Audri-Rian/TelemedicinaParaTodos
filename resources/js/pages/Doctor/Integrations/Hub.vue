<script setup lang="ts">
import ComingSoonOverlay from '@/components/ComingSoonOverlay.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import * as integrationRoutes from '@/routes/doctor/integrations';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    BookOpenText,
    CalendarClock,
    CheckCircle2,
    FileBarChart,
    FlaskConical,
    FlaskRound,
    Plus,
    RefreshCw,
    Settings2,
    Shield,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    stats: {
        activeIntegrations: number;
        syncedExams: number;
        lastSync: string | null;
        errors24h: number;
    };
    laboratories: Array<{
        id: number;
        name: string;
        slug: string;
        status: string;
        last_sync_at: string | null;
    }>;
    nextSyncAt: string | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Integrações', href: doctorRoutes.integrations().url },
];

const formatLastSync = computed(() => {
    if (!props.stats.lastSync) return '—';
    const date = new Date(props.stats.lastSync);
    return date.toLocaleString('pt-BR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' });
});

// MVP 1: indicador de laboratório piloto ainda não definido.
// TODO: quando o backend expor settings.is_pilot na Laboratory, trocar a heurística
// "primeiro lab ativo" por: props.laboratories.find(l => l.settings?.is_pilot === true).
// Até lá, qualquer lab ativo é tratado como piloto — é uma aproximação segura no MVP.
const pilotLab = computed(() => props.laboratories.find((l) => l.status === 'active') ?? null);

const formatNextSync = computed(() => {
    if (!props.nextSyncAt) return null;
    const date = new Date(props.nextSyncAt);
    if (isNaN(date.getTime())) return null;
    return date.toLocaleString('pt-BR', {
        weekday: 'long',
        hour: '2-digit',
        minute: '2-digit',
        timeZoneName: 'short',
    });
});
const hasActiveLabs = computed(() => pilotLab.value !== null);
const needsPilotLabSetup = computed(() => !pilotLab.value);
const docsUrl = '/docs/interoperabilidade';
</script>

<template>
    <Head title="Hub de Integrações" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full space-y-10 p-6 pb-16">
            <!-- 1. Cabeçalho -->
            <header class="space-y-2">
                <h1 class="text-4xl font-bold tracking-tight text-foreground">Hub de Integrações</h1>
                <p class="max-w-2xl text-base text-muted-foreground">Gerencie e monitore as conexões clínicas da sua rede.</p>
                <Button variant="link" class="h-auto p-0 text-primary" as-child>
                    <Link :href="docsUrl">
                        <BookOpenText class="mr-2 size-4" />
                        Ver documentação de interoperabilidade
                    </Link>
                </Button>
            </header>

            <!-- 1b. Banner: Laboratório piloto (MVP 1) -->
            <!--
                Placeholder para o MVP 1. Enquanto nenhum laboratório piloto estiver
                definido, exibe CTA para cadastrar. Quando houver, exibe o piloto
                selecionado com badge "Piloto MVP 1".
                TODO: trocar heurística quando houver campo settings.is_pilot real.
            -->
            <section
                v-if="needsPilotLabSetup"
                class="relative overflow-hidden rounded-xl border-2 border-dashed border-amber-300 bg-amber-50/60 px-6 py-5"
                data-testid="pilot-lab-setup-banner"
            >
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <FlaskRound class="size-5" stroke-width="2" />
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-amber-900">Defina o laboratório piloto</h2>
                                <Badge
                                    variant="outline"
                                    class="border-amber-300 bg-amber-100 text-[10px] font-bold tracking-widest text-amber-800 uppercase"
                                >
                                    MVP 1 · Pendente
                                </Badge>
                            </div>
                            <p class="max-w-2xl text-sm leading-relaxed text-amber-900/80">
                                Para validar o fluxo de integração fim-a-fim, conecte o primeiro laboratório parceiro. Após o cadastro ele poderá ser
                                marcado como piloto do MVP 1 e usado para os testes reais.
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <Button as-child size="lg" class="gap-2 bg-amber-700 text-amber-50 shadow-sm hover:bg-amber-800">
                            <Link :href="integrationRoutes.connect()">
                                Cadastrar laboratório piloto
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <section v-else class="rounded-xl border border-primary/20 bg-primary/5 px-6 py-4" data-testid="pilot-lab-configured-banner">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <FlaskRound class="size-5" stroke-width="2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <!-- pilotLab é garantidamente não-null neste branch v-else -->
                                <p class="font-semibold text-foreground">{{ pilotLab!.name }}</p>
                                <Badge class="bg-primary px-2 py-0.5 text-[10px] font-bold tracking-widest text-primary-foreground uppercase">
                                    Piloto MVP 1
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground">Laboratório piloto ativo — validação do fluxo de interoperabilidade.</p>
                        </div>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="`/doctor/integrations/${pilotLab!.id}`">Ver detalhes</Link>
                    </Button>
                </div>
            </section>

            <!-- 2. Cards de resumo operacional -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Integrações ativas -->
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-3 px-5 py-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold tracking-widest text-muted-foreground uppercase">Integrações ativas</span>
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Settings2 class="size-4" stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.activeIntegrations }}</p>
                        <p class="text-xs text-muted-foreground">Número de parceiros conectados no momento</p>
                    </CardContent>
                </Card>

                <!-- Exames sincronizados -->
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-3 px-5 py-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold tracking-widest text-muted-foreground uppercase">Exames sincronizados</span>
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileBarChart class="size-4" stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.syncedExams }}</p>
                        <p class="text-xs text-muted-foreground">Total de exames recebidos via integração</p>
                    </CardContent>
                </Card>

                <!-- Última sincronização -->
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-3 px-5 py-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold tracking-widest text-muted-foreground uppercase">Última sincronização</span>
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <RefreshCw class="size-4" stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ formatLastSync }}</p>
                        <p class="text-xs text-muted-foreground">Horário da última atualização de dados</p>
                    </CardContent>
                </Card>

                <!-- Erros (24h) -->
                <Card class="gap-0 py-0 shadow-sm">
                    <CardContent class="space-y-3 px-5 py-5">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold tracking-widest uppercase"
                                :class="stats.errors24h > 0 ? 'text-red-600' : 'text-muted-foreground'"
                                >Erros (24h)</span
                            >
                            <div
                                class="flex size-8 items-center justify-center rounded-lg"
                                :class="stats.errors24h > 0 ? 'bg-red-500/10 text-red-600' : 'bg-primary/10 text-primary'"
                            >
                                <AlertCircle class="size-4" stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.errors24h }}</p>
                        <p class="text-xs text-muted-foreground">Falhas registradas nas últimas 24 horas</p>
                    </CardContent>
                </Card>
            </div>

            <!-- 2b. Integridade do sistema + Próxima manutenção -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Integridade do sistema -->
                <Card class="gap-0 py-0 shadow-sm lg:col-span-2">
                    <CardContent class="space-y-4 px-6 py-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-full"
                                :class="stats.errors24h === 0 ? 'bg-green-500/10 text-green-600' : 'bg-amber-500/10 text-amber-600'"
                            >
                                <CheckCircle2 class="size-5" stroke-width="2" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-foreground">Integridade do Sistema</h3>
                                <div class="flex items-center gap-1.5">
                                    <span class="size-2 rounded-full" :class="stats.errors24h === 0 ? 'bg-green-500' : 'bg-amber-500'" />
                                    <span
                                        class="text-xs font-semibold tracking-wide uppercase"
                                        :class="stats.errors24h === 0 ? 'text-green-700' : 'text-amber-700'"
                                    >
                                        {{ stats.errors24h === 0 ? 'Operacional' : 'Atenção necessária' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            <template v-if="stats.errors24h === 0">
                                Todos os nós clínicos estão respondendo normalmente. Nenhuma interrupção de serviço detectada nas últimas 24 horas.
                            </template>
                            <template v-else>
                                {{ stats.errors24h }} falha(s) detectada(s) nas últimas 24 horas. Verifique os logs de integração para mais detalhes.
                            </template>
                        </p>
                        <div class="flex gap-3 pt-1">
                            <Button variant="default" size="sm" as-child>
                                <Link :href="integrationRoutes.partners()">
                                    <Wrench class="mr-2 size-4" />
                                    Gerenciar Parceiros
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="docsUrl">
                                    <BookOpenText class="mr-2 size-4" />
                                    Documentação
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Próxima manutenção -->
                <Card class="gap-0 overflow-hidden border-0 py-0 shadow-sm" style="background-color: #c4e4e4">
                    <CardContent class="relative flex h-full flex-col justify-between px-6 py-6">
                        <!-- Ícone decorativo de fundo -->
                        <div class="absolute -right-4 -bottom-4 text-primary/5">
                            <CalendarClock class="size-32" stroke-width="1" />
                        </div>
                        <div class="relative space-y-2">
                            <h3 class="text-lg font-bold text-primary italic">Próxima Sincronização</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">Próxima execução automática de pull de resultados de exames.</p>
                        </div>
                        <p class="relative mt-6 text-base font-bold tracking-wide text-primary uppercase">
                            {{ formatNextSync ?? '—' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- 3. Laboratórios -->
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-primary">Laboratórios</h2>

                <!-- Laboratórios conectados -->
                <div v-if="hasActiveLabs" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="lab in laboratories.filter((l) => l.status === 'active')" :key="lab.id" class="gap-0 py-0 shadow-sm">
                        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pt-5 pb-4">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FlaskConical class="size-4.5" stroke-width="2" />
                            </div>
                            <Badge
                                variant="outline"
                                class="border-green-200 bg-green-50 text-[10px] font-semibold tracking-wide text-green-800 uppercase"
                            >
                                Ativo
                            </Badge>
                        </CardHeader>
                        <CardContent class="space-y-2 px-5 pt-4 pb-2">
                            <h3 class="text-lg font-semibold text-foreground">{{ lab.name }}</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">Integração ativa via protocolo FHIR R4.</p>
                        </CardContent>
                        <CardFooter class="flex flex-row items-center justify-between border-t border-border/60 px-5 py-4">
                            <span class="text-xs text-muted-foreground">
                                {{
                                    lab.last_sync_at
                                        ? `Última sync: ${new Date(lab.last_sync_at).toLocaleString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
                                        : 'Sem sincronização'
                                }}
                            </span>
                            <Button variant="link" class="h-auto p-0 text-primary" as-child>
                                <Link :href="`/doctor/integrations/${lab.id}`">Gerenciar</Link>
                            </Button>
                        </CardFooter>
                        <CardFooter class="border-t border-border/60 px-5 pt-0 pb-4">
                            <Button variant="ghost" size="sm" class="h-auto p-0 text-xs text-muted-foreground hover:text-primary" as-child>
                                <Link :href="docsUrl">
                                    <BookOpenText class="mr-1.5 size-3.5" />
                                    Ver docs de integração
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Estado vazio -->
                <Card v-else class="border-0 bg-muted/40 py-0 shadow-none">
                    <CardContent class="flex flex-col items-center justify-center px-6 py-14 text-center">
                        <div class="mb-5 flex size-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                            <FlaskConical class="size-7 opacity-70" stroke-width="1.75" />
                        </div>
                        <h3 class="text-lg font-semibold text-foreground">Nenhum laboratório conectado</h3>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
                            Sua clínica ainda não possui laboratórios parceiros ativos. Conecte-se agora para automatizar resultados.
                        </p>
                        <Button class="mt-6" variant="secondary" as-child>
                            <Link :href="integrationRoutes.connect()">Conectar laboratório</Link>
                        </Button>
                        <Button class="mt-3" variant="link" as-child>
                            <Link :href="docsUrl">
                                <BookOpenText class="mr-2 size-4" />
                                Ler documentação
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </section>

            <!-- 4. Planos de saúde — em breve -->
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-primary">Planos de saúde</h2>
                <ComingSoonOverlay>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Card class="gap-0 py-0 shadow-sm">
                            <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pt-5 pb-4">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Shield class="size-4.5" stroke-width="2" />
                                </div>
                                <Badge
                                    variant="outline"
                                    class="border-red-200 bg-red-50 text-[10px] font-semibold tracking-wide text-red-800 uppercase"
                                >
                                    Indisponível
                                </Badge>
                            </CardHeader>
                            <CardContent class="space-y-2 px-5 pt-4 pb-2">
                                <h3 class="text-lg font-semibold text-foreground">MedCore Premium</h3>
                                <p class="text-sm leading-relaxed text-muted-foreground">
                                    Erro na sincronização com o servidor do convênio. Verifique as credenciais.
                                </p>
                            </CardContent>
                            <CardFooter class="flex flex-row items-center justify-between border-t border-border/60 px-5 py-4">
                                <span class="text-xs font-medium text-red-600">Última sincronização: há 14h</span>
                                <Button variant="link" class="h-auto p-0 text-primary">Gerenciar</Button>
                            </CardFooter>
                        </Card>

                        <Card class="gap-0 py-0 shadow-sm">
                            <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pt-5 pb-4">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                    <Plus class="size-4.5" stroke-width="2" />
                                </div>
                                <Badge
                                    variant="outline"
                                    class="border-border bg-muted/80 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Desativado
                                </Badge>
                            </CardHeader>
                            <CardContent class="space-y-2 px-5 pt-4 pb-2">
                                <h3 class="text-lg font-semibold text-foreground">Global Health Plus</h3>
                                <p class="text-sm leading-relaxed text-muted-foreground">
                                    Integração pausada manualmente. Nenhum dado será trocado até reativar.
                                </p>
                            </CardContent>
                            <CardFooter class="flex flex-row items-center justify-between border-t border-border/60 px-5 py-4">
                                <span class="text-xs text-muted-foreground">Sem atividade recente</span>
                                <Button variant="link" class="h-auto p-0 text-primary">Reativar</Button>
                            </CardFooter>
                        </Card>

                        <div
                            class="flex min-h-[220px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/25 bg-muted/20 px-6 py-10 text-center"
                        >
                            <div class="mb-3 flex size-11 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                <Plus class="size-5" stroke-width="2" />
                            </div>
                            <span class="text-base font-semibold text-muted-foreground">Nova conexão de plano</span>
                        </div>
                    </div>
                </ComingSoonOverlay>
            </section>

            <!-- 5. CTA -->
            <section class="flex flex-col gap-8 rounded-xl bg-muted/70 px-8 py-10 md:flex-row md:items-center md:justify-between md:gap-10">
                <div class="max-w-2xl space-y-2 text-left">
                    <h2 class="text-xl font-bold text-primary md:text-2xl">Precisa de um parceiro específico?</h2>
                    <p class="text-base leading-relaxed text-muted-foreground">
                        Nossa equipe pode desenvolver integrações customizadas para laboratórios locais ou sistemas legados da sua região.
                    </p>
                </div>
                <div class="shrink-0 md:pl-4">
                    <Button class="w-full shadow-md md:w-auto" size="lg" as-child>
                        <Link :href="integrationRoutes.connect()">Solicitar Integração</Link>
                    </Button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
