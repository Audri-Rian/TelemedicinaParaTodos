<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Laboratórios', href: doctorRoutes.laboratorios().url },
    { title: 'Hub de Integrações', href: doctorRoutes.laboratorios().url },
];
</script>

<template>
    <Head title="Hub de Integrações · Laboratórios" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="text-2xl font-semibold tracking-tight">Hub de Integrações</h1>
            <p class="mt-2 text-muted-foreground">Visão geral das integrações com laboratórios parceiros.</p>
        </div>
    </AppLayout>
</template>
