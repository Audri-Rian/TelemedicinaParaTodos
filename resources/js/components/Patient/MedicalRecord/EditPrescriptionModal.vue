<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Prescription } from '@/types/medical-records';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    prescription: Prescription;
    patientId: string;
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({
    medications: (props.prescription.medications ?? []).map((m) => ({ ...m })),
    instructions: props.prescription.instructions ?? '',
    valid_until: props.prescription.valid_until ?? '',
    change_reason: '',
});

const patchUrl = computed(() => `/doctor/patients/${props.patientId}/medical-record/prescriptions/${props.prescription.id}`);

function addMedication(): void {
    form.medications.push({ name: '', dosage: '', frequency: '' });
}

function removeMedication(idx: number): void {
    form.medications.splice(idx, 1);
}

function submit(): void {
    form.patch(patchUrl.value, {
        onSuccess: () => {
            form.reset('change_reason');
            emit('close');
        },
    });
}
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
                <DialogTitle>Editar prescrição</DialogTitle>
            </DialogHeader>

            <form class="mt-4 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto pr-1" @submit.prevent="submit">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label>Medicamentos</Label>
                        <Button type="button" variant="outline" size="sm" @click="addMedication">+ Adicionar</Button>
                    </div>

                    <div v-for="(med, idx) in form.medications" :key="idx" class="space-y-2 rounded-lg border border-[#dde5ea] p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500">Medicamento {{ idx + 1 }}</span>
                            <button
                                v-if="form.medications.length > 1"
                                type="button"
                                class="text-xs text-red-500 hover:underline"
                                @click="removeMedication(idx)"
                            >
                                Remover
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="col-span-3 space-y-1">
                                <Label :for="`med-name-${idx}`" class="text-xs">Nome</Label>
                                <Input :id="`med-name-${idx}`" v-model="med.name" placeholder="Nome do medicamento" />
                            </div>
                            <div class="space-y-1">
                                <Label :for="`med-dosage-${idx}`" class="text-xs">Dose</Label>
                                <Input :id="`med-dosage-${idx}`" v-model="med.dosage" placeholder="500mg" />
                            </div>
                            <div class="col-span-2 space-y-1">
                                <Label :for="`med-freq-${idx}`" class="text-xs">Frequência</Label>
                                <Input :id="`med-freq-${idx}`" v-model="med.frequency" placeholder="1x ao dia" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="presc-instructions">Instruções</Label>
                    <Textarea id="presc-instructions" v-model="form.instructions" rows="3" class="resize-none" />
                </div>

                <div class="space-y-1.5">
                    <Label for="presc-valid-until">Válido até</Label>
                    <Input id="presc-valid-until" v-model="form.valid_until" type="date" />
                    <p v-if="form.errors.valid_until" class="text-xs text-red-600">{{ form.errors.valid_until }}</p>
                </div>

                <div class="space-y-1.5 border-t border-[#dde5ea] pt-4">
                    <Label for="presc-reason"> Motivo da alteração <span class="text-red-500">*</span> </Label>
                    <Textarea
                        id="presc-reason"
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
