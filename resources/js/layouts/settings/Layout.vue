<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { edit as editPassword } from '@/routes/password';
import { edit } from '@/routes/profile';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bug, Lock, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    fullWidth?: boolean;
    hideHeading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    fullWidth: false,
    hideHeading: false,
});

const sidebarNavItems: NavItem[] = [
    {
        title: 'Perfil',
        href: edit(),
        icon: User,
    },
    {
        title: 'Senha',
        href: editPassword(),
        icon: Lock,
    },
    {
        title: 'Notificar Bug',
        href: '/settings/bug-report',
        icon: Bug,
    },
];

const page = usePage();
const currentPath = computed(() => (typeof page.url === 'string' ? page.url : '').replace(/\?.*$/, ''));
const auth = computed(() => page.props.auth as { isDoctor?: boolean; role?: string | null } | undefined);
const isDoctorSettings = computed(() => auth.value?.isDoctor === true || auth.value?.role === 'doctor');
const isProfilePage = computed(() => currentPath.value === '/settings/profile');

const profileSubnavItems = [
    { title: 'Informações básicas', href: '#basic' },
    { title: 'Sobre você', href: '#about' },
    { title: 'Especialidades', href: '#specialties' },
    { title: 'Dados profissionais', href: '#professional' },
    { title: 'Idiomas', href: '#languages' },
    { title: 'Modalidades e valores', href: '#modality' },
    { title: 'Locais de atendimento', href: '#locations' },
    { title: 'Recebimentos', href: '#payouts' },
    { title: 'Notificações', href: '#notifications' },
    { title: 'Formação e certificações', href: '#timeline' },
    { title: 'Zona de perigo', href: '#danger', danger: true },
];

function isActive(href: string | { url?: string }) {
    const path = typeof href === 'string' ? href : (href?.url ?? '');
    return currentPath.value === path || (path && currentPath.value.startsWith(path));
}
</script>

<template>
    <div v-if="isDoctorSettings" class="min-h-[60vh] bg-[#fafbfc] px-4 py-7 text-slate-950 md:px-6">
        <div class="mx-auto w-full max-w-[1240px]">
            <header class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-[28px] font-semibold tracking-normal text-slate-950">Configurações</h1>
                    <p class="mt-1 text-[14.5px] text-slate-500">Gerencie seu perfil e configurações da conta.</p>
                </div>
            </header>

            <div class="grid items-start gap-7 lg:grid-cols-[240px_minmax(0,1fr)]">
                <aside class="lg:sticky lg:top-20">
                    <nav class="rounded-[14px] border border-slate-200 bg-white p-3 shadow-xs">
                        <p class="px-2.5 pt-2 pb-1 text-[10.5px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Conta</p>
                        <Link
                            v-for="item in sidebarNavItems"
                            :key="typeof item.href === 'string' ? item.href : item.href?.url"
                            :href="typeof item.href === 'string' ? item.href : item.href?.url"
                            :class="[
                                'flex w-full items-center gap-2.5 rounded-[9px] px-2.5 py-2.5 text-left text-[13.5px] font-medium transition-colors',
                                isActive(typeof item.href === 'string' ? item.href : (item.href?.url ?? ''))
                                    ? 'bg-teal-50 text-teal-900 shadow-[inset_2.5px_0_0_#0f766e]'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-950',
                            ]"
                        >
                            <component :is="item.icon" class="size-4 opacity-85" />
                            <span>{{ item.title }}</span>
                            <span
                                v-if="item.title === 'Perfil' && !isProfilePage"
                                class="ml-auto inline-flex h-[18px] items-center rounded-full border border-teal-200 bg-teal-50 px-1.5 text-[11px] font-medium text-teal-900"
                            >
                                Principal
                            </span>
                        </Link>

                        <template v-if="isProfilePage">
                            <p class="mt-2 px-2.5 pt-2 pb-1 text-[10.5px] font-semibold tracking-[0.08em] text-slate-500 uppercase">
                                Seções do perfil
                            </p>
                            <a
                                v-for="item in profileSubnavItems"
                                :key="item.href"
                                :href="item.href"
                                :class="[
                                    'flex w-full items-center rounded-[9px] px-5 py-2 text-left text-[12.5px] font-medium transition-colors hover:bg-slate-100',
                                    item.danger ? 'text-rose-700 hover:text-rose-800' : 'text-slate-500 hover:text-slate-950',
                                ]"
                            >
                                {{ item.title }}
                            </a>
                        </template>
                    </nav>
                </aside>

                <main class="min-w-0">
                    <section class="w-full space-y-5">
                        <slot />
                    </section>
                </main>
            </div>
        </div>
    </div>

    <div v-else class="min-h-[60vh] bg-gray-50/80 py-6 pr-4 pl-4 md:pr-6 md:pl-6">
        <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
            <!-- Barra lateral: fundo cinza claro, itens com destaque branco e indicador primary no ativo -->
            <aside class="w-full shrink-0 lg:w-52">
                <nav class="flex flex-col gap-1">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="typeof item.href === 'string' ? item.href : item.href?.url"
                        :href="typeof item.href === 'string' ? item.href : item.href?.url"
                        :class="[
                            'relative rounded-xl px-4 py-3 text-left text-sm font-medium transition-all',
                            isActive(typeof item.href === 'string' ? item.href : (item.href?.url ?? ''))
                                ? 'border-l-4 border-primary bg-white text-gray-800 shadow-sm'
                                : 'text-gray-500 hover:bg-white/60 hover:text-gray-700',
                        ]"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <!-- Conteúdo principal -->
            <main :class="[props.fullWidth ? 'min-w-0 flex-1' : 'min-w-0 flex-1 md:max-w-2xl']">
                <Heading v-if="!props.hideHeading" title="Configurações" description="Gerencie seu perfil e configurações da conta" />
                <section :class="[props.hideHeading ? 'mt-0' : 'mt-0', props.fullWidth ? 'w-full space-y-6' : 'max-w-xl space-y-6']">
                    <slot />
                </section>
            </main>
        </div>
    </div>
</template>
