<script setup lang="ts">
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    items: NavItem[];
    class?: string;
}

const props = defineProps<Props>();

const isExternalLink = (href: NavItem['href']): boolean => {
    if (typeof href === 'string') {
        return href.startsWith('http://') || href.startsWith('https://');
    }
    return false;
};
</script>

<template>
    <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton class="text-neutral-600 hover:text-neutral-800" as-child>
                        <a
                            v-if="isExternalLink(item.href)"
                            :href="typeof item.href === 'string' ? item.href : item.href.url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </a>
                        <Link
                            v-else
                            :href="typeof item.href === 'string' ? item.href : item.href"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
