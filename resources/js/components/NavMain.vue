<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage, type InertiaLinkProps } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { reactive, watch } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();

function hrefToString(href: NonNullable<InertiaLinkProps['href']>): string {
    return typeof href === 'string' ? href : href.url;
}

const isCurrentRoute = (href: NonNullable<InertiaLinkProps['href']>) => {
    const urlString = hrefToString(href);
    return page.url === urlString;
};

function sectionPathPrefix(item: NavItem): string {
    return item.activePathPrefix ?? hrefToString(item.href);
}

/** Estado de expansão dos itens com filhos (estilo workspace). */
const nestedOpen = reactive<Record<string, boolean>>({});

watch(
    () => [page.url, props.items] as const,
    ([url]) => {
        for (const item of props.items) {
            if (!item.children?.length) {
                continue;
            }
            const prefix = sectionPathPrefix(item);
            if (url === prefix || url.startsWith(`${prefix}/`)) {
                nestedOpen[item.title] = true;
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <SidebarMenuItem v-if="!item.children?.length">
                    <SidebarMenuButton as-child :is-active="isCurrentRoute(item.href)" :tooltip="item.title">
                        <Link :href="item.href">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem v-else class="group/nested">
                    <Collapsible
                        class="w-full min-w-0"
                        :open="nestedOpen[item.title] ?? false"
                        @update:open="(v: boolean) => (nestedOpen[item.title] = v)"
                    >
                        <div class="flex w-full min-w-0 items-stretch gap-0 rounded-md">
                            <SidebarMenuButton
                                as-child
                                class="min-w-0 flex-1 pr-1"
                                :is-active="false"
                                :tooltip="item.title"
                            >
                                <Link :href="item.href" class="flex min-w-0 flex-1 items-center gap-2">
                                    <component :is="item.icon" v-if="item.icon" class="size-4 shrink-0 opacity-90" />
                                    <span class="truncate text-left font-medium">{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <CollapsibleTrigger as-child>
                                <button
                                    type="button"
                                    class="text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground inline-flex size-8 shrink-0 items-center justify-center rounded-md outline-hidden transition-colors focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                    :aria-expanded="nestedOpen[item.title] ?? false"
                                    :aria-label="`Recolher ou expandir ${item.title}`"
                                >
                                    <ChevronRight
                                        class="size-4 transition-transform duration-200"
                                        :class="(nestedOpen[item.title] ?? false) && 'rotate-90'"
                                    />
                                </button>
                            </CollapsibleTrigger>
                        </div>
                        <CollapsibleContent>
                            <SidebarMenuSub
                                class="border-sidebar-border/60 mt-0.5 ml-3 gap-0.5 border-l border-dashed py-1 pl-5"
                            >
                                <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                    <SidebarMenuSubButton
                                        as-child
                                        size="sm"
                                        class="text-muted-foreground hover:text-sidebar-foreground h-8 px-2 text-[13px] font-normal"
                                        :is-active="isCurrentRoute(child.href)"
                                    >
                                        <Link :href="child.href" class="flex w-full min-w-0 items-center gap-2.5">
                                            <component
                                                :is="child.icon"
                                                v-if="child.icon"
                                                class="size-3.5 shrink-0 opacity-60"
                                            />
                                            <span class="truncate">{{ child.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
