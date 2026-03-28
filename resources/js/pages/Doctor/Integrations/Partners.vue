<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import * as integrationRoutes from '@/routes/doctor/integrations';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Integrações', href: doctorRoutes.integrations().url },
    { title: 'Gerenciar Parceiros', href: integrationRoutes.partners().url },
];
</script>

<template>
    <Head title="Gerenciar Parceiros · Integrações" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="text-2xl font-semibold tracking-tight">Gerenciar Parceiros</h1>
            <p class="mt-2 text-muted-foreground">Lista, status e configuração dos parceiros conectados.</p>
        </div>
    </AppLayout>
</template>
