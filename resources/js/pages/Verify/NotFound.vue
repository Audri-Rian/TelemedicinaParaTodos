<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{ verificationCode: string }>();
</script>

<template>
    <Head title="Documento não encontrado" />
    <div class="min-h-screen bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <div class="rounded-lg bg-white p-8 text-center shadow">
                <h1 class="mb-2 text-2xl font-bold text-gray-900">Documento não encontrado</h1>
                <p class="mb-6 text-sm text-gray-500">
                    Nenhum documento corresponde ao código
                    <span class="font-mono">{{ verificationCode }}</span
                    >.
                </p>
                <p class="text-sm text-gray-600">
                    Verifique se digitou o código exatamente como aparece no documento impresso. Códigos são alfanuméricos maiúsculos (ex.:
                    <span class="font-mono">A1B2C3D4E5F6</span>).
                </p>
            </div>
        </div>
    </div>
</template>
