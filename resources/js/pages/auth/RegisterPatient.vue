<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePatientRegistration } from '@/composables/usePatientRegistration';
import BackgroundDecorativo from '@/components/BackgroundDecorativo.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

// Composables
const {
  formData,
  isSubmitting,
  hasErrors,
  canSubmit,
  submitError,
  rateLimit,
  updateField,
  touchField,
  submitForm,
  getFieldError,
  hasFieldError,
  isFieldTouched
} = usePatientRegistration();

// Estado local
const showSuccessMessage = ref(false);

// Função para lidar com submissão
const handleSubmit = async () => {
  const success = await submitForm();
  if (success) {
    showSuccessMessage.value = true;
    // Redirecionar após sucesso
    setTimeout(() => {
      window.location.href = '/dashboard';
    }, 2000);
  }
};
</script>

<template>
    <AuthBase title="Seja bem-vindo a Telemedicina para Todos"
        description="Sua jornada de saúde começa aqui. Registre-se para começar.">

        <Head title="Registro de Paciente" />

        <!-- Background decorativo moderno -->
        <BackgroundDecorativo 
            variant="patient" 
            intensity="medium" 
            :enable-animations="true" 
        />

        <!-- Container principal com layout responsivo -->
        <div class="relative w-full max-w-8xl mx-auto z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8 lg:gap-20">

                <!-- Formulário -->
                <div class="w-full lg:w-200 border rounded-3xl">
                    <div>
                        <!-- Card do formulário -->
                        <div
                            class="bg-white/95 backdrop-blur-lg rounded-2xl lg:rounded-3xl p-2 lg:p-3 shadow-2xl border border-white/40 hover:shadow-3xl transition-all duration-500 relative z-20">
                            <!-- Header com ícone -->
                            <div class="text-center">
                                <div
                                    class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-primary/20 to-primary/10 rounded-lg mb-1 shadow-lg">
                                    <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <h1
                                    class="text-base lg:text-lg font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                                    Registro de Paciente</h1>
                                <p class="text-xs text-gray-500 font-medium">Junte-se à nossa comunidade
                                    médica</p>
                            </div>

                            <!-- Mensagem de sucesso -->
                            <div v-if="showSuccessMessage" 
                                class="mb-2 p-2 bg-green-100 border border-green-400 text-green-700 rounded-lg"
                                role="alert"
                                aria-live="polite">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Conta criada com sucesso! Redirecionando...
                                </div>
                            </div>

                            <!-- Mensagem de erro de rate limit -->
                            <div v-if="submitError" 
                                class="mb-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg"
                                role="alert"
                                aria-live="assertive">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ submitError }}
                                </div>
                            </div>

                            <form @submit.prevent="handleSubmit" class="space-y-2 lg:space-y-3">

                                <!-- Campo Nome -->
                                <div class="space-y-0.5">
                                    <Label for="name" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                        Nome completo
                                    </Label>
                                    <div class="relative">
                                        <Input 
                                            id="name" 
                                            type="text" 
                                            required 
                                            autofocus 
                                            :tabindex="1"
                                            autocomplete="name" 
                                            name="name" 
                                            placeholder="Digite seu nome completo"
                                            :value="formData.name"
                                            @input="updateField('name', ($event.target as HTMLInputElement).value)"
                                            @blur="touchField('name')"
                                            :class="[
                                                'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                hasFieldError('name') && isFieldTouched('name') 
                                                    ? 'border-red-500 focus:border-red-500' 
                                                    : 'border-gray-200/50 focus:border-primary'
                                            ]"
                                            :aria-invalid="hasFieldError('name') && isFieldTouched('name')"
                                            :aria-describedby="hasFieldError('name') && isFieldTouched('name') ? 'name-error' : undefined"
                                        />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('name') && isFieldTouched('name')"
                                        :message="getFieldError('name')" 
                                        id="name-error"
                                    />
                                </div>

                                <!-- Campo Email -->
                                <div class="space-y-0.5">
                                    <Label for="email" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        E-mail
                                    </Label>
                                    <div class="relative">
                                        <Input 
                                            id="email" 
                                            type="email" 
                                            required 
                                            :tabindex="2" 
                                            autocomplete="email"
                                            name="email" 
                                            placeholder="seu@email.com"
                                            :value="formData.email"
                                            @input="updateField('email', ($event.target as HTMLInputElement).value)"
                                            @blur="touchField('email')"
                                            :class="[
                                                'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                hasFieldError('email') && isFieldTouched('email') 
                                                    ? 'border-red-500 focus:border-red-500' 
                                                    : 'border-gray-200/50 focus:border-primary'
                                            ]"
                                            :aria-invalid="hasFieldError('email') && isFieldTouched('email')"
                                            :aria-describedby="hasFieldError('email') && isFieldTouched('email') ? 'email-error' : undefined"
                                        />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('email') && isFieldTouched('email')"
                                        :message="getFieldError('email')" 
                                        id="email-error"
                                    />
                                </div>

                                <!-- Campo Data de Nascimento -->
                                <div class="space-y-0.5">
                                    <Label for="date_of_birth" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        Data de Nascimento
                                    </Label>
                                    <div class="relative">
                                        <Input 
                                            id="date_of_birth" 
                                            type="date" 
                                            required 
                                            :tabindex="3"
                                            name="date_of_birth" 
                                            :value="formData.date_of_birth"
                                            @input="updateField('date_of_birth', ($event.target as HTMLInputElement).value)"
                                            @blur="touchField('date_of_birth')"
                                            :class="[
                                                'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                hasFieldError('date_of_birth') && isFieldTouched('date_of_birth') 
                                                    ? 'border-red-500 focus:border-red-500' 
                                                    : 'border-gray-200/50 focus:border-primary'
                                            ]"
                                            :aria-invalid="hasFieldError('date_of_birth') && isFieldTouched('date_of_birth')"
                                            :aria-describedby="hasFieldError('date_of_birth') && isFieldTouched('date_of_birth') ? 'date_of_birth-error' : undefined"
                                        />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('date_of_birth') && isFieldTouched('date_of_birth')"
                                        :message="getFieldError('date_of_birth')" 
                                        id="date_of_birth-error"
                                    />
                                </div>

                                <!-- Campos de Emergência lado a lado -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 lg:gap-3">
                                    <!-- Campo Contato de Emergência -->
                                    <div class="space-y-0.5">
                                        <Label for="emergency_contact" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                            <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            Contato de Emergência
                                        </Label>
                                        <div class="relative">
                                            <Input 
                                                id="emergency_contact" 
                                                type="text" 
                                                required 
                                                :tabindex="4"
                                                name="emergency_contact" 
                                                placeholder="Nome do contato"
                                                :value="formData.emergency_contact"
                                                @input="updateField('emergency_contact', ($event.target as HTMLInputElement).value)"
                                                @blur="touchField('emergency_contact')"
                                                :class="[
                                                    'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                    hasFieldError('emergency_contact') && isFieldTouched('emergency_contact') 
                                                        ? 'border-red-500 focus:border-red-500' 
                                                        : 'border-gray-200/50 focus:border-primary'
                                                ]"
                                                :aria-invalid="hasFieldError('emergency_contact') && isFieldTouched('emergency_contact')"
                                                :aria-describedby="hasFieldError('emergency_contact') && isFieldTouched('emergency_contact') ? 'emergency_contact-error' : undefined"
                                            />
                                        </div>
                                        <InputError 
                                            v-if="hasFieldError('emergency_contact') && isFieldTouched('emergency_contact')"
                                            :message="getFieldError('emergency_contact')" 
                                            id="emergency_contact-error"
                                        />
                                    </div>

                                    <!-- Campo Telefone de Emergência -->
                                    <div class="space-y-0.5">
                                        <Label for="emergency_phone" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                            <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                                </path>
                                            </svg>
                                            Telefone de Emergência
                                        </Label>
                                        <div class="relative">
                                            <Input 
                                                id="emergency_phone" 
                                                type="tel" 
                                                required 
                                                :tabindex="5"
                                                name="emergency_phone" 
                                                placeholder="(11) 99999-9999"
                                                :value="formData.emergency_phone"
                                                @input="updateField('emergency_phone', ($event.target as HTMLInputElement).value)"
                                                @blur="touchField('emergency_phone')"
                                                :class="[
                                                    'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                    hasFieldError('emergency_phone') && isFieldTouched('emergency_phone') 
                                                        ? 'border-red-500 focus:border-red-500' 
                                                        : 'border-gray-200/50 focus:border-primary'
                                                ]"
                                                :aria-invalid="hasFieldError('emergency_phone') && isFieldTouched('emergency_phone')"
                                                :aria-describedby="hasFieldError('emergency_phone') && isFieldTouched('emergency_phone') ? 'emergency_phone-error' : undefined"
                                            />
                                        </div>
                                        <InputError 
                                            v-if="hasFieldError('emergency_phone') && isFieldTouched('emergency_phone')"
                                            :message="getFieldError('emergency_phone')" 
                                            id="emergency_phone-error"
                                        />
                                    </div>
                                </div>

                                <!-- Campo Senha -->
                                <div class="space-y-0.5">
                                    <Label for="password"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                            </path>
                                        </svg>
                                        Senha
                                    </Label>
                                    <div class="relative">
                                        <Input 
                                            id="password" 
                                            type="password" 
                                            required 
                                            :tabindex="6"
                                            autocomplete="new-password" 
                                            name="password"
                                            placeholder="Mínimo 8 caracteres"
                                            :value="formData.password"
                                            @input="updateField('password', ($event.target as HTMLInputElement).value)"
                                            @blur="touchField('password')"
                                            :class="[
                                                'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                hasFieldError('password') && isFieldTouched('password') 
                                                    ? 'border-red-500 focus:border-red-500' 
                                                    : 'border-gray-200/50 focus:border-primary'
                                            ]"
                                            :aria-invalid="hasFieldError('password') && isFieldTouched('password')"
                                            :aria-describedby="hasFieldError('password') && isFieldTouched('password') ? 'password-error' : undefined"
                                        />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('password') && isFieldTouched('password')"
                                        :message="getFieldError('password')" 
                                        id="password-error"
                                    />
                                </div>

                                <!-- Campo Confirmar Senha -->
                                <div class="space-y-0.5">
                                    <Label for="password_confirmation"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Confirmar senha
                                    </Label>
                                    <div class="relative">
                                        <Input 
                                            id="password_confirmation" 
                                            type="password" 
                                            required 
                                            :tabindex="7"
                                            autocomplete="new-password" 
                                            name="password_confirmation"
                                            placeholder="Digite a senha novamente"
                                            :value="formData.password_confirmation"
                                            @input="updateField('password_confirmation', ($event.target as HTMLInputElement).value)"
                                            @blur="touchField('password_confirmation')"
                                            :class="[
                                                'h-9 lg:h-10 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-lg lg:rounded-xl px-3 lg:px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                hasFieldError('password_confirmation') && isFieldTouched('password_confirmation') 
                                                    ? 'border-red-500 focus:border-red-500' 
                                                    : 'border-gray-200/50 focus:border-primary'
                                            ]"
                                            :aria-invalid="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')"
                                            :aria-describedby="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation') ? 'password_confirmation-error' : undefined"
                                        />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')"
                                        :message="getFieldError('password_confirmation')" 
                                        id="password_confirmation-error"
                                    />
                                </div>

                                <!-- Checkbox Consentimento Telemedicina -->
                                <div class="space-y-0.5">
                                    <div class="flex items-start gap-3">
                                        <input 
                                            id="consent_telemedicine" 
                                            type="checkbox" 
                                            required 
                                            :tabindex="8"
                                            :checked="formData.consent_telemedicine"
                                            @change="updateField('consent_telemedicine', ($event.target as HTMLInputElement).checked)"
                                            @blur="touchField('consent_telemedicine')"
                                            :class="[
                                                'mt-1 h-4 w-4 rounded border-2 focus:ring-2 focus:ring-primary',
                                                hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine') 
                                                    ? 'border-red-500' 
                                                    : 'border-gray-300'
                                            ]"
                                            :aria-invalid="hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine')"
                                            :aria-describedby="hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine') ? 'consent_telemedicine-error' : undefined"
                                        />
                                        <div class="text-xs text-gray-600 leading-relaxed">
                                            <label for="consent_telemedicine" class="cursor-pointer">
                                                Concordo com os 
                                                <a href="/terms" target="_blank" class="text-primary hover:text-primary/80 underline underline-offset-2 hover:underline-offset-4 transition-all duration-300">
                                                    Termos de Serviço
                                                </a> 
                                                e 
                                                <a href="/privacy" target="_blank" class="text-primary hover:text-primary/80 underline underline-offset-2 hover:underline-offset-4 transition-all duration-300">
                                                    Política de Privacidade
                                                </a>
                                                , incluindo o tratamento de dados pessoais conforme a 
                                                <strong class="text-gray-700">Lei Geral de Proteção de Dados (LGPD)</strong>.
                                                <br>
                                                <span class="text-gray-500 mt-1 block">
                                                    Autorizo o uso da telemedicina e o tratamento dos meus dados exclusivamente para prestação de serviços médicos.
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine')"
                                        :message="getFieldError('consent_telemedicine')" 
                                        id="consent_telemedicine-error"
                                    />
                                </div>

                                <!-- Botão de Registro -->
                                <div class="">
                                    <Button 
                                        type="submit"
                                        :disabled="!canSubmit"
                                        :tabindex="9"
                                        class="w-full h-9 lg:h-10 bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary text-black text-sm font-bold rounded-lg lg:rounded-xl shadow-xl hover:shadow-2xl transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border-2 border-primary/20 hover:border-primary/30"
                                        :aria-describedby="rateLimit.remainingAttempts < 3 ? 'rate-limit-warning' : undefined"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <LoaderCircle v-if="isSubmitting" class="w-4 h-4 animate-spin" aria-hidden="true" />
                                            <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span class="font-bold">
                                                {{ isSubmitting ? 'Criando conta...' : 'Criar conta' }}
                                            </span>
                                        </div>
                                    </Button>
                                    
                                    <!-- Aviso de rate limit -->
                                    <div v-if="rateLimit.remainingAttempts < 3 && rateLimit.remainingAttempts > 0" 
                                        id="rate-limit-warning"
                                        class="mt-2 text-sm text-orange-600 text-center"
                                        role="alert"
                                        aria-live="polite">
                                        ⚠️ Restam {{ rateLimit.remainingAttempts }} tentativa{{ rateLimit.remainingAttempts > 1 ? 's' : '' }}
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Imagem central - apenas em desktop -->
                <div class="hidden lg:flex relative w-[400px] h-[400px] items-center justify-center">
                    <!-- Container principal com gradiente de fundo sutil -->
                    <div class="relative w-full h-full flex items-center justify-center">
                        <!-- Gradiente de fundo sutil -->
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-primary/10 rounded-full blur-3xl">
                        </div>

                        <!-- Bolas decorativas estáticas -->
                        <div class="absolute top-8 left-8 w-16 h-16 bg-primary/20 rounded-full shadow-lg"></div>
                        <div class="absolute top-16 left-16 w-12 h-12 bg-primary/15 rounded-full shadow-md"></div>

                        <div class="absolute bottom-12 right-12 w-20 h-20 bg-primary/18 rounded-full shadow-lg"></div>
                        <div class="absolute bottom-20 right-20 w-14 h-14 bg-primary/12 rounded-full shadow-md"></div>

                        <div class="absolute top-1/4 right-8 w-10 h-10 bg-primary/25 rounded-full shadow-md"></div>
                        <div class="absolute bottom-1/4 left-8 w-8 h-8 bg-primary/20 rounded-full shadow-sm"></div>

                        <div class="absolute top-1/2 left-4 w-6 h-6 bg-primary/30 rounded-full shadow-sm"></div>
                        <div class="absolute top-1/2 right-4 w-7 h-7 bg-primary/22 rounded-full shadow-sm"></div>

                        <!-- Imagem central -->
                        <div class="relative z-10 flex items-center justify-center">
                            <img src="/storage/photos/patientdoodle.png" alt="Doctor Doodle"
                                class="w-[420px] h-[420px] object-contain drop-shadow-2xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link para login -->
        <div class="text-center relative z-10">
            <div
                class="inline-flex items-center gap-1 bg-white/80 backdrop-blur-md rounded-lg lg:rounded-xl px-2 lg:px-3 py-1 lg:py-2 shadow-lg border border-white/30">
                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg>
                <p class="text-gray-600 text-xs lg:text-sm font-medium">
                    Já tem uma conta?
                    <TextLink :href="login()"
                        class="text-black hover:text-black/80 font-bold underline underline-offset-4 hover:underline-offset-2 transition-all duration-300 ml-1"
                        :tabindex="6">
                        Faça login aqui
                    </TextLink>
                </p>
            </div>
        </div>
    </AuthBase>
</template>
