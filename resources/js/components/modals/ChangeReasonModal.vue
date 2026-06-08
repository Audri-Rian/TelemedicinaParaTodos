<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ref } from 'vue';

defineProps<{ isOpen: boolean; title?: string }>();

const emit = defineEmits<{ close: []; confirm: [reason: string] }>();

const reason = ref('');

function handleConfirm(): void {
    emit('confirm', reason.value);
    reason.value = '';
}

function handleClose(): void {
    reason.value = '';
    emit('close');
}
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (v) => {
                if (!v) handleClose();
            }
        "
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title ?? 'Motivo da alteração' }}</DialogTitle>
            </DialogHeader>

            <div class="mt-2">
                <Label for="change-reason" class="text-sm font-semibold text-gray-700">
                    Descreva o motivo da alteração <span class="text-red-500">*</span>
                </Label>
                <Textarea
                    id="change-reason"
                    v-model="reason"
                    class="mt-1.5 resize-none"
                    rows="3"
                    placeholder="Mínimo 10 caracteres..."
                    maxlength="500"
                />
                <p class="mt-1 text-right text-xs text-gray-400">{{ reason.length }}/500</p>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="handleClose">Cancelar</Button>
                <Button :disabled="reason.trim().length < 10" @click="handleConfirm">Confirmar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
