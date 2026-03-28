<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import * as doctorLabRoutes from '@/routes/doctor/laboratorios';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Laboratórios', href: doctorRoutes.laboratorios().url },
    { title: 'Conectar Parceiro', href: doctorLabRoutes.conectar().url },
];
</script>

<template>
    <Head title="Conectar Parceiro · Laboratórios" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="text-2xl font-semibold tracking-tight">Conectar Parceiro</h1>
            <p class="mt-2 text-muted-foreground">Adicione um novo laboratório com credenciais e permissões.</p>
        </div>
    </AppLayout>
</template>
