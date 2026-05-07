<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/composables/useFormatters';
import type { AutoSaveStatus } from '@/types/consultation-detail';
import { AlertCircle, CheckCircle2, Clock, Download, Loader2, MessageSquare, Save } from 'lucide-vue-next';
import type { Component } from 'vue';

defineProps<{
    patientName: string;
    scheduledDateFormatted: string;
    statusBadge: { label: string; color: string; icon: Component };
    isInProgress: boolean;
    isCompleted: boolean;
    isScheduled: boolean;
    elapsedTime?: number | null;
    elapsedTimeFormatted: string;
    autoSaveStatus: AutoSaveStatus;
    hasUnsavedChanges: boolean;
    isSaving: boolean;
    lastSaved: Date | null;
}>();

const emit = defineEmits<{
    start: [];
    save: [];
    finalize: [];
    pdf: [];
    messages: [];
}>();

const { formatTime } = useFormatters();
</script>

<template>
    <div class="sticky top-0 z-50 border-b bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">{{ patientName }}</h1>
                        <p class="text-sm text-gray-600">{{ scheduledDateFormatted }}</p>
                    </div>
                    <Badge :class="statusBadge.color" class="text-white">
                        <component :is="statusBadge.icon" class="mr-1 h-4 w-4" />
                        {{ statusBadge.label }}
                    </Badge>
                    <div v-if="isInProgress && elapsedTime" class="flex items-center gap-2 text-sm text-gray-600">
                        <Clock class="h-4 w-4" />
                        <span class="font-mono">{{ elapsedTimeFormatted }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div v-if="isInProgress" class="flex items-center gap-2 text-xs">
                        <div v-if="autoSaveStatus === 'saving'" class="flex items-center gap-1 text-blue-600">
                            <Loader2 class="h-3 w-3 animate-spin" />
                            <span>Salvando...</span>
                        </div>
                        <div v-else-if="autoSaveStatus === 'saved'" class="flex items-center gap-1 text-green-600">
                            <CheckCircle2 class="h-3 w-3" />
                            <span>Salvo</span>
                        </div>
                        <div v-else-if="autoSaveStatus === 'error'" class="flex items-center gap-1 text-red-600">
                            <AlertCircle class="h-3 w-3" />
                            <span>Erro ao salvar</span>
                        </div>
                        <div v-else-if="hasUnsavedChanges" class="flex items-center gap-1 text-amber-600">
                            <Clock class="h-3 w-3" />
                            <span>Alterações não salvas</span>
                        </div>
                        <div v-else-if="lastSaved" class="text-gray-400">Salvo às {{ formatTime(lastSaved) }}</div>
                    </div>

                    <div v-else-if="isSaving" class="flex items-center gap-2 text-sm text-gray-500">
                        <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-gray-900"></div>
                        Salvando...
                    </div>
                    <div v-else-if="lastSaved" class="text-xs text-gray-400">Salvo às {{ formatTime(lastSaved) }}</div>

                    <Button v-if="isScheduled" variant="default" @click="emit('start')"> Iniciar Consulta </Button>
                    <Button v-if="isInProgress || isCompleted" variant="outline" :disabled="isSaving" @click="emit('save')">
                        <Save class="mr-2 h-4 w-4" />
                        Salvar
                    </Button>
                    <Button v-if="isInProgress" variant="default" @click="emit('finalize')">
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        Finalizar Consulta
                    </Button>
                    <Button v-if="isCompleted" variant="outline" @click="emit('pdf')">
                        <Download class="mr-2 h-4 w-4" />
                        Gerar PDF
                    </Button>
                    <Button v-if="isInProgress || isCompleted" variant="outline" title="Enviar mensagem ao paciente" @click="emit('messages')">
                        <MessageSquare class="mr-2 h-4 w-4" />
                        Mensagens
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
