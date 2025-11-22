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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { AlertCircle, CheckCircle, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Props {
    isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

const page = usePage();
const user = (page.props.auth as any).user;

const form = ref({
    title: '',
    description: '',
    steps_to_reproduce: '',
    expected_behavior: '',
    actual_behavior: '',
    severity: 'medium' as 'low' | 'medium' | 'high' | 'critical',
});

const isSubmitting = ref(false);
const submitError = ref<string | null>(null);
const submitSuccess = ref(false);

const severityOptions = [
    { value: 'low', label: 'Baixo' },
    { value: 'medium', label: 'Médio' },
    { value: 'high', label: 'Alto' },
    { value: 'critical', label: 'Crítico' },
];

const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        steps_to_reproduce: '',
        expected_behavior: '',
        actual_behavior: '',
        severity: 'medium',
    };
    submitError.value = null;
    submitSuccess.value = false;
};

const submit = async () => {
    if (!form.value.title.trim() || !form.value.description.trim()) {
        submitError.value = 'Por favor, preencha pelo menos o título e a descrição do bug.';
        return;
    }

    isSubmitting.value = true;
    submitError.value = null;

    try {
        // TODO: Implementar envio real para backend
        // await axios.post('/api/bug-reports', {
        //     ...form.value,
        //     user_id: user.id,
        //     browser: navigator.userAgent,
        //     url: typeof window !== 'undefined' ? window.location.href : '',
        // });

        // Simular envio
        await new Promise(resolve => setTimeout(resolve, 1000));

        submitSuccess.value = true;
        resetForm();

        setTimeout(() => {
            submitSuccess.value = false;
            emit('saved');
            emit('close');
        }, 1500);
    } catch (error: any) {
        submitError.value = error.response?.data?.message || 'Erro ao enviar notificação de bug. Tente novamente.';
    } finally {
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    if (!isSubmitting.value) {
        resetForm();
        emit('close');
    }
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => { if (!value) handleClose() }">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-xl font-bold">Reportar Novo Bug</DialogTitle>
                <DialogDescription>
                    Ajude-nos a melhorar a plataforma reportando bugs ou problemas que você encontrou
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="title">Título do Bug *</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        placeholder="Ex: Erro ao salvar perfil"
                        required
                        :disabled="isSubmitting"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="severity">Gravidade *</Label>
                    <select
                        id="severity"
                        v-model="form.severity"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        required
                        :disabled="isSubmitting"
                    >
                        <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Descrição do Bug *</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Descreva o bug em detalhes..."
                        rows="4"
                        required
                        :disabled="isSubmitting"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="steps_to_reproduce">Passos para Reproduzir</Label>
                    <Textarea
                        id="steps_to_reproduce"
                        v-model="form.steps_to_reproduce"
                        placeholder="1. Acesse a página X&#10;2. Clique em Y&#10;3. O erro ocorre..."
                        rows="4"
                        :disabled="isSubmitting"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="expected_behavior">Comportamento Esperado</Label>
                    <Textarea
                        id="expected_behavior"
                        v-model="form.expected_behavior"
                        placeholder="O que deveria acontecer?"
                        rows="3"
                        :disabled="isSubmitting"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="actual_behavior">Comportamento Atual</Label>
                    <Textarea
                        id="actual_behavior"
                        v-model="form.actual_behavior"
                        placeholder="O que está acontecendo?"
                        rows="3"
                        :disabled="isSubmitting"
                    />
                </div>

                <div v-if="submitError" class="rounded-lg border border-red-200 bg-red-50 p-4 flex items-start gap-3">
                    <AlertCircle class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
                    <p class="text-sm text-red-800">{{ submitError }}</p>
                </div>

                <div v-if="submitSuccess" class="rounded-lg border border-green-200 bg-green-50 p-4 flex items-start gap-3">
                    <CheckCircle class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                    <p class="text-sm text-green-800">Bug reportado com sucesso! Obrigado por nos ajudar a melhorar a plataforma.</p>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                        :disabled="isSubmitting"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="isSubmitting">
                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmitting ? 'Enviando...' : 'Enviar Notificação' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

