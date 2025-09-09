<script setup lang="ts">
import { ref } from 'vue';
import { useDoctorRegistration } from '@/composables/useDoctorRegistration';
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
  specialties,
  updateField,
  touchField,
  submitForm,
  getFieldError,
  hasFieldError,
  isFieldTouched
} = useDoctorRegistration();

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
        description="Conecte-se com pacientes e ofereça cuidados médicos de qualidade.">

        <Head title="Registro de Médico" />

        <!-- Background decorativo moderno -->
        <BackgroundDecorativo 
            variant="doctor" 
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
                            class="bg-white/95 backdrop-blur-lg rounded-2xl lg:rounded-3xl p-3 lg:p-4 shadow-2xl border border-white/40 hover:shadow-3xl transition-all duration-500 relative z-20">
                            <!-- Header com ícone -->
                            <div class="text-center">
                                <div
                                    class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-primary/20 to-primary/10 rounded-xl mb-2 shadow-lg">
                                    <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                                <h1
                                    class="text-lg lg:text-xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                                    Registro de Médico</h1>
                                <p class="text-xs text-gray-500 mt-1 font-medium">Junte-se à nossa plataforma médica</p>
                            </div>

                            <!-- Mensagem de sucesso -->
                            <div v-if="showSuccessMessage" 
                                class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"
                                role="alert"
                                aria-live="polite">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Conta criada com sucesso! Redirecionando...
                                </div>
                            </div>

                            <!-- Mensagem de erro de rate limit -->
                            <div v-if="submitError" 
                                class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"
                                role="alert"
                                aria-live="assertive">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
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
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                        Nome completo
                                    </Label>
                                    <div class="relative">
                                        <Input id="name" type="text" required autofocus :tabindex="1"
                                            autocomplete="name" name="name" placeholder="Dr. Seu Nome Completo"
                                            class="h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('name') && isFieldTouched('name')"
                                        :message="getFieldError('name')" 
                                        id="name-error"
                                    />
                                </div>

                                <!-- Campos CRM e Especialidade lado a lado -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 lg:gap-3">
                                    <!-- Campo CRM -->
                                    <div class="space-y-0.5">
                                        <Label for="crm" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                            <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            CRM
                                        </Label>
                                        <div class="relative">
                                            <Input id="crm" type="text" required :tabindex="2"
                                                autocomplete="off" name="crm" placeholder="Ex: 123456-SP"
                                                class="h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                        </div>
                                        <InputError 
                                            v-if="hasFieldError('crm') && isFieldTouched('crm')"
                                            :message="getFieldError('crm')" 
                                            id="crm-error"
                                        />
                                    </div>

                                    <!-- Campo Especialidade -->
                                    <div class="space-y-0.5">
                                        <Label for="specialty" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                            <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                                </path>
                                            </svg>
                                            Especialidade
                                        </Label>
                                        <div class="relative">
                                            <select 
                                                id="specialty" 
                                                name="specialty" 
                                                required 
                                                :tabindex="3"
                                                :value="formData.specialty"
                                                @change="updateField('specialty', ($event.target as HTMLSelectElement).value)"
                                                @blur="touchField('specialty')"
                                                :class="[
                                                    'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm text-gray-700 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md w-full',
                                                    hasFieldError('specialty') && isFieldTouched('specialty') 
                                                        ? 'border-red-500 focus:border-red-500' 
                                                        : 'border-gray-200/50 focus:border-primary'
                                                ]"
                                                :aria-invalid="hasFieldError('specialty') && isFieldTouched('specialty')"
                                                :aria-describedby="hasFieldError('specialty') && isFieldTouched('specialty') ? 'specialty-error' : undefined"
                                            >
                                                <option value="">Selecione sua especialidade</option>
                                                <option v-for="specialty in specialties" :key="specialty" :value="specialty.toLowerCase()">
                                                    {{ specialty }}
                                                </option>
                                            </select>
                                        </div>
                                        <InputError 
                                            v-if="hasFieldError('specialty') && isFieldTouched('specialty')"
                                            :message="getFieldError('specialty')" 
                                            id="specialty-error"
                                        />
                                    </div>
                                </div>

                                <!-- Campo Email -->
                                <div class="space-y-0.5">
                                    <Label for="email" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        E-mail Profissional
                                    </Label>
                                    <div class="relative">
                                        <Input id="email" type="email" required :tabindex="4" autocomplete="email"
                                            name="email" placeholder="seu@email.com"
                                            class="h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('email') && isFieldTouched('email')"
                                        :message="getFieldError('email')" 
                                        id="email-error"
                                    />
                                </div>

                                <!-- Campo Senha -->
                                <div class="space-y-0.5">
                                    <Label for="password"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                            </path>
                                        </svg>
                                        Senha
                                    </Label>
                                    <div class="relative">
                                        <Input id="password" type="password" required :tabindex="5"
                                            autocomplete="new-password" name="password"
                                            placeholder="Mínimo 8 caracteres"
                                            class="h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
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
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Confirmar senha
                                    </Label>
                                    <div class="relative">
                                        <Input id="password_confirmation" type="password" required :tabindex="6"
                                            autocomplete="new-password" name="password_confirmation"
                                            placeholder="Digite a senha novamente"
                                            class="h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')"
                                        :message="getFieldError('password_confirmation')" 
                                        id="password_confirmation-error"
                                    />
                                </div>

                                <!-- Termos de Serviço e LGPD -->
                                <div class="space-y-2">
                                    <div class="flex items-start gap-3">
                                        <input 
                                            id="terms_accepted" 
                                            type="checkbox" 
                                            required 
                                            :tabindex="7"
                                            v-model="formData.terms_accepted"
                                            class="mt-1 h-4 w-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2"
                                            :aria-invalid="hasFieldError('terms_accepted') && isFieldTouched('terms_accepted')"
                                            :aria-describedby="hasFieldError('terms_accepted') && isFieldTouched('terms_accepted') ? 'terms_accepted-error' : undefined"
                                        />
                                        <div class="text-xs text-gray-600 leading-relaxed">
                                            <label for="terms_accepted" class="cursor-pointer">
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
                                                    Seus dados serão utilizados exclusivamente para prestação de serviços médicos e comunicação relacionada ao atendimento.
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <InputError 
                                        v-if="hasFieldError('terms_accepted') && isFieldTouched('terms_accepted')"
                                        :message="getFieldError('terms_accepted')" 
                                        id="terms_accepted-error"
                                    />
                                </div>

                                <!-- Botão de Registro -->
                                <div class="">
                                    <Button 
                                        type="submit"
                                        :disabled="!canSubmit"
                                        :tabindex="8"
                                        class="w-full h-9 lg:h-11 bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary text-black text-sm font-bold rounded-xl lg:rounded-2xl shadow-xl hover:shadow-2xl transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border-2 border-primary/20 hover:border-primary/30"
                                        :aria-describedby="rateLimit.remainingAttempts < 2 ? 'rate-limit-warning' : undefined"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <LoaderCircle v-if="isSubmitting" class="w-4 h-4 animate-spin" aria-hidden="true" />
                                            <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <span class="font-bold">
                                                {{ isSubmitting ? 'Registrando médico...' : 'Registrar como Médico' }}
                                            </span>
                                        </div>
                                    </Button>
                                    
                                    <!-- Aviso de rate limit -->
                                    <div v-if="rateLimit.remainingAttempts < 2 && rateLimit.remainingAttempts > 0" 
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
                            class="absolute inset-0 bg-gradient-to-br from-primary/8 via-transparent to-primary/12 rounded-full blur-3xl">
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
                            <img src="/storage/photos/doctordoodle.png" alt="Doctor Doodle"
                                class="w-[420px] h-[420px] object-contain drop-shadow-2xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link para login -->
        <div class="text-center mt-6 lg:mt-1 relative z-10">
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
                        :tabindex="8">
                        Faça login aqui
                    </TextLink>
                </p>
            </div>
        </div>
    </AuthBase>
</template>
