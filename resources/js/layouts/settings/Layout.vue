<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { edit as editPassword } from '@/routes/password';
import { edit } from '@/routes/profile';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
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
    },
    {
        title: 'Senha',
        href: editPassword(),
    },
    {
        title: 'Notificar Bug',
        href: '/settings/bug-report',
    },
];

const page = usePage();
const currentPath = computed(() => (typeof page.url === 'string' ? page.url : '').replace(/\?.*$/, ''));

function isActive(href: string | { url?: string }) {
    const path = typeof href === 'string' ? href : href?.url ?? '';
    return currentPath.value === path || (path && currentPath.value.startsWith(path));
}
</script>

<template>
    <div class="min-h-[60vh] bg-gray-50/80 py-6 pl-4 pr-4 md:pl-6 md:pr-6">
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
                            isActive(typeof item.href === 'string' ? item.href : item.href?.url ?? '')
                                ? 'bg-white text-gray-800 shadow-sm border-l-4 border-primary'
                                : 'text-gray-500 hover:bg-white/60 hover:text-gray-700',
                        ]"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <!-- Conteúdo principal -->
            <main :class="[props.fullWidth ? 'min-w-0 flex-1' : 'min-w-0 flex-1 md:max-w-2xl']">
                <Heading
                    v-if="!props.hideHeading"
                    title="Configurações"
                    description="Gerencie seu perfil e configurações da conta"
                />
                <section :class="[props.hideHeading ? 'mt-0' : 'mt-0', props.fullWidth ? 'w-full space-y-6' : 'max-w-xl space-y-6']">
                    <slot />
                </section>
            </main>
        </div>
    </div>
</template>
