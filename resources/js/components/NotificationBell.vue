<template>
    <div class="relative">
        <button
            @click="toggleDropdown"
            class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full transition-colors"
            :class="{ 'text-blue-600': hasUnread }"
        >
            <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>
            <span
                v-if="hasUnread"
                class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"
            ></span>
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <NotificationDropdown
            v-if="showDropdown"
            @close="showDropdown = false"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import NotificationDropdown from './NotificationDropdown.vue';

const { unreadCount, hasUnread } = useNotifications();
const showDropdown = ref(false);

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
};

onMounted(() => {
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.relative')) {
            showDropdown.value = false;
        }
    });
});
</script>


