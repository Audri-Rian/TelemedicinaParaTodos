<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { ClinicalNote } from '@/types/medical-records';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    note: ClinicalNote;
    patientId: string;
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({
    title: props.note.title,
    content: props.note.content,
    is_private: props.note.is_private,
    category: props.note.category,
    tags: (props.note.tags ?? []).join(', '),
    change_reason: '',
});

const patchUrl = computed(() => `/doctor/patients/${props.patientId}/medical-record/notes/${props.note.id}`);

function submit(): void {
    form.transform((data) => ({
        ...data,
        tags: data.tags
            .split(',')
            .map((t) => t.trim())
            .filter(Boolean),
    })).patch(patchUrl.value, {
        onSuccess: () => {
            form.reset('change_reason');
            emit('close');
        },
    });
}

const categories = [
    { value: 'general', label: 'Geral' },
    { value: 'diagnosis', label: 'Diagnóstico' },
    { value: 'treatment', label: 'Tratamento' },
    { value: 'follow_up', label: 'Acompanhamento' },
    { value: 'other', label: 'Outro' },
];
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (v) => {
                if (!v) emit('close');
            }
        "
    >
        <DialogContent class="flex max-h-[85vh] flex-col sm:max-w-lg">
            <DialogHeader class="shrink-0">
                <DialogTitle>Editar anotação clínica</DialogTitle>
            </DialogHeader>

            <form class="mt-4 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto pr-1" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label for="note-title">Título</Label>
                    <Input id="note-title" v-model="form.title" :class="{ 'border-red-500': form.errors.title }" />
                    <p v-if="form.errors.title" class="text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div class="space-y-1.5">
                    <Label for="note-content">Conteúdo</Label>
                    <Textarea
                        id="note-content"
                        v-model="form.content"
                        rows="5"
                        class="resize-none"
                        :class="{ 'border-red-500': form.errors.content }"
                    />
                    <p v-if="form.errors.content" class="text-xs text-red-600">{{ form.errors.content }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="note-category">Categoria</Label>
                        <select
                            id="note-category"
                            v-model="form.category"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label>
                            <input v-model="form.is_private" type="checkbox" class="mr-1.5" />
                            Nota privada
                        </Label>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="note-tags">Etiquetas (separadas por vírgula)</Label>
                    <Input id="note-tags" v-model="form.tags" placeholder="ex: urgente, retorno" />
                </div>

                <div class="space-y-1.5 border-t border-[#dde5ea] pt-4">
                    <Label for="note-reason"> Motivo da alteração <span class="text-red-500">*</span> </Label>
                    <Textarea
                        id="note-reason"
                        v-model="form.change_reason"
                        rows="2"
                        class="resize-none"
                        placeholder="Mínimo 10 caracteres..."
                        maxlength="500"
                        :class="{ 'border-red-500': form.errors.change_reason }"
                    />
                    <p v-if="form.errors.change_reason" class="text-xs text-red-600">{{ form.errors.change_reason }}</p>
                </div>
            </form>

            <DialogFooter class="mt-4 shrink-0 gap-2">
                <Button variant="outline" :disabled="form.processing" @click="emit('close')">Cancelar</Button>
                <Button :disabled="form.processing || form.change_reason.trim().length < 10" @click="submit">
                    {{ form.processing ? 'Salvando...' : 'Salvar alterações' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
