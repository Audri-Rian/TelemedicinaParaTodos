<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import * as doctorRoutes from '@/routes/doctor';
import * as integrationRoutes from '@/routes/doctor/integrations';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { FlaskConical, Plus, Shield } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Integrações', href: doctorRoutes.integrations().url },
    { title: 'Hub de Integrações', href: doctorRoutes.integrations().url },
];
</script>

<template>
    <Head title="Hub de Integrações" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-6xl space-y-10 p-6 pb-16">
            <!-- 1. Cabeçalho da página -->
            <header class="space-y-2">
                <h1 class="text-4xl font-bold tracking-tight text-foreground">Hub de Integrações</h1>
                <p class="max-w-2xl text-base text-muted-foreground">
                    Gerencie e monitore as conexões clínicas da sua rede.
                </p>
            </header>

            <!-- 2. Laboratórios — estado vazio -->
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-primary">Laboratórios</h2>
                <Card class="border-0 bg-muted/40 py-0 shadow-none">
                    <CardContent class="flex flex-col items-center justify-center px-6 py-14 text-center">
                        <div
                            class="mb-5 flex size-14 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                        >
                            <FlaskConical class="size-7 opacity-70" stroke-width="1.75" />
                        </div>
                        <h3 class="text-lg font-semibold text-foreground">Nenhum laboratório conectado</h3>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
                            Sua clínica ainda não possui laboratórios parceiros ativos. Conecte-se agora para
                            automatizar resultados.
                        </p>
                        <Button class="mt-6" variant="secondary" as-child>
                            <Link :href="integrationRoutes.connect()">Conectar</Link>
                        </Button>
                    </CardContent>
                </Card>
            </section>

            <!-- 3. Planos de saúde — cards de status -->
            <section class="space-y-4">
                <h2 class="text-2xl font-semibold text-primary">Planos de saúde</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Card: erro / indisponível -->
                    <Card class="gap-0 py-0 shadow-sm">
                        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 pt-5">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Shield class="size-4.5" stroke-width="2" />
                            </div>
                            <Badge
                                variant="outline"
                                class="border-red-200 bg-red-50 text-[10px] font-semibold uppercase tracking-wide text-red-800"
                            >
                                • Indisponível / com erro
                            </Badge>
                        </CardHeader>
                        <CardContent class="space-y-2 px-5 pt-4 pb-2">
                            <h3 class="text-lg font-semibold text-foreground">MedCore Premium</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                Erro na sincronização com o servidor do convênio. Verifique as credenciais ou tente
                                novamente.
                            </p>
                        </CardContent>
                        <CardFooter class="flex flex-row items-center justify-between border-t border-border/60 px-5 py-4">
                            <span class="text-xs font-medium text-red-600">Última sincronização: há 14h</span>
                            <Button variant="link" class="h-auto p-0 text-primary" as-child>
                                <Link :href="integrationRoutes.partners()">Gerenciar</Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <!-- Card: desativado -->
                    <Card class="gap-0 py-0 shadow-sm">
                        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 pt-5">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                            >
                                <Plus class="size-4.5" stroke-width="2" />
                            </div>
                            <Badge
                                variant="outline"
                                class="border-border bg-muted/80 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground"
                            >
                                • Desativado
                            </Badge>
                        </CardHeader>
                        <CardContent class="space-y-2 px-5 pt-4 pb-2">
                            <h3 class="text-lg font-semibold text-foreground">Global Health Plus</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                Integração pausada manualmente. Nenhum dado será trocado até você reativar.
                            </p>
                        </CardContent>
                        <CardFooter class="flex flex-row items-center justify-between border-t border-border/60 px-5 py-4">
                            <span class="text-xs text-muted-foreground">Sem atividade recente</span>
                            <Button variant="link" class="h-auto p-0 text-primary" as-child>
                                <Link :href="integrationRoutes.partners()">Reativar</Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <!-- Card: nova conexão -->
                    <Link
                        :href="integrationRoutes.connect()"
                        class="flex min-h-[220px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/25 bg-muted/20 px-6 py-10 text-center transition-colors hover:border-primary/40 hover:bg-muted/35"
                    >
                        <div
                            class="mb-3 flex size-11 items-center justify-center rounded-full bg-muted text-muted-foreground"
                        >
                            <Plus class="size-5" stroke-width="2" />
                        </div>
                        <span class="text-base font-semibold text-muted-foreground">Nova conexão de plano</span>
                    </Link>
                </div>
            </section>

            <!-- 4. CTA — integração customizada -->
            <section
                class="flex flex-col gap-8 rounded-xl bg-muted/70 px-8 py-10 md:flex-row md:items-center md:justify-between md:gap-10"
            >
                <div class="max-w-2xl space-y-2 text-left">
                    <h2 class="text-xl font-bold text-primary md:text-2xl">Precisa de um parceiro específico?</h2>
                    <p class="text-base leading-relaxed text-muted-foreground">
                        Nossa equipe de engenharia pode desenvolver integrações customizadas para laboratórios locais ou
                        sistemas legados da sua região.
                    </p>
                </div>
                <div class="shrink-0 md:pl-4">
                    <Button
                        class="w-full shadow-md md:w-auto"
                        size="lg"
                        as-child
                    >
                        <Link :href="integrationRoutes.connect()">Solicitar Nova Integração</Link>
                    </Button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
