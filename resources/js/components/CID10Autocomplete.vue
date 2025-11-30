<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Input } from '@/components/ui/input';
import { useCID10 } from '@/composables/useCID10';
import { Search, X, Check } from 'lucide-vue-next';

interface Props {
    modelValue?: string;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Digite o código CID-10 (ex: J00)',
    disabled: false,
    class: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'select': [item: { code: string; description: string }];
}>();

const { searchTerm, isOpen, filteredItems, findCID10, getDescription } = useCID10();
const inputRef = ref<HTMLInputElement | null>(null);
const dropdownRef = ref<HTMLDivElement | null>(null);
const highlightedIndex = ref(-1);

// Sincronizar com modelValue
watch(() => props.modelValue, (newValue) => {
    if (newValue && newValue !== searchTerm.value) {
        searchTerm.value = newValue;
        const item = findCID10(newValue);
        if (item) {
            isOpen.value = false;
        }
    }
});

// Atualizar modelValue quando searchTerm mudar
watch(searchTerm, (newValue) => {
    emit('update:modelValue', newValue);
    if (newValue.length >= 1) {
        isOpen.value = true;
        highlightedIndex.value = -1;
    } else {
        isOpen.value = false;
    }
});

// Fechar dropdown ao clicar fora
const handleClickOutside = (event: MouseEvent) => {
    if (
        dropdownRef.value &&
        !dropdownRef.value.contains(event.target as Node) &&
        inputRef.value &&
        !inputRef.value.contains(event.target as Node)
    ) {
        isOpen.value = false;
    }
};

// Navegação com teclado
const handleKeyDown = (e: KeyboardEvent) => {
    if (!isOpen.value || filteredItems.value.length === 0) {
        if (e.key === 'ArrowDown' && searchTerm.value.length >= 1) {
            isOpen.value = true;
        }
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex.value = Math.min(
            highlightedIndex.value + 1,
            filteredItems.value.length - 1
        );
        scrollToHighlighted();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1);
        scrollToHighlighted();
    } else if (e.key === 'Enter' && highlightedIndex.value >= 0) {
        e.preventDefault();
        selectItem(filteredItems.value[highlightedIndex.value]);
    } else if (e.key === 'Escape') {
        isOpen.value = false;
        highlightedIndex.value = -1;
    }
};

const scrollToHighlighted = () => {
    const highlightedElement = document.querySelector(
        `[data-cid10-index="${highlightedIndex.value}"]`
    );
    if (highlightedElement) {
        highlightedElement.scrollIntoView({ block: 'nearest' });
    }
};

const selectItem = (item: { code: string; description: string }) => {
    searchTerm.value = item.code;
    isOpen.value = false;
    highlightedIndex.value = -1;
    emit('select', item);
    inputRef.value?.blur();
};

const clearInput = () => {
    searchTerm.value = '';
    isOpen.value = false;
    highlightedIndex.value = -1;
    inputRef.value?.focus();
};

const currentDescription = computed(() => {
    if (!searchTerm.value) return '';
    return getDescription(searchTerm.value);
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    if (props.modelValue) {
        searchTerm.value = props.modelValue;
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" :class="props.class">
        <div class="relative">
            <Input
                ref="inputRef"
                :model-value="searchTerm"
                @update:model-value="searchTerm = $event"
                @keydown="handleKeyDown"
                @focus="isOpen = searchTerm.length >= 1"
                :placeholder="placeholder"
                :disabled="disabled"
                class="font-mono pr-10"
                maxlength="10"
            />
            <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                <button
                    v-if="searchTerm"
                    @click="clearInput"
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    tabindex="-1"
                >
                    <X class="w-4 h-4" />
                </button>
                <Search class="w-4 h-4 text-gray-400" />
            </div>
        </div>

        <!-- Descrição do código atual -->
        <p v-if="currentDescription && !isOpen" class="text-xs text-gray-600 mt-1 px-1">
            {{ currentDescription }}
        </p>

        <!-- Dropdown de resultados -->
        <div
            v-if="isOpen && filteredItems.length > 0"
            ref="dropdownRef"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
        >
            <div
                v-for="(item, index) in filteredItems"
                :key="item.code"
                :data-cid10-index="index"
                @click="selectItem(item)"
                @mouseenter="highlightedIndex = index"
                :class="[
                    'px-3 py-2 cursor-pointer transition-colors',
                    highlightedIndex === index
                        ? 'bg-primary/10 text-gray-900'
                        : 'hover:bg-gray-50 text-gray-700'
                ]"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-semibold text-primary">{{ item.code }}</span>
                            <Check
                                v-if="searchTerm.toUpperCase() === item.code.toUpperCase()"
                                class="w-3 h-3 text-green-600"
                            />
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">{{ item.description }}</p>
                        <p v-if="item.category" class="text-xs text-gray-400 mt-0.5">
                            {{ item.category }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagem quando não há resultados -->
        <div
            v-if="isOpen && searchTerm.length >= 1 && filteredItems.length === 0"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-3"
        >
            <p class="text-sm text-gray-500 text-center">
                Nenhum código CID-10 encontrado para "{{ searchTerm }}"
            </p>
        </div>
    </div>
</template>

