<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { PatientProfile } from '@/types/medical-records';
import { Calendar, Clock, FileText, Mail, Phone, Shield, User } from 'lucide-vue-next';

interface Consultation {
    id: string;
    date?: string | null;
    time?: string | null;
    status: string;
    statusClass: string;
    notes?: string | null;
}

interface PatientProfileExtra {
    email?: string | null;
    phone?: string | null;
    cpf?: string | null;
    emergency_contact?: string | null;
    medical_history?: string[];
    recent_consultations?: Consultation[];
    total_consultations_with_doctor?: number;
}

defineProps<{
    patient: PatientProfile;
    patientProfile?: PatientProfileExtra;
}>();

const { getInitials } = useInitials();
</script>

<template>
    <div class="space-y-6">
        <!-- Identity card -->
        <section class="rounded-lg border border-[#dde5ea] bg-white p-5">
            <div class="flex items-start gap-5">
                <Avatar class="h-16 w-16 shrink-0">
                    <AvatarImage :src="patient.user.avatar ?? undefined" :alt="patient.user.name" />
                    <AvatarFallback class="bg-[#e5f1f2] text-lg font-black text-[#0f6e78]">
                        {{ getInitials(patient.user.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="min-w-0 flex-1">
                    <h2 class="text-xl font-black text-gray-950">{{ patient.user.name }}</h2>
                    <p class="mt-0.5 text-sm font-semibold text-gray-500">
                        {{ patient.age ? `${patient.age} anos` : '—' }}
                        <span v-if="patient.date_of_birth"> · {{ patient.date_of_birth }}</span>
                        <span v-if="patient.gender"> · {{ patient.gender }}</span>
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div v-if="patientProfile?.email" class="flex items-center gap-2 text-sm text-gray-600">
                            <Mail class="h-4 w-4 shrink-0 text-gray-400" />
                            {{ patientProfile.email }}
                        </div>
                        <div v-if="patientProfile?.phone" class="flex items-center gap-2 text-sm text-gray-600">
                            <Phone class="h-4 w-4 shrink-0 text-gray-400" />
                            {{ patientProfile.phone }}
                        </div>
                        <div v-if="patientProfile?.cpf" class="flex items-center gap-2 text-sm text-gray-600">
                            <User class="h-4 w-4 shrink-0 text-gray-400" />
                            CPF: {{ patientProfile.cpf }}
                        </div>
                        <div v-if="patientProfile?.emergency_contact" class="flex items-center gap-2 text-sm text-gray-600">
                            <Shield class="h-4 w-4 shrink-0 text-gray-400" />
                            {{ patientProfile.emergency_contact }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Clinical summary -->
            <section class="rounded-lg border border-[#dde5ea] bg-white p-5 lg:col-span-1">
                <h3 class="mb-4 flex items-center gap-2 font-black text-gray-950">
                    <FileText class="h-4 w-4 text-[#0f6e78]" />
                    Resumo clínico
                </h3>

                <div class="space-y-4 text-sm">
                    <div v-if="patient.blood_type">
                        <span class="font-black text-gray-950">Tipo sanguíneo:</span>
                        <span class="ml-1 text-gray-700">{{ patient.blood_type }}</span>
                    </div>
                    <div v-if="patient.allergies">
                        <span class="font-black text-gray-950">Alergias:</span>
                        <span class="ml-1 text-gray-700">{{ patient.allergies }}</span>
                    </div>
                    <div v-if="patient.current_medications">
                        <span class="font-black text-gray-950">Medicações em uso:</span>
                        <span class="ml-1 text-gray-700">{{ patient.current_medications }}</span>
                    </div>
                    <div v-if="patient.height || patient.weight">
                        <span class="font-black text-gray-950">Medidas:</span>
                        <span class="ml-1 text-gray-700">
                            {{ patient.height ? `${patient.height} cm` : '' }}
                            {{ patient.height && patient.weight ? '·' : '' }}
                            {{ patient.weight ? `${patient.weight} kg` : '' }}
                            {{ patient.bmi ? `· IMC ${patient.bmi} (${patient.bmi_category})` : '' }}
                        </span>
                    </div>

                    <template v-if="patientProfile?.medical_history?.length">
                        <div class="pt-2">
                            <p class="mb-2 font-black text-gray-950">Histórico médico</p>
                            <ul class="space-y-1">
                                <li
                                    v-for="condition in patientProfile.medical_history"
                                    :key="condition"
                                    class="flex items-center gap-2 text-gray-700"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-red-400" />
                                    {{ condition }}
                                </li>
                            </ul>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Recent consultations with this doctor -->
            <section class="rounded-lg border border-[#dde5ea] bg-white p-5 lg:col-span-2">
                <h3 class="mb-4 flex items-center gap-2 font-black text-gray-950">
                    <Calendar class="h-4 w-4 text-[#0f6e78]" />
                    Consultas recentes comigo
                    <span class="ml-auto text-xs font-semibold text-gray-500">
                        {{ patientProfile?.total_consultations_with_doctor ?? 0 }} concluídas
                    </span>
                </h3>

                <div v-if="patientProfile?.recent_consultations?.length" class="space-y-3">
                    <div
                        v-for="consultation in patientProfile.recent_consultations"
                        :key="consultation.id"
                        class="flex items-center justify-between rounded-lg border border-[#dde5ea] p-3"
                    >
                        <div class="flex items-center gap-4">
                            <div class="text-center">
                                <p class="text-sm font-black text-gray-950">{{ consultation.date }}</p>
                                <p class="flex items-center gap-1 text-xs text-gray-500">
                                    <Clock class="h-3 w-3" />
                                    {{ consultation.time }}
                                </p>
                            </div>
                            <p v-if="consultation.notes" class="text-sm text-gray-600">{{ consultation.notes }}</p>
                        </div>
                        <span :class="consultation.statusClass" class="rounded-full px-2 py-0.5 text-xs font-black">
                            {{ consultation.status }}
                        </span>
                    </div>
                </div>

                <p v-else class="text-sm text-gray-500">Nenhuma consulta registrada.</p>
            </section>
        </div>
    </div>
</template>
