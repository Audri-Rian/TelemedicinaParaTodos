<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getInitials } from '@/composables/useInitials';

interface BreadcrumbItemType {
    title: string;
    href?: string;
}

const props = defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user);

const showAvatar = computed(() => user.value?.avatar && user.value.avatar !== '');
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <!-- Avatar do usuário no início do breadcrumb -->
            <BreadcrumbItem v-if="user">
                <div class="flex items-center gap-2">
                    <Avatar class="h-6 w-6 overflow-hidden rounded-full">
                        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                        <AvatarFallback class="rounded-full bg-neutral-200 text-xs font-semibold text-black" :delay-ms="600">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="text-sm font-medium text-neutral-700">{{ user.name }}</span>
                </div>
            </BreadcrumbItem>
            <BreadcrumbSeparator v-if="user && breadcrumbs.length > 0" />
            
            <!-- Itens do breadcrumb -->
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link :href="item.href ?? '#'">{{ item.title }}</Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
