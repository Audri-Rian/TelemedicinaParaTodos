<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ConsultationPatient, RecentConsultation } from '@/types/consultation-detail';
import { router } from '@inertiajs/vue3';
import { AlertCircle, ChevronDown, ChevronUp, Clock, FileText, Heart, Pill } from 'lucide-vue-next';

// @ts-expect-error - route helper from Ziggy
declare const route: (name: string, params?: unknown) => string;

defineProps<{
    patient: ConsultationPatient;
    recentConsultations: RecentConsultation[];
    collapsed: boolean;
}>();

const emit = defineEmits<{ toggle: [] }>();
</script>

<template>
    <div :class="['transition-all duration-300', collapsed ? 'w-0 overflow-hidden' : 'w-80 flex-shrink-0']">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-lg">Prontuário Resumido</CardTitle>
                <Button variant="ghost" size="sm" @click="emit('toggle')">
                    <ChevronUp v-if="!collapsed" class="h-4 w-4" />
                    <ChevronDown v-else class="h-4 w-4" />
                </Button>
            </CardHeader>
            <CardContent v-if="!collapsed" class="max-h-[calc(100vh-200px)] space-y-4 overflow-y-auto">
                <div v-if="patient.allergies.length > 0" class="border-b pb-3">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-red-600">
                        <AlertCircle class="h-4 w-4" />
                        Alergias
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <Badge v-for="allergy in patient.allergies" :key="allergy" variant="destructive" class="text-xs">
                            {{ allergy }}
                        </Badge>
                    </div>
                </div>

                <div v-if="patient.current_medications" class="border-b pb-3">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                        <Pill class="h-4 w-4 text-blue-600" />
                        Medicações em Uso
                    </h3>
                    <p class="text-sm leading-relaxed text-gray-700">{{ patient.current_medications }}</p>
                </div>

                <div v-if="patient.medical_history" class="border-b pb-3">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                        <FileText class="h-4 w-4 text-purple-600" />
                        Histórico Médico
                    </h3>
                    <p class="line-clamp-3 text-sm leading-relaxed text-gray-700">{{ patient.medical_history }}</p>
                </div>

                <div class="border-b pb-3">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                        <Heart class="h-4 w-4 text-primary" />
                        Dados Básicos
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Idade:</span>
                            <span class="text-gray-900">{{ patient.age }} anos</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-600">Gênero:</span>
                            <span class="text-gray-900 capitalize">{{
                                patient.gender === 'male' ? 'Masculino' : patient.gender === 'female' ? 'Feminino' : 'Outro'
                            }}</span>
                        </div>
                        <div v-if="patient.blood_type" class="flex justify-between">
                            <span class="font-medium text-gray-600">Tipo Sanguíneo:</span>
                            <span class="text-gray-900">{{ patient.blood_type }}</span>
                        </div>
                        <div v-if="patient.height && patient.weight" class="flex justify-between">
                            <span class="font-medium text-gray-600">IMC:</span>
                            <span class="font-semibold text-gray-900">{{ patient.bmi?.toFixed(1) }}</span>
                        </div>
                        <div v-if="patient.height" class="flex justify-between">
                            <span class="font-medium text-gray-600">Altura:</span>
                            <span class="text-gray-900">{{ patient.height }} cm</span>
                        </div>
                        <div v-if="patient.weight" class="flex justify-between">
                            <span class="font-medium text-gray-600">Peso:</span>
                            <span class="text-gray-900">{{ patient.weight }} kg</span>
                        </div>
                    </div>
                </div>

                <div v-if="recentConsultations.length > 0" class="border-b pb-3">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                        <Clock class="h-4 w-4 text-amber-600" />
                        Últimas Consultas
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="consultation in recentConsultations"
                            :key="consultation.id"
                            class="cursor-pointer rounded border border-gray-200 bg-gray-50 p-2 text-sm transition-colors hover:bg-gray-100"
                            @click="router.get(route('doctor.consultations.detail', consultation.id))"
                        >
                            <p class="font-medium text-gray-900">{{ consultation.date }}</p>
                            <p v-if="consultation.diagnosis" class="mt-1 text-xs text-gray-700">{{ consultation.diagnosis }}</p>
                            <p v-if="consultation.cid10" class="mt-1 text-xs text-gray-500">
                                CID-10: <span class="font-mono">{{ consultation.cid10 }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <Button variant="outline" class="w-full" @click="router.get(`/doctor/patients/${patient.id}/medical-record`)">
                    <FileText class="mr-2 h-4 w-4" />
                    Ver Prontuário Completo
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
