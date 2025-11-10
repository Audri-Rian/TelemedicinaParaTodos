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
import { AlertCircle, User } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import * as profileRoutes from '@/routes/profile';

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const goToProfile = () => {
    emit('close');
    router.visit(profileRoutes.edit().url);
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => { if (!value) emit('close') }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100">
                        <AlertCircle class="h-5 w-5 text-yellow-600" />
                    </div>
                    <DialogTitle class="text-xl font-bold text-gray-900">
                        Cadastro Incompleto
                    </DialogTitle>
                </div>
                <DialogDescription class="text-base text-gray-700 pt-2">
                    Para agendar consultas, é necessário completar a segunda etapa de autenticação.
                </DialogDescription>
            </DialogHeader>

            <div class="py-4">
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 space-y-3">
                    <p class="text-sm font-semibold text-yellow-800">
                        O que você precisa fazer:
                    </p>
                    <ul class="space-y-2 text-sm text-yellow-700">
                        <li class="flex items-start gap-2">
                            <span class="text-yellow-600">•</span>
                            <span>Complete seu cadastro adicionando o <strong>contato de emergência</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-yellow-600">•</span>
                            <span>Esta informação é obrigatória para garantir sua segurança durante as consultas.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-yellow-600">•</span>
                            <span>O processo leva apenas alguns minutos.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button 
                    variant="outline" 
                    class="border border-gray-300 bg-white text-gray-900 hover:bg-gray-50"
                    @click="emit('close')"
                >
                    Fechar
                </Button>
                <Button 
                    class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold flex items-center gap-2"
                    @click="goToProfile"
                >
                    <User class="h-4 w-4" />
                    Completar Cadastro
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

