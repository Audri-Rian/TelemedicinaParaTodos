<script setup lang="ts">
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertTriangle } from 'lucide-vue-next';

const props = defineProps<{
    isOpen: boolean;
    doctorName?: string;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

const handleConfirm = () => {
    emit('confirm');
    emit('close');
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => { if (!value) emit('close') }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                    </div>
                    <DialogTitle class="text-xl font-bold text-gray-900">
                        Cancelar Consulta
                    </DialogTitle>
                </div>
                <DialogDescription class="text-base text-gray-700 pt-2">
                    Tem certeza que deseja cancelar sua consulta com <strong>{{ props.doctorName || 'este médico' }}</strong>?
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 space-y-3">
                    <p class="text-sm font-semibold text-orange-800">
                        Informações importantes:
                    </p>
                    <ul class="space-y-2 text-sm text-orange-700">
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600">•</span>
                            <span>Cancele com pelo menos 24 horas de antecedência para evitar taxas.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600">•</span>
                            <span>Você pode reagendar sua consulta a qualquer momento.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600">•</span>
                            <span>Caso precise, entre em contato com nossa equipe de suporte.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button 
                        variant="outline" 
                        class="border border-gray-300 bg-white text-gray-900 hover:bg-gray-50"
                    >
                        Voltar
                    </Button>
                </DialogClose>
                <Button 
                    variant="destructive" 
                    class="bg-red-600 hover:bg-red-700 text-white"
                    @click="handleConfirm"
                >
                    Confirmar Cancelamento
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

