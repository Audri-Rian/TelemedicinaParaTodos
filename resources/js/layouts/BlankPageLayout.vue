<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { Info, Search } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const navItems = [
    { label: 'Docs', href: '/docs/interoperabilidade', active: true },
    { label: 'SDKs', href: '#' },
    { label: 'Community', href: '#' },
    { label: 'Support', href: '#' },
];

const sectionItems = [{ id: 'introduction', label: 'Introduction', icon: Info }] as const;

const activeSection = ref('fhir-resources');

const updateActiveSectionFromHash = () => {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        activeSection.value = hash;
    }
};

const onSelectSection = (sectionId: string) => {
    activeSection.value = sectionId;
};

onMounted(() => {
    updateActiveSectionFromHash();
    window.addEventListener('hashchange', updateActiveSectionFromHash);
});

onBeforeUnmount(() => {
    window.removeEventListener('hashchange', updateActiveSectionFromHash);
});
</script>

<template>
    <div class="min-h-screen w-full bg-white text-foreground">
        <header class="sticky top-0 z-40 w-full border-b border-border/70 bg-muted/20 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-[1440px] items-center justify-between px-3">
                <div class="flex items-center">
                    <Link href="/" class="flex items-center gap-3">
                        <div class="flex size-11 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <AppLogoIcon class="size-8" />
                        </div>
                        <span class="text-lg font-semibold text-foreground">Telemedicina Para Todos API</span>
                    </Link>
                </div>

                <nav class="absolute left-1/2 flex -translate-x-1/2 items-center gap-6">
                    <a
                        v-for="item in navItems"
                        :key="item.label"
                        :href="item.href"
                        class="relative pb-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        :class="{ 'text-primary': item.active }"
                    >
                        {{ item.label }}
                        <span v-if="item.active" class="absolute right-0 bottom-0 left-0 h-0.5 rounded-full bg-primary" />
                    </a>
                </nav>

                <div class="flex items-center gap-3">
                    <label class="relative hidden md:block">
                        <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            type="text"
                            placeholder="Search documentation..."
                            class="h-10 w-72 rounded-md border border-border bg-background pr-3 pl-9 text-sm transition-colors outline-none placeholder:text-muted-foreground/80 focus:border-primary/50"
                        />
                    </label>

                    <Link
                        href="/dashboard"
                        class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </header>

        <main class="min-h-[calc(100vh-4rem)] w-full">
            <div class="flex min-h-[calc(100vh-4rem)]">
                <aside class="hidden w-72 border-r border-border/70 bg-muted/30 lg:block">
                    <div class="sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto px-5 py-6">
                        <p class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase">Current Workspace</p>
                        <h2 class="mt-1 text-xl font-semibold text-foreground">Documentation</h2>
                        <p class="text-xs text-muted-foreground">v4.0.1 [R4]</p>

                        <nav class="mt-6 space-y-1.5">
                            <template v-for="item in sectionItems" :key="item.id">
                                <a
                                    :href="`#${item.id}`"
                                    class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
                                    :class="{
                                        'bg-background text-primary shadow-sm': activeSection === item.id,
                                    }"
                                    @click="onSelectSection(item.id)"
                                >
                                    <component :is="item.icon" class="size-4" />
                                    <span>{{ item.label }}</span>
                                </a>

                                <div v-if="'children' in item" class="ml-6 border-l border-border pl-4">
                                    <a
                                        v-for="child in item.children"
                                        :key="child.id"
                                        :href="`#${child.id}`"
                                        class="block rounded-md px-2 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
                                        :class="{
                                            'text-primary': activeSection === child.id,
                                        }"
                                        @click="onSelectSection(child.id)"
                                    >
                                        {{ child.label }}
                                    </a>
                                </div>
                            </template>
                        </nav>
                    </div>
                </aside>

                <div class="min-w-0 flex-1">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
