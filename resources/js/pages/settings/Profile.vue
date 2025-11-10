<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { AlertCircle } from 'lucide-vue-next';

interface Patient {
    id: string;
    emergency_contact?: string | null;
    emergency_phone?: string | null;
    medical_history?: string | null;
    allergies?: string | null;
    current_medications?: string | null;
    blood_type?: string | null;
    height?: number | null;
    weight?: number | null;
    insurance_provider?: string | null;
    insurance_number?: string | null;
    consent_telemedicine?: boolean;
}

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    patient?: Patient | null;
    bloodTypes?: string[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurações de Perfil',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const auth = page.props.auth as { isPatient: boolean; role: string | null };

// Estado para consentimento de telemedicina
const consentTelemedicineValue = ref(props.patient?.consent_telemedicine ?? false);

// Verificar se segunda etapa está completa
const isSecondStageComplete = computed(() => {
    if (!props.patient) return false;
    return !!(props.patient.emergency_contact && props.patient.emergency_phone);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações de Perfil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Informações do Perfil" description="Atualize seu nome e endereço de e-mail" />

                <Form v-bind="update.form()" class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
                    <!-- Primeira Etapa: Informações Básicas -->
                    <div class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input
                                id="name"
                                class="mt-1 block w-full"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Nome completo"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Endereço de e-mail</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Endereço de e-mail"
                            />
                            <InputError class="mt-2" :message="errors.email" />
                        </div>

                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="-mt-4 text-sm text-muted-foreground">
                                Seu endereço de e-mail não foi verificado.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                >
                                    Clique aqui para reenviar o e-mail de verificação.
                                </Link>
                            </p>

                            <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                                Um novo link de verificação foi enviado para o seu endereço de e-mail.
                            </div>
                        </div>
                    </div>

                    <!-- Segunda Etapa: Informações de Saúde e Emergência -->
                    <div v-if="auth?.isPatient || auth?.role === 'patient'" class="space-y-6 border-t pt-6">
                        <div class="flex items-center gap-2">
                            <HeadingSmall 
                                title="Segunda Etapa de Autenticação" 
                                description="Complete seu cadastro para agendar consultas"
                            />
                            <div v-if="!isSecondStageComplete" class="flex items-center gap-2 text-yellow-600">
                                <AlertCircle class="h-5 w-5" />
                                <span class="text-sm font-medium">Incompleto</span>
                            </div>
                            <div v-else class="flex items-center gap-2 text-green-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm font-medium">Completo</span>
                            </div>
                        </div>

                        <!-- Contato de Emergência (Obrigatório) -->
                        <div class="space-y-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <h3 class="text-sm font-semibold text-yellow-800">Contato de Emergência *</h3>
                            <p class="text-xs text-yellow-700">Estes campos são obrigatórios para agendar consultas.</p>
                            
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="emergency_contact">Nome do Contato de Emergência</Label>
                                    <Input
                                        id="emergency_contact"
                                        name="emergency_contact"
                                        :default-value="props.patient?.emergency_contact ?? ''"
                                        placeholder="Nome completo do contato"
                                    />
                                    <InputError class="mt-2" :message="errors.emergency_contact" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="emergency_phone">Telefone de Emergência</Label>
                                    <Input
                                        id="emergency_phone"
                                        name="emergency_phone"
                                        type="tel"
                                        :default-value="props.patient?.emergency_phone ?? ''"
                                        placeholder="(00) 00000-0000"
                                    />
                                    <InputError class="mt-2" :message="errors.emergency_phone" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações Médicas -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Informações Médicas</h3>
                            
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label for="medical_history">Histórico Médico</Label>
                                    <Textarea
                                        id="medical_history"
                                        name="medical_history"
                                        :default-value="props.patient?.medical_history ?? ''"
                                        placeholder="Descreva seu histórico médico, cirurgias, condições crônicas, etc."
                                        :rows="4"
                                    />
                                    <InputError class="mt-2" :message="errors.medical_history" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="allergies">Alergias</Label>
                                    <Textarea
                                        id="allergies"
                                        name="allergies"
                                        :default-value="props.patient?.allergies ?? ''"
                                        placeholder="Liste suas alergias (medicamentos, alimentos, etc.)"
                                        :rows="3"
                                    />
                                    <InputError class="mt-2" :message="errors.allergies" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="current_medications">Medicamentos em Uso</Label>
                                    <Textarea
                                        id="current_medications"
                                        name="current_medications"
                                        :default-value="props.patient?.current_medications ?? ''"
                                        placeholder="Liste os medicamentos que você está tomando atualmente"
                                        :rows="3"
                                    />
                                    <InputError class="mt-2" :message="errors.current_medications" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações Físicas e Tipo Sanguíneo -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Informações Físicas</h3>
                            
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label for="blood_type">Tipo Sanguíneo</Label>
                                    <Select
                                        id="blood_type"
                                        name="blood_type"
                                        :default-value="props.patient?.blood_type ?? ''"
                                    >
                                        <option value="">Selecione...</option>
                                        <option v-for="bloodType in props.bloodTypes" :key="bloodType" :value="bloodType">
                                            {{ bloodType }}
                                        </option>
                                    </Select>
                                    <InputError class="mt-2" :message="errors.blood_type" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="height">Altura (cm)</Label>
                                    <Input
                                        id="height"
                                        name="height"
                                        type="number"
                                        step="0.01"
                                        min="50"
                                        max="250"
                                        :default-value="props.patient?.height ?? ''"
                                        placeholder="Ex: 175"
                                    />
                                    <InputError class="mt-2" :message="errors.height" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="weight">Peso (kg)</Label>
                                    <Input
                                        id="weight"
                                        name="weight"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max="500"
                                        :default-value="props.patient?.weight ?? ''"
                                        placeholder="Ex: 70.5"
                                    />
                                    <InputError class="mt-2" :message="errors.weight" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Plano de Saúde -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Plano de Saúde</h3>
                            
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="insurance_provider">Operadora do Plano</Label>
                                    <Input
                                        id="insurance_provider"
                                        name="insurance_provider"
                                        :default-value="props.patient?.insurance_provider ?? ''"
                                        placeholder="Nome da operadora"
                                    />
                                    <InputError class="mt-2" :message="errors.insurance_provider" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="insurance_number">Número do Plano</Label>
                                    <Input
                                        id="insurance_number"
                                        name="insurance_number"
                                        :default-value="props.patient?.insurance_number ?? ''"
                                        placeholder="Número da carteirinha"
                                    />
                                    <InputError class="mt-2" :message="errors.insurance_number" />
                                </div>
                            </div>
                        </div>

                        <!-- Consentimento para Telemedicina -->
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center">
                                <input
                                    type="hidden"
                                    name="consent_telemedicine"
                                    :value="consentTelemedicineValue ? '1' : '0'"
                                />
                                <Checkbox
                                    id="consent_telemedicine"
                                    :checked="consentTelemedicineValue"
                                    @update:checked="(checked) => consentTelemedicineValue = checked"
                                />
                            </div>
                            <div class="grid gap-1">
                                <Label for="consent_telemedicine" class="cursor-pointer font-medium">
                                    Consentimento para Telemedicina
                                </Label>
                                <p class="text-xs text-gray-600">
                                    Autorizo a realização de consultas médicas por meio de telemedicina, conforme a legislação vigente.
                                </p>
                                <InputError class="mt-2" :message="errors.consent_telemedicine" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Salvar</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Salvo.</p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
