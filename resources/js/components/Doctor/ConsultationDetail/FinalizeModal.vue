<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { CheckCircle2, X } from 'lucide-vue-next';

defineProps<{
    open: boolean;
    hasChiefComplaint: boolean;
    hasDiagnosis: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Finalizar Consulta</DialogTitle>
                <DialogDescription>
                    Ao finalizar, a consulta será marcada como concluída. Você ainda poderá editar os dados posteriormente se necessário.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-2 py-4">
                <div class="flex items-center gap-2">
                    <CheckCircle2 v-if="hasChiefComplaint" class="h-5 w-5 text-green-500" />
                    <X v-else class="h-5 w-5 text-red-500" />
                    <span>Queixa principal</span>
                </div>
                <div class="flex items-center gap-2">
                    <CheckCircle2 v-if="hasDiagnosis" class="h-5 w-5 text-green-500" />
                    <X v-else class="h-5 w-5 text-red-500" />
                    <span>Diagnóstico</span>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Cancelar</Button>
                <Button @click="emit('confirm')">Finalizar Consulta</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
