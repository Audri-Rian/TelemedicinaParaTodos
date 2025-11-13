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
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { MapPin, Video, Building2, Phone, Plus } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
    isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    confirm: [data: {
        name: string;
        type: string;
        address?: string;
        phone?: string;
        description?: string;
    }];
}>();

// Form fields
const locationName = ref('');
const locationType = ref('consultorio');
const address = ref('');
const phone = ref('');
const description = ref('');

const locationTypes = [
    { value: 'teleconsulta', label: 'Teleconsulta', icon: Video },
    { value: 'consultorio', label: 'Consultório', icon: Building2 },
    { value: 'hospital', label: 'Hospital', icon: Building2 },
    { value: 'clinica', label: 'Clínica', icon: Building2 },
];

const handleClose = () => {
    // Reset form
    locationName.value = '';
    locationType.value = 'consultorio';
    address.value = '';
    phone.value = '';
    description.value = '';
    emit('close');
};

const isTeleconsultation = computed(() => locationType.value === 'teleconsulta');

const handleConfirm = () => {
    if (!locationName.value.trim()) return;
    
    emit('confirm', {
        name: locationName.value.trim(),
        type: locationType.value,
        address: address.value.trim() || undefined,
        phone: phone.value.trim() || undefined,
        description: description.value.trim() || undefined,
    });
    
    handleClose();
};
</script>

<template>
    <Dialog :open="props.isOpen" @update:open="(value) => { if (!value) handleClose() }">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                        <MapPin class="h-5 w-5 text-primary" />
                    </div>
                    <DialogTitle class="text-xl font-bold text-gray-900">
                        Adicionar Local de Atendimento
                    </DialogTitle>
                </div>
                <DialogDescription class="text-base text-gray-700 pt-2">
                    Preencha as informações do novo local de atendimento
                </DialogDescription>
            </DialogHeader>

            <div class="py-4 space-y-6">
                <!-- Nome do Local -->
                <div class="space-y-2">
                    <Label for="location-name" class="text-sm font-medium text-gray-900">
                        Nome do Local <span class="text-red-500">*</span>
                    </Label>
                    <Input
                        id="location-name"
                        v-model="locationName"
                        type="text"
                        placeholder="Ex: Consultório Dr. João, Clínica Saúde, etc."
                        class="w-full"
                    />
                    <p class="text-xs text-gray-500">
                        Digite um nome identificador para este local
                    </p>
                </div>

                <!-- Tipo de Local -->
                <div class="space-y-2">
                    <Label for="location-type" class="text-sm font-medium text-gray-900">
                        Tipo de Local <span class="text-red-500">*</span>
                    </Label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="type in locationTypes"
                            :key="type.value"
                            @click="locationType = type.value"
                            :class="[
                                'flex items-center gap-3 p-3 border-2 rounded-lg transition-all duration-200',
                                locationType === type.value
                                    ? 'border-primary bg-primary/5 shadow-sm'
                                    : 'border-gray-200 hover:border-gray-300 bg-white'
                            ]"
                        >
                            <component :is="type.icon" 
                                :class="[
                                    'w-5 h-5',
                                    locationType === type.value ? 'text-primary' : 'text-gray-400'
                                ]"
                            />
                            <span :class="[
                                'text-sm font-medium',
                                locationType === type.value ? 'text-gray-900' : 'text-gray-600'
                            ]">
                                {{ type.label }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Endereço (se não for teleconsulta) -->
                <div v-if="!isTeleconsultation" class="space-y-2">
                    <Label for="location-address" class="text-sm font-medium text-gray-900">
                        Endereço Completo
                    </Label>
                    <Textarea
                        id="location-address"
                        v-model="address"
                        placeholder="Ex: Rua das Flores, 123 - Centro, São Paulo - SP, 01234-567"
                        :rows="3"
                        class="w-full resize-none"
                    />
                    <p class="text-xs text-gray-500">
                        Digite o endereço completo do local de atendimento
                    </p>
                </div>

                <!-- Telefone -->
                <div class="space-y-2">
                    <Label for="location-phone" class="text-sm font-medium text-gray-900">
                        Telefone de Contato
                    </Label>
                    <div class="relative">
                        <Phone class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                        <Input
                            id="location-phone"
                            v-model="phone"
                            type="tel"
                            placeholder="(11) 99999-9999"
                            class="w-full pl-10"
                        />
                    </div>
                    <p class="text-xs text-gray-500">
                        Telefone para contato relacionado a este local
                    </p>
                </div>

                <!-- Descrição/Observações -->
                <div class="space-y-2">
                    <Label for="location-description" class="text-sm font-medium text-gray-900">
                        Observações
                    </Label>
                    <Textarea
                        id="location-description"
                        v-model="description"
                        placeholder="Informações adicionais sobre o local (ex: acesso, estacionamento, referências, etc.)"
                        :rows="4"
                        class="w-full resize-none"
                    />
                    <p class="text-xs text-gray-500">
                        Adicione observações importantes sobre este local
                    </p>
                </div>

                <!-- Info para Teleconsulta -->
                <div v-if="isTeleconsultation" class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                    <div class="flex items-start gap-2">
                        <Video class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 mb-1">
                                Informações sobre Teleconsulta
                            </p>
                            <p class="text-xs text-gray-600">
                                Para teleconsultas, os pacientes receberão um link para acessar a consulta virtual. 
                                O link será gerado automaticamente quando a consulta for agendada.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button
                    variant="outline"
                    class="border border-gray-300 bg-white text-gray-900 hover:bg-gray-50"
                    @click="handleClose"
                >
                    Cancelar
                </Button>
                <Button
                    class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold"
                    @click="handleConfirm"
                    :disabled="!locationName.trim()"
                >
                    <Plus class="w-4 h-4 mr-2" />
                    Adicionar Local
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

