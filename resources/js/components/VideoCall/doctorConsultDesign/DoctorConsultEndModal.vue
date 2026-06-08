<script setup lang="ts">
import { PhoneOff } from 'lucide-vue-next';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    confirmEnd: [];
}>();
</script>

<template>
    <div v-if="open" class="modal-backdrop" @click.self="emit('close')">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="dcv-end-title" @click.stop>
            <h3 id="dcv-end-title">Encerrar consulta?</h3>
            <p>Suas anotações ficam salvas no prontuário. Você pode emitir prescrição e atestado depois pela aba do paciente.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" @click="emit('close')">Continuar consulta</button>
                <button type="button" class="btn btn-destructive-solid" @click="emit('confirmEnd')">
                    <PhoneOff class="h-3.5 w-3.5" />
                    Encerrar agora
                </button>
            </div>
        </div>
    </div>
</template>
