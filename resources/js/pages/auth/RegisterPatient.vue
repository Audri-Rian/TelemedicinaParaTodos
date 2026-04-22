<script setup lang="ts">
import BackgroundDecorativo from '@/components/BackgroundDecorativo.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePatientRegistration } from '@/composables/Patient/usePatientRegistration';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

// Composables
const {
    formData,
    isSubmitting,
    hasErrors,
    submitError,
    rateLimit,
    updateField,
    touchField,
    submitForm,
    getFieldError,
    hasFieldError,
    isFieldTouched,
} = usePatientRegistration();

const fieldLabels: Record<string, string> = {
    name: 'nome completo',
    date_of_birth: 'data de nascimento',
    email: 'e-mail',
    phone_number: 'telefone',
    gender: 'gênero',
    password: 'senha',
    password_confirmation: 'confirmação de senha',
    consent_telemedicine: 'termos de telemedicina',
};

const showValidationNotice = computed(() => hasErrors.value && !submitError.value && !isSubmitting.value);

const getFriendlyFieldError = (fieldName: keyof typeof fieldLabels): string => {
    const rawMessage = getFieldError(fieldName as any);
    if (!rawMessage) return '';

    const label = fieldLabels[fieldName] || 'campo';
    const normalized = rawMessage.toLowerCase();

    if (normalized.includes('é obrigatório')) return `Informe ${label}.`;
    if (normalized.includes('email válido')) return 'Digite um e-mail no formato nome@dominio.com.';
    if (normalized.includes('formato dd/mm/aaaa')) return 'Use o formato dd/mm/aaaa para a data de nascimento.';
    if (normalized.includes('não pode ser no futuro')) return 'Informe uma data de nascimento anterior à data de hoje.';
    if (normalized.includes('selecione um gênero válido')) return 'Selecione uma opção de gênero para continuar.';
    if (normalized.includes('deve ser igual')) return 'A confirmação precisa ser igual à senha informada.';
    if (normalized.includes('termos de telemedicina')) return 'Para seguir, aceite os Termos de Serviço e a Política de Privacidade.';

    return rawMessage;
};

// Função para aplicar máscara de data brasileira
const applyDateMask = (value: string) => {
    // Remove tudo que não é número
    const numbers = value.replace(/\D/g, '');

    // Aplica a máscara dd/mm/aaaa
    if (numbers.length <= 2) {
        return numbers;
    } else if (numbers.length <= 4) {
        return `${numbers.slice(0, 2)}/${numbers.slice(2)}`;
    } else {
        return `${numbers.slice(0, 2)}/${numbers.slice(2, 4)}/${numbers.slice(4, 8)}`;
    }
};

// Função para lidar com entrada de data
const handleDateInput = (value: string | number) => {
    const stringValue = String(value);
    const maskedValue = applyDateMask(stringValue);
    updateField('date_of_birth', maskedValue);
};

// Função para lidar com submissão
const handleSubmit = async () => {
    await submitForm();
    // O redirecionamento é feito automaticamente pelo Inertia.js
    // Não precisa mais de setTimeout manual
};
</script>

