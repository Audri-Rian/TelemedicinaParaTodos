<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Video, X } from 'lucide-vue-next';

defineProps<{
    open: boolean;
    callerLabel?: string;
    appointmentLabel?: string;
    isAccepting?: boolean;
}>();

const emit = defineEmits<{
    enter: [];
    dismiss: [];
}>();
</script>

<template>
    <Dialog
        :open="open"
        @update:open="
            (v) => {
                if (!v) emit('dismiss');
            }
        "
    >
        <DialogContent class="sm:max-w-sm">
            <DialogHeader class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-100">
                        <Video class="h-5 w-5 text-teal-600" />
                    </div>
                    <DialogTitle class="text-lg font-bold text-gray-900"> Videochamada ativa </DialogTitle>
                </div>
                <DialogDescription class="text-sm text-gray-600">
                    <span v-if="appointmentLabel">Consulta: {{ appointmentLabel }}</span>
                    <span v-else>Sua videochamada foi iniciada e está aguardando você.</span>
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="mt-4 flex gap-2">
                <Button variant="outline" size="sm" class="flex-1" @click="emit('dismiss')">
                    <X class="mr-1 h-4 w-4" />
                    Fechar
                </Button>
                <Button size="sm" class="flex-1 bg-teal-600 text-white hover:bg-teal-700" :disabled="isAccepting" @click="emit('enter')">
                    <Video class="mr-1 h-4 w-4" />
                    {{ isAccepting ? 'Entrando...' : 'Entrar na chamada' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
