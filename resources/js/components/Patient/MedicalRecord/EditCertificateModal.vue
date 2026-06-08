<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { MedicalCertificate } from '@/types/medical-records';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    certificate: MedicalCertificate;
    patientId: string;
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({
    type: props.certificate.type,
    start_date: props.certificate.start_date ?? '',
    end_date: props.certificate.end_date ?? '',
    days: props.certificate.days ?? null,
    reason: props.certificate.reason,
    restrictions: props.certificate.restrictions ?? '',
    change_reason: '',
});

const patchUrl = computed(() => `/doctor/patients/${props.patientId}/medical-record/certificates/${props.certificate.id}`);

function submit(): void {
    form.patch(patchUrl.value, {
        onSuccess: () => {
            form.reset('change_reason');
            emit('close');
        },
    });
}

const types = [
    { value: 'absence', label: 'Afastamento' },
    { value: 'attendance', label: 'Comparecimento' },
    { value: 'disability', label: 'Incapacidade' },
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
                <DialogTitle>Editar atestado médico</DialogTitle>
            </DialogHeader>

            <form class="mt-4 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto pr-1" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label for="cert-type">Tipo</Label>
                    <select
                        id="cert-type"
                        v-model="form.type"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                    >
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1.5">
                        <Label for="cert-start">Data início</Label>
                        <Input id="cert-start" v-model="form.start_date" type="date" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="cert-end">Data fim</Label>
                        <Input id="cert-end" v-model="form.end_date" type="date" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="cert-days">Dias</Label>
                        <Input id="cert-days" v-model="form.days" type="number" min="1" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="cert-reason">Motivo clínico</Label>
                    <Textarea id="cert-reason" v-model="form.reason" rows="3" class="resize-none" :class="{ 'border-red-500': form.errors.reason }" />
                    <p v-if="form.errors.reason" class="text-xs text-red-600">{{ form.errors.reason }}</p>
                </div>

                <div class="space-y-1.5">
                    <Label for="cert-restrictions">Restrições</Label>
                    <Textarea id="cert-restrictions" v-model="form.restrictions" rows="2" class="resize-none" />
                </div>

                <div class="space-y-1.5 border-t border-[#dde5ea] pt-4">
                    <Label for="cert-change-reason"> Motivo da alteração <span class="text-red-500">*</span> </Label>
                    <Textarea
                        id="cert-change-reason"
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
