<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { edit as editPassword } from '@/routes/password';
import { edit } from '@/routes/profile';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

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

const currentPath = typeof window !== undefined ? window.location.pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading v-if="!props.hideHeading" title="Configurações" description="Gerencie seu perfil e configurações da conta" />

        <div class="flex flex-col lg:flex-row lg:space-x-12" :class="props.hideHeading ? 'mt-0' : ''">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="typeof item.href === 'string' ? item.href : item.href?.url"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': currentPath === (typeof item.href === 'string' ? item.href : item.href?.url) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div :class="props.fullWidth ? 'flex-1 w-full' : 'flex-1 md:max-w-2xl'">
                <section :class="props.fullWidth ? 'w-full space-y-6' : 'max-w-xl space-y-12'">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
