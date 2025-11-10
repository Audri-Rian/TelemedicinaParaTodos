<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { AlertTriangle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
    isOpen: boolean;
    appointmentDate?: string;
    appointmentTime?: string;
    isSubmitting?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isSubmitting: false,
});

const emit = defineEmits<{
    close: [];
    confirm: [reason: string | null];
}>();

const reason = ref('');

const handleConfirm = () => {
    if (props.isSubmitting) return;
    emit('confirm', reason.value.trim() || null);
};

const handleClose = () => {
    if (props.isSubmitting) return; // Prevenir fechamento durante submissão
    reason.value = '';
    emit('close');
};

const formattedDate = computed(() => {
    if (!props.appointmentDate) return '';
    try {
        const date = new Date(props.appointmentDate);
        return date.toLocaleDateString('pt-BR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    } catch {
        return props.appointmentDate;
    }
});
</script>

<template>
    <Dialog :open="props.isOpen" @update:open="(value) => { if (!value && !props.isSubmitting) handleClose() }">
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
                    Tem certeza que deseja cancelar esta consulta?
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <div v-if="props.appointmentDate || props.appointmentTime" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm font-semibold text-gray-800 mb-1">Detalhes da Consulta:</p>
                    <p v-if="props.appointmentDate" class="text-sm text-gray-700">
                        <strong>Data:</strong> {{ formattedDate }}
                    </p>
                    <p v-if="props.appointmentTime" class="text-sm text-gray-700">
                        <strong>Horário:</strong> {{ props.appointmentTime }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="cancel-reason">Motivo do Cancelamento (Opcional)</Label>
                    <Textarea
                        id="cancel-reason"
                        v-model="reason"
                        placeholder="Informe o motivo do cancelamento..."
                        :rows="4"
                        :disabled="props.isSubmitting"
                        class="resize-none"
                    />
                    <p class="text-xs text-gray-500">
                        Este campo é opcional, mas pode ajudar a melhorar nossos serviços.
                    </p>
                </div>

                <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita. A consulta será cancelada permanentemente.
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button
                    variant="outline"
                    class="border border-gray-300 bg-white text-gray-900 hover:bg-gray-50"
                    @click="handleClose"
                    :disabled="props.isSubmitting"
                >
                    Não, manter consulta
                </Button>
                <Button
                    class="bg-red-600 hover:bg-red-700 text-white font-semibold"
                    @click="handleConfirm"
                    :disabled="props.isSubmitting"
                >
                    <span v-if="props.isSubmitting">Cancelando...</span>
                    <span v-else>Sim, cancelar consulta</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