<template>
    <AuthBase title="" description="">
        <Head title="Registro de Paciente" />
        <BackgroundDecorativo variant="patient" intensity="low" :enable-animations="false" />

        <div class="relative z-10 mx-auto w-full max-w-7xl space-y-5">
            <Transition name="fade-slide">
                <div
                    v-if="isSubmitting"
                    class="mx-auto flex w-full max-w-4xl items-center justify-center rounded-full bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-lg"
                >
                    Criando conta...
                </div>
            </Transition>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
                <aside class="animate-fade-up hidden space-y-5 lg:block">
                    <div class="space-y-4">
                        <h1 class="text-5xl leading-[0.95] font-extrabold text-teal-700">Comece sua jornada de cuidado.</h1>
                        <p class="text-xl leading-relaxed text-gray-600">
                            Junte-se ao Sanctuary Health para uma experiência de saúde personalizada, humana e de alta tecnologia.
                        </p>
                    </div>
                    <div class="flex h-72 items-center justify-center rounded-[32px] border-2 border-dashed border-gray-300 bg-white/70 shadow-lg">
                        <p class="text-lg font-semibold text-gray-500">Imagem pendente</p>
                    </div>
                </aside>

                <section class="space-y-5">
                    <div class="animate-fade-up-delayed rounded-[34px] bg-white/90 p-6 shadow-xl ring-1 ring-gray-200/60 backdrop-blur">
                        <div class="mb-5">
                            <h2 class="text-4xl font-bold text-gray-800">Dados do Paciente</h2>
                            <p class="mt-1 text-lg text-gray-500">Preencha as informações abaixo para criar seu prontuário digital.</p>
                        </div>

                        <Transition name="fade-slide">
                            <div
                                v-if="submitError"
                                class="mb-4 rounded-xl border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700"
                                role="alert"
                                aria-live="assertive"
                            >
                                {{ submitError }}
                            </div>
                        </Transition>

                        <Transition name="fade-slide">
                            <div
                                v-if="showValidationNotice"
                                class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800"
                                role="status"
                                aria-live="polite"
                            >
                                Revise os campos destacados para continuar.
                            </div>
                        </Transition>

                        <form @submit.prevent="handleSubmit" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label for="name" class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase"
                                        >Nome Completo</Label
                                    >
                                    <Input
                                        id="name"
                                        type="text"
                                        required
                                        autofocus
                                        :tabindex="1"
                                        autocomplete="name"
                                        name="name"
                                        placeholder="Ana Beatriz Silva"
                                        :model-value="formData.name"
                                        @update:model-value="updateField('name', $event)"
                                        @blur="touchField('name')"
                                        :class="[
                                            'h-11 rounded-lg border-0 bg-gray-100 px-4 text-sm shadow-inner transition-all duration-200 placeholder:text-gray-400 focus-visible:ring-2 focus-visible:ring-teal-500',
                                            hasFieldError('name') && isFieldTouched('name') ? 'bg-red-50/70 ring-2 ring-red-300' : '',
                                        ]"
                                        :aria-invalid="hasFieldError('name') && isFieldTouched('name')"
                                        :aria-describedby="hasFieldError('name') && isFieldTouched('name') ? 'name-error' : undefined"
                                    />
                                    <Transition name="fade-slide">
                                        <p
                                            v-if="hasFieldError('name') && isFieldTouched('name')"
                                            id="name-error"
                                            class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                        >
                                            <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                                >error_outline</span
                                            >{{ getFriendlyFieldError('name') }}
                                        </p>
                                    </Transition>
                                </div>

                                <div>
                                    <Label for="date_of_birth" class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase"
                                        >Data de Nascimento</Label
                                    >
                                    <Input
                                        id="date_of_birth"
                                        type="text"
                                        required
                                        :tabindex="2"
                                        name="date_of_birth"
                                        placeholder="12/05/1992"
                                        maxlength="10"
                                        :model-value="formData.date_of_birth"
                                        @update:model-value="handleDateInput"
                                        @blur="touchField('date_of_birth')"
                                        :class="[
                                            'h-11 rounded-lg border-0 bg-gray-100 px-4 text-sm shadow-inner transition-all duration-200 placeholder:text-gray-400 focus-visible:ring-2 focus-visible:ring-teal-500',
                                            hasFieldError('date_of_birth') && isFieldTouched('date_of_birth')
                                                ? 'bg-red-50/70 ring-2 ring-red-300'
                                                : '',
                                        ]"
                                        :aria-invalid="hasFieldError('date_of_birth') && isFieldTouched('date_of_birth')"
                                        :aria-describedby="
                                            hasFieldError('date_of_birth') && isFieldTouched('date_of_birth') ? 'date_of_birth-error' : undefined
                                        "
                                    />
                                    <Transition name="fade-slide">
                                        <p
                                            v-if="hasFieldError('date_of_birth') && isFieldTouched('date_of_birth')"
                                            id="date_of_birth-error"
                                            class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                        >
                                            <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                                >error_outline</span
                                            >{{ getFriendlyFieldError('date_of_birth') }}
                                        </p>
                                    </Transition>
                                </div>
                            </div>

                            <div>
                                <Label for="email" class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    :tabindex="3"
                                    autocomplete="email"
                                    name="email"
                                    placeholder="ana.beatriz@email.com"
                                    :model-value="formData.email"
                                    @update:model-value="updateField('email', $event)"
                                    @blur="touchField('email')"
                                    :class="[
                                        'h-11 rounded-lg border-0 bg-gray-100 px-4 text-sm shadow-inner transition-all duration-200 placeholder:text-gray-400 focus-visible:ring-2 focus-visible:ring-teal-500',
                                        hasFieldError('email') && isFieldTouched('email') ? 'bg-red-50/70 ring-2 ring-red-300' : '',
                                    ]"
                                    :aria-invalid="hasFieldError('email') && isFieldTouched('email')"
                                    :aria-describedby="hasFieldError('email') && isFieldTouched('email') ? 'email-error' : undefined"
                                />
                                <Transition name="fade-slide">
                                    <p
                                        v-if="hasFieldError('email') && isFieldTouched('email')"
                                        id="email-error"
                                        class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                    >
                                        <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                            >error_outline</span
                                        >{{ getFriendlyFieldError('email') }}
                                    </p>
                                </Transition>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label for="phone_number" class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase"
                                        >Telefone</Label
                                    >
                                    <Input
                                        id="phone_number"
                                        type="tel"
                                        required
                                        :tabindex="4"
                                        name="phone_number"
                                        placeholder="(11) 98765-4321"
                                        :model-value="formData.phone_number"
                                        @update:model-value="updateField('phone_number', $event)"
                                        @blur="touchField('phone_number')"
                                        :class="[
                                            'h-11 rounded-lg border-0 bg-gray-100 px-4 text-sm shadow-inner transition-all duration-200 placeholder:text-gray-400 focus-visible:ring-2 focus-visible:ring-teal-500',
                                            hasFieldError('phone_number') && isFieldTouched('phone_number') ? 'bg-red-50/70 ring-2 ring-red-300' : '',
                                        ]"
                                        :aria-invalid="hasFieldError('phone_number') && isFieldTouched('phone_number')"
                                        :aria-describedby="
                                            hasFieldError('phone_number') && isFieldTouched('phone_number') ? 'phone_number-error' : undefined
                                        "
                                    />
                                    <Transition name="fade-slide">
                                        <p
                                            v-if="hasFieldError('phone_number') && isFieldTouched('phone_number')"
                                            id="phone_number-error"
                                            class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                        >
                                            <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                                >error_outline</span
                                            >{{ getFriendlyFieldError('phone_number') }}
                                        </p>
                                    </Transition>
                                </div>

                                <div>
                                    <Label for="gender" class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase"
                                        >Gênero</Label
                                    >
                                    <select
                                        id="gender"
                                        required
                                        :tabindex="5"
                                        name="gender"
                                        :value="formData.gender"
                                        @change="updateField('gender', ($event.target as HTMLSelectElement).value)"
                                        @blur="touchField('gender')"
                                        :class="[
                                            'h-11 w-full rounded-lg border-0 bg-gray-100 px-4 text-sm text-gray-700 shadow-inner transition-all duration-200 focus-visible:ring-2 focus-visible:ring-teal-500',
                                            hasFieldError('gender') && isFieldTouched('gender') ? 'bg-red-50/70 ring-2 ring-red-300' : '',
                                        ]"
                                        :aria-invalid="hasFieldError('gender') && isFieldTouched('gender')"
                                        :aria-describedby="hasFieldError('gender') && isFieldTouched('gender') ? 'gender-error' : undefined"
                                    >
                                        <option value="">Selecione</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Feminino</option>
                                        <option value="other">Outro</option>
                                    </select>
                                    <Transition name="fade-slide">
                                        <p
                                            v-if="hasFieldError('gender') && isFieldTouched('gender')"
                                            id="gender-error"
                                            class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                        >
                                            <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                                >error_outline</span
                                            >{{ getFriendlyFieldError('gender') }}
                                        </p>
                                    </Transition>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label for="password" class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase"
                                        >Senha</Label
                                    >
                                    <Input
                                        id="password"
                                        type="password"
                                        required
                                        :tabindex="6"
                                        autocomplete="new-password"
                                        name="password"
                                        placeholder="Mínimo 8 caracteres"
                                        :model-value="formData.password"
                                        @update:model-value="updateField('password', $event)"
                                        @blur="touchField('password')"
                                        :class="[
                                            'h-11 rounded-lg border-0 bg-gray-100 px-4 text-sm shadow-inner transition-all duration-200 placeholder:text-gray-400 focus-visible:ring-2 focus-visible:ring-teal-500',
                                            hasFieldError('password') && isFieldTouched('password') ? 'bg-red-50/70 ring-2 ring-red-300' : '',
                                        ]"
                                        :aria-invalid="hasFieldError('password') && isFieldTouched('password')"
                                        :aria-describedby="hasFieldError('password') && isFieldTouched('password') ? 'password-error' : undefined"
                                    />
                                    <Transition name="fade-slide">
                                        <p
                                            v-if="hasFieldError('password') && isFieldTouched('password')"
                                            id="password-error"
                                            class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                        >
                                            <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                                >error_outline</span
                                            >{{ getFriendlyFieldError('password') }}
                                        </p>
                                    </Transition>
                                </div>

                                <div>
                                    <Label
                                        for="password_confirmation"
                                        class="mb-2 block text-[11px] font-bold tracking-[0.08em] text-gray-500 uppercase"
                                        >Confirmar Senha</Label
                                    >
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        required
                                        :tabindex="7"
                                        autocomplete="new-password"
                                        name="password_confirmation"
                                        placeholder="Digite novamente"
                                        :model-value="formData.password_confirmation"
                                        @update:model-value="updateField('password_confirmation', $event)"
                                        @blur="touchField('password_confirmation')"
                                        :class="[
                                            'h-11 rounded-lg border-0 bg-gray-100 px-4 text-sm shadow-inner transition-all duration-200 placeholder:text-gray-400 focus-visible:ring-2 focus-visible:ring-teal-500',
                                            hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')
                                                ? 'bg-red-50/70 ring-2 ring-red-300'
                                                : '',
                                        ]"
                                        :aria-invalid="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')"
                                        :aria-describedby="
                                            hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')
                                                ? 'password_confirmation-error'
                                                : undefined
                                        "
                                    />
                                    <Transition name="fade-slide">
                                        <p
                                            v-if="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')"
                                            id="password_confirmation-error"
                                            class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                        >
                                            <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                                >error_outline</span
                                            >{{ getFriendlyFieldError('password_confirmation') }}
                                        </p>
                                    </Transition>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-3">
                                    <input
                                        id="consent_telemedicine"
                                        type="checkbox"
                                        required
                                        :tabindex="8"
                                        :checked="formData.consent_telemedicine"
                                        @change="updateField('consent_telemedicine', ($event.target as HTMLInputElement).checked)"
                                        @blur="touchField('consent_telemedicine')"
                                        :class="[
                                            'mt-1 h-4 w-4 rounded border border-gray-300 text-teal-700 focus:ring-teal-500',
                                            hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine')
                                                ? 'border-red-400 bg-red-50/70 ring-1 ring-red-300'
                                                : '',
                                        ]"
                                        :aria-invalid="hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine')"
                                        :aria-describedby="
                                            hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine')
                                                ? 'consent_telemedicine-error'
                                                : undefined
                                        "
                                    />
                                    <label for="consent_telemedicine" class="cursor-pointer text-sm leading-relaxed text-gray-600">
                                        Concordo com os
                                        <a href="/terms" target="_blank" class="font-semibold text-teal-700 hover:text-teal-800">Termos de Serviço</a>
                                        e
                                        <a href="/privacy" target="_blank" class="font-semibold text-teal-700 hover:text-teal-800"
                                            >Política de Privacidade</a
                                        >
                                        conforme a LGPD e autorizo o uso da telemedicina.
                                    </label>
                                </div>
                                <Transition name="fade-slide">
                                    <p
                                        v-if="hasFieldError('consent_telemedicine') && isFieldTouched('consent_telemedicine')"
                                        id="consent_telemedicine-error"
                                        class="mt-1 flex items-center gap-1 text-xs text-red-600"
                                    >
                                        <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true"
                                            >error_outline</span
                                        >{{ getFriendlyFieldError('consent_telemedicine') }}
                                    </p>
                                </Transition>
                            </div>

                            <div class="space-y-2 pt-1">
                                <!--
                                    :disabled="isSubmitting" (não !canSubmit): o clique deve sempre
                                    disparar submitForm(), que mostra toast com a razão específica
                                    da falha (rate limit, campos faltando, etc.).
                                -->
                                <Button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    :tabindex="9"
                                    class="mx-auto h-12 w-full max-w-xs rounded-full border-0 bg-teal-700 text-base font-bold text-white shadow-lg transition-all duration-300 hover:scale-[1.02] hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
                                    :aria-describedby="rateLimit.remainingAttempts < 3 ? 'rate-limit-warning' : undefined"
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <LoaderCircle v-if="isSubmitting" class="h-4 w-4 animate-spin" aria-hidden="true" />
                                        <span>{{ isSubmitting ? 'Criando conta...' : 'Criar conta' }}</span>
                                    </div>
                                </Button>

                                <div
                                    v-if="rateLimit.remainingAttempts < 3 && rateLimit.remainingAttempts > 0"
                                    id="rate-limit-warning"
                                    class="flex items-center justify-center gap-1 text-center text-sm text-orange-600"
                                    role="alert"
                                    aria-live="polite"
                                >
                                    <span class="material-icons shrink-0 text-[16px] leading-none text-orange-500" aria-hidden="true">warning</span>
                                    <span>{{ rateLimit.remainingAttempts }} tentativa{{ rateLimit.remainingAttempts > 1 ? 's' : '' }} restantes</span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="hidden grid-cols-1 gap-4 md:grid-cols-2 lg:grid">
                        <div
                            class="rounded-3xl bg-white/85 p-5 shadow-md ring-1 ring-gray-200/60 transition-transform duration-300 hover:-translate-y-1"
                        >
                            <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-teal-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8V4m0 0l-3 3m3-3l3 3m-9 9a6 6 0 1112 0v1a2 2 0 11-4 0v-1a2 2 0 10-4 0v1a2 2 0 11-4 0v-1z"
                                    />
                                </svg>
                            </div>
                            <p class="text-2xl font-bold text-gray-800">Privacidade Total</p>
                            <p class="mt-2 text-sm text-gray-600">Seus dados são criptografados e seguem rigorosamente a LGPD.</p>
                        </div>

                        <div
                            class="rounded-3xl bg-white/85 p-5 shadow-md ring-1 ring-gray-200/60 transition-transform duration-300 hover:-translate-y-1"
                        >
                            <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-orange-100 text-orange-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <p class="text-2xl font-bold text-gray-800">Atendimento Ágil</p>
                            <p class="mt-2 text-sm text-gray-600">Conecte-se com especialistas em minutos após a validação.</p>
                        </div>
                    </div>

                    <div class="text-center text-sm text-gray-600">
                        Já tem uma conta?
                        <TextLink :href="login()" class="ml-1 font-semibold text-teal-700 hover:text-teal-800" :tabindex="10">
                            Faça login aqui
                        </TextLink>
                    </div>
                </section>
            </div>
        </div>
    </AuthBase>
</template>

<style scoped>
.animate-fade-up {
    animation: fadeUp 500ms cubic-bezier(0.22, 1, 0.36, 1);
}

.animate-fade-up-delayed {
    animation: fadeUp 580ms cubic-bezier(0.22, 1, 0.36, 1) 80ms both;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 220ms ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-up,
    .animate-fade-up-delayed {
        animation: none;
    }

    .fade-slide-enter-active,
    .fade-slide-leave-active {
        transition: none;
    }
}
</style>
