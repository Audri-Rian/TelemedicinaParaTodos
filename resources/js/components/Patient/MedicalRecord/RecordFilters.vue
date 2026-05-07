<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Filter, Search } from 'lucide-vue-next';

defineProps<{
    search: string;
    dateFrom: string;
    dateTo: string;
    hasFilters: boolean;
}>();

const emit = defineEmits<{
    'update:search': [value: string];
    'update:dateFrom': [value: string];
    'update:dateTo': [value: string];
    apply: [];
    clear: [];
}>();
</script>

<template>
    <section class="rounded-lg border border-[#dde5ea] bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
            <label class="flex flex-1 flex-col gap-2">
                <span class="text-sm font-black text-gray-700">Busca</span>
                <div class="relative">
                    <Search class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        :value="search"
                        class="h-11 rounded-lg border-[#dde5ea] bg-[#f7f8f9] pl-11 font-semibold focus:border-[#0f6e78] focus:ring-[#0f6e78]/20"
                        placeholder="Diagnóstico, médico, sintoma, documento..."
                        @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                    />
                </div>
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-sm font-black text-gray-700">De</span>
                <Input
                    :value="dateFrom"
                    type="date"
                    class="h-11 rounded-lg border-[#dde5ea] font-semibold"
                    @input="emit('update:dateFrom', ($event.target as HTMLInputElement).value)"
                />
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-sm font-black text-gray-700">Até</span>
                <Input
                    :value="dateTo"
                    type="date"
                    class="h-11 rounded-lg border-[#dde5ea] font-semibold"
                    @input="emit('update:dateTo', ($event.target as HTMLInputElement).value)"
                />
            </label>

            <div class="flex gap-2">
                <Button class="h-11 bg-[#0f6e78] font-black text-white hover:bg-[#0a4f57]" @click="emit('apply')">
                    <Filter class="mr-2 h-4 w-4" />
                    Aplicar
                </Button>
                <Button variant="outline" class="h-11 border-[#dde5ea] font-extrabold" :disabled="!hasFilters" @click="emit('clear')">
                    Limpar
                </Button>
            </div>
        </div>
    </section>
</template>
