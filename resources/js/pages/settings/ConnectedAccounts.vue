<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Link2, Link2Off } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    googleLinked: boolean;
    googleEmail?: string | null;
    hasPassword: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Contas conectadas', href: '/settings/connected-accounts' }];

const page = usePage();
const flashStatus = computed(() => (page.props.flash as Record<string, unknown>)?.status as string | undefined);
const flashError = computed(() => (page.props.flash as Record<string, unknown>)?.error as string | undefined);

const linkGoogle = () => {
    window.location.href = '/settings/connected-accounts/google';
};

const unlinkGoogle = () => {
    if (!confirm('Deseja desvincular sua conta Google?')) return;
    useForm({}).delete('/settings/connected-accounts/google');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Contas conectadas" />
        <SettingsLayout>
            <div class="space-y-5">
                <!-- Aviso: sem senha -->
                <div v-if="!hasPassword" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <strong>Atenção:</strong> Para desvincular o Google ou vincular uma nova conta, você precisa primeiro
                    <a href="/settings/password" class="font-semibold underline">criar uma senha</a> para não perder acesso.
                </div>

                <!-- Status flash -->
                <div v-if="flashStatus" class="flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 p-3 text-sm text-teal-700">
                    <CheckCircle2 class="h-4 w-4 shrink-0" />
                    {{ flashStatus }}
                </div>
                <div v-if="flashError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ flashError }}
                </div>

                <section class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <p class="mb-4 text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Contas conectadas</p>

                    <!-- Google -->
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="flex items-center gap-3">
                            <!-- Google icon -->
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200 bg-white shadow-xs">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                        fill="#4285F4"
                                    />
                                    <path
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                        fill="#34A853"
                                    />
                                    <path
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                        fill="#FBBC05"
                                    />
                                    <path
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                        fill="#EA4335"
                                    />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">Google</p>
                                <p class="text-xs text-slate-500">
                                    {{ googleLinked ? (googleEmail ?? 'Vinculado') : 'Não vinculado' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                v-if="googleLinked"
                                class="inline-flex items-center gap-1 rounded-full border border-teal-200 bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-800"
                            >
                                <Link2 class="h-3 w-3" />
                                Vinculado
                            </span>

                            <Button v-if="!googleLinked" size="sm" variant="outline" :disabled="!hasPassword" @click="linkGoogle">
                                <Link2 class="mr-1.5 h-3.5 w-3.5" />
                                Vincular
                            </Button>

                            <Button
                                v-else
                                size="sm"
                                variant="outline"
                                :disabled="!hasPassword"
                                @click="unlinkGoogle"
                                class="text-red-600 hover:text-red-700"
                            >
                                <Link2Off class="mr-1.5 h-3.5 w-3.5" />
                                Desvincular
                            </Button>
                        </div>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
