<script setup lang="ts">
import { PhoneOff } from 'lucide-vue-next';

defineProps<{ open: boolean }>();

const emit = defineEmits<{ close: []; confirmEnd: [] }>();
</script>

<template>
    <div v-if="open" class="modal-backdrop" @click.self="emit('close')">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="pcv-end-title" @click.stop>
            <h3 id="pcv-end-title">Sair da consulta?</h3>
            <p>Você pode voltar a qualquer momento enquanto a chamada estiver ativa. As prescrições e exames recebidos ficam salvos na sua conta.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" @click="emit('close')">Continuar na consulta</button>
                <button type="button" class="btn btn-destructive-solid" @click="emit('confirmEnd')">
                    <PhoneOff class="h-3.5 w-3.5" />
                    Sair agora
                </button>
            </div>
        </div>
    </div>
</template>
