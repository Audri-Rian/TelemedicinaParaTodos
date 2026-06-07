<script setup lang="ts">
import MessagesWorkspace from '@/components/MessagesWorkspace.vue';
import { useRouteGuard } from '@/composables/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';

interface Conversation {
    id: string;
    name: string;
    avatar: string | null;
    lastMessage: string;
    lastMessageTime: string | null;
    unread: number;
}

const page = usePage();
const { canAccessPatientRoute } = useRouteGuard();

const conversations = ((page.props.conversations as Conversation[]) || []).map((conversation) => ({
    ...conversation,
    avatar: conversation.avatar || null,
}));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Mensagens',
        href: patientRoutes.messages().url,
    },
];

onMounted(() => {
    canAccessPatientRoute();
});
</script>

<template>
    <Head title="Mensagens" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <MessagesWorkspace
            :conversations="conversations"
            perspective="patient"
            title="Mensagens"
            subtitle="Converse com médicos vinculados às suas consultas de forma segura e organizada."
            empty-description="Você precisa ter consultas com médicos para iniciar conversas."
            :primary-href="patientRoutes.searchConsultations().url"
            primary-label="Buscar consulta"
            contact-label="Médico"
            contact-meta="Disponível"
            :profile-href="(conversation) => patientRoutes.doctorPerfil({ query: { doctor_id: conversation.id } }).url"
        />
    </AppLayout>
</template>
