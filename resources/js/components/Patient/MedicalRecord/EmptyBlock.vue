<script setup lang="ts">
defineProps<{ text: string }>();
</script>

<template>
    <div class="rounded-lg border border-dashed border-[#c8d2d7] bg-[#f7f8f9] px-6 py-12 text-center">
        <p class="text-sm font-bold text-gray-500">{{ text }}</p>
    </div>
</template>
