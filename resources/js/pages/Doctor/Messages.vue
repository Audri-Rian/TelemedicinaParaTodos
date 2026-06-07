<script setup lang="ts">
import MessagesWorkspace from '@/components/MessagesWorkspace.vue';
import { useRouteGuard } from '@/composables/auth/useRouteGuard';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
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

const { canAccessDoctorRoute } = useRouteGuard();

onMounted(() => {
    canAccessDoctorRoute();
});

const page = usePage();

const conversations = ((page.props.conversations as Conversation[]) || []).map((conversation) => ({
    ...conversation,
    avatar: conversation.avatar || null,
}));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Mensagens',
        href: doctorRoutes.messages().url,
    },
];
</script>

<template>
    <Head title="Mensagens" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <MessagesWorkspace
            :conversations="conversations"
            perspective="doctor"
            title="Mensagens"
            subtitle="Acompanhe conversas clínicas com seus pacientes e mantenha o cuidado assíncrono em dia."
            empty-description="Você precisa ter consultas com pacientes para iniciar conversas."
            :primary-href="doctorRoutes.patients().url"
            primary-label="Ver pacientes"
            contact-label="Paciente"
            contact-meta="Acompanhamento ativo"
            :profile-href="(conversation) => doctorRoutes.patients({ query: { search: conversation.name } }).url"
        />
    </AppLayout>
</template>
