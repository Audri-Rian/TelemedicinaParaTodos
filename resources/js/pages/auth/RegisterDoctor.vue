<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useDoctorRegistration } from '@/composables/Doctor/useDoctorRegistration';
import BackgroundDecorativo from '@/components/BackgroundDecorativo.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { LoaderCircle, ChevronDown, X } from 'lucide-vue-next';
import doctorDoodleImage from '@images/DoctorDoodle.png';

// Props do Inertia
const props = defineProps<{
  specializations: Array<{ id: string; name: string }>;
}>();

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
  isFieldTouched
} = useDoctorRegistration();

// Estado local para especializações
const availableSpecializations = ref<Array<{ id: string; name: string }>>([]);
const isDropdownOpen = ref(false);
const searchTerm = ref('');
const filteredSpecializations = ref<Array<{ id: string; name: string }>>([]);

// Estado local
const showSuccessMessage = ref(false);

const fieldLabels: Record<string, string> = {
  name: 'nome completo',
  crm: 'CRM',
  cns: 'CNS',
  specializations: 'especialização',
  email: 'e-mail profissional',
  password: 'senha',
  password_confirmation: 'confirmação de senha'
};

const showValidationNotice = computed(() => hasErrors.value && !submitError.value && !isSubmitting.value);

// terms_accepted não é validado pelo composable (não está em RequiredDoctorFields),
// então gerenciamos estado de "tocado" e erro localmente para exibir feedback inline.
const termsTouched = ref(false);
const termsError = computed(() => {
    if (!termsTouched.value) return '';
    return formData.value.terms_accepted ? '' : 'Aceite os Termos de Uso e a Política de Privacidade para continuar.';
});

const getFriendlyFieldError = (fieldName: string): string => {
  const rawMessage = getFieldError(fieldName as 'name' | 'crm' | 'email' | 'password' | 'password_confirmation' | 'specializations');
  if (!rawMessage) return '';

  const label = fieldLabels[fieldName] || 'campo';
  const normalized = rawMessage.toLowerCase();

  if (normalized.includes('é obrigatório')) return `Informe ${label}.`;
  if (normalized.includes('email válido')) return 'Digite um e-mail no formato nome@dominio.com.';
  if (normalized.includes('pelo menos') && normalized.includes('caracteres')) return `Use pelo menos ${rawMessage.match(/\d+/)?.[0] || '8'} caracteres em ${label}.`;
  if (normalized.includes('deve ser igual')) return 'A confirmação precisa ser igual à senha informada.';
  if (normalized.includes('apenas letras e espaços')) return 'Use somente letras e espaços no nome.';
  if (normalized.includes('crm deve conter')) return 'Digite o CRM usando letras maiúsculas e números (sem símbolos).';
  if (normalized.includes('especializa')) return 'Selecione ao menos uma especialização válida.';

  return rawMessage;
};

// Função para fechar dropdown quando clicar fora
const closeDropdown = () => {
  isDropdownOpen.value = false;
  searchTerm.value = '';
};

// Inicializar especializações
onMounted(() => {
  availableSpecializations.value = props.specializations || [];
  filteredSpecializations.value = availableSpecializations.value;
  
  // Listener para fechar dropdown ao clicar fora
  document.addEventListener('click', (event) => {
    const dropdown = document.querySelector('.specialization-dropdown');
    if (dropdown && !dropdown.contains(event.target as Node)) {
      closeDropdown();
    }
  });
});

// Filtrar especializações baseado na busca
watch(searchTerm, (newTerm) => {
  if (newTerm.trim() === '') {
    filteredSpecializations.value = availableSpecializations.value;
  } else {
    filteredSpecializations.value = availableSpecializations.value.filter(spec =>
      spec.name.toLowerCase().includes(newTerm.toLowerCase())
    );
  }
});

// Funções para gerenciar especializações
const toggleSpecialization = (specializationId: string) => {
  const currentSpecializations = [...formData.value.specializations];
  const index = currentSpecializations.indexOf(specializationId);
  
  if (index > -1) {
    // Remove se já estiver selecionada
    currentSpecializations.splice(index, 1);
  } else {
    // Adiciona se não estiver selecionada (máximo 5)
    if (currentSpecializations.length < 5) {
      currentSpecializations.push(specializationId);
    }
  }
  
  formData.value.specializations = currentSpecializations;
  
  // Marcar campo como tocado para validação
  touchField('specializations');
  
  // Fechar dropdown após seleção (opcional - pode manter aberto para múltiplas seleções)
  // closeDropdown();
};

const removeSpecialization = (specializationId: string) => {
  const currentSpecializations = [...formData.value.specializations];
  const index = currentSpecializations.indexOf(specializationId);
  if (index > -1) {
    currentSpecializations.splice(index, 1);
    formData.value.specializations = currentSpecializations;
    
    // Marcar campo como tocado para validação
    touchField('specializations');
  }
};

const getSelectedSpecializations = () => {
  return availableSpecializations.value.filter(spec =>
    formData.value.specializations.includes(spec.id)
  );
};

const isSpecializationSelected = (specializationId: string) => {
  return formData.value.specializations.includes(specializationId);
};

// Watchers para conectar v-model com os composables
watch(() => formData.value.name, (newValue) => {
  updateField('name', newValue);
});

watch(() => formData.value.crm, (newValue) => {
  updateField('crm', newValue);
});

watch(() => formData.value.cns, (newValue) => {
  updateField('cns', newValue);
});

watch(() => formData.value.specializations, (newValue) => {
  updateField('specializations', newValue);
}, { deep: true });

watch(() => formData.value.email, (newValue) => {
  updateField('email', newValue);
});

watch(() => formData.value.password, (newValue) => {
  updateField('password', newValue);
});

watch(() => formData.value.password_confirmation, (newValue) => {
  updateField('password_confirmation', newValue);
});

// Função para lidar com submissão.
// O submitForm agora dispara toasts para qualquer caminho de falha (rate limit,
// form inválido, erro do backend), então não precisamos de fallback manual aqui.
// O redirect acontece automaticamente via Inertia quando o backend responde 302.
const handleSubmit = async () => {
  // Força validação do terms_accepted para exibir erro inline antes de submeter.
  termsTouched.value = true;

  const success = await submitForm();
  if (success) {
    showSuccessMessage.value = true;
  }
};
</script>

<template>
    <AuthBase title=""
        description="">
        <Head title="Registro de Médico" />
        <BackgroundDecorativo variant="doctor" intensity="medium" :enable-animations="true" />

        <div class="relative z-10 mx-auto w-full max-w-7xl">
            <div class="grid grid-cols-1 items-stretch gap-8 lg:grid-cols-[420px_minmax(0,1fr)] lg:gap-12">
                <div class="animate-fade-up rounded-[28px] bg-white/90 p-6 shadow-xl ring-1 ring-gray-200/70 backdrop-blur transition-transform duration-300 hover:-translate-y-0.5 lg:min-h-[660px]">
                    <div class="mb-6">
                        <h1 class="text-4xl font-extrabold tracking-tight text-gray-900">Registro de Médico</h1>
                        <p class="mt-2 text-lg text-gray-500">Junte-se à nossa plataforma médica</p>
                    </div>

                    <Transition name="fade-slide">
                        <div v-if="showSuccessMessage" class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" role="alert" aria-live="polite">
                            Conta criada com sucesso! Redirecionando...
                        </div>
                    </Transition>

                    <Transition name="fade-slide">
                        <div v-if="submitError" class="mb-4 rounded-xl border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert" aria-live="assertive">
                            {{ submitError }}
                        </div>
                    </Transition>

                    <Transition name="fade-slide">
                        <div v-if="showValidationNotice" class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800" role="status" aria-live="polite">
                            Revise os campos destacados para continuar.
                        </div>
                    </Transition>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div>
                            <Label for="name" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">Nome completo</Label>
                            <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name"
                                placeholder="Dr. Nome Completo" v-model="formData.name" @blur="touchField('name')"
                                :class="[
                                    'h-11 rounded-full border-0 bg-gray-100 px-4 text-sm shadow-inner placeholder:text-gray-400 transition-all duration-200 placeholder:transition-colors focus-visible:ring-2 focus-visible:ring-teal-500',
                                    hasFieldError('name') && isFieldTouched('name') ? 'bg-red-50/70 ring-2 ring-red-300' : ''
                                ]"
                                :aria-invalid="hasFieldError('name') && isFieldTouched('name')"
                                :aria-describedby="hasFieldError('name') && isFieldTouched('name') ? 'name-error' : undefined" />
                            <Transition name="fade-slide">
                                <p v-if="hasFieldError('name') && isFieldTouched('name')" id="name-error" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                    <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ getFriendlyFieldError('name') }}
                                </p>
                            </Transition>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="crm" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">CRM</Label>
                                <Input id="crm" type="text" required :tabindex="2" autocomplete="off" name="crm"
                                    placeholder="000000-SP" v-model="formData.crm" @blur="touchField('crm')"
                                    :class="[
                                        'h-11 rounded-full border-0 bg-gray-100 px-4 text-sm shadow-inner placeholder:text-gray-400 transition-all duration-200 placeholder:transition-colors focus-visible:ring-2 focus-visible:ring-teal-500',
                                        hasFieldError('crm') && isFieldTouched('crm') ? 'bg-red-50/70 ring-2 ring-red-300' : ''
                                    ]"
                                    :aria-invalid="hasFieldError('crm') && isFieldTouched('crm')"
                                    :aria-describedby="hasFieldError('crm') && isFieldTouched('crm') ? 'crm-error' : undefined" />
                                <Transition name="fade-slide">
                                    <p v-if="hasFieldError('crm') && isFieldTouched('crm')" id="crm-error" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                        <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ getFriendlyFieldError('crm') }}
                                    </p>
                                </Transition>
                            </div>

                            <div>
                                <Label for="cns" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">CNS (opcional)</Label>
                                <Input id="cns" type="text" inputmode="numeric" :tabindex="3" autocomplete="off" name="cns"
                                    placeholder="000 0000 0000 000" maxlength="15" v-model="formData.cns"
                                    class="h-11 rounded-full border-0 bg-gray-100 px-4 text-sm shadow-inner placeholder:text-gray-400 transition-all duration-200 placeholder:transition-colors focus-visible:ring-2 focus-visible:ring-teal-500" />
                            </div>
                        </div>

                        <div>
                            <Label for="specializations" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">Especializações</Label>
                            <div class="relative specialization-dropdown">
                                <!-- @focusout em vez de @blur: blur em <div> com tabindex é flaky,
                                     focusout propaga corretamente quando foco sai do elemento ou filhos. -->
                                <div id="specializations" @click="isDropdownOpen = !isDropdownOpen" @focusout="touchField('specializations')" tabindex="4"
                                    :class="[
                                        'min-h-11 w-full cursor-pointer rounded-full border-0 bg-gray-100 px-3 py-2 text-sm shadow-inner focus-visible:ring-2 focus-visible:ring-teal-500',
                                        hasFieldError('specializations') && isFieldTouched('specializations') ? 'bg-red-50/70 ring-2 ring-red-300' : ''
                                    ]"
                                    :aria-invalid="hasFieldError('specializations') && isFieldTouched('specializations')"
                                    :aria-describedby="hasFieldError('specializations') && isFieldTouched('specializations') ? 'specializations-error' : undefined">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                            <TransitionGroup name="chip-pop" tag="div" class="flex flex-wrap items-center gap-1.5">
                                                <span v-for="spec in getSelectedSpecializations()" :key="spec.id"
                                                    class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-700">
                                                    {{ spec.name }}
                                                    <button type="button" @click.stop="removeSpecialization(spec.id)" class="text-teal-600 hover:text-teal-800">
                                                        <X class="h-3 w-3" />
                                                    </button>
                                                </span>
                                            </TransitionGroup>
                                            <span v-if="formData.specializations.length === 0" class="text-sm text-gray-400">Adicionar especializações</span>
                                        </div>
                                        <button type="button" class="shrink-0 text-sm font-semibold text-teal-700">+ Adicionar</button>
                                    </div>
                                </div>

                                <Transition name="dropdown-pop">
                                    <div v-if="isDropdownOpen" class="absolute z-50 mt-2 w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg">
                                        <div class="border-b border-gray-100 p-3">
                                            <Input v-model="searchTerm" placeholder="Buscar especializações..." class="h-9 rounded-xl border-gray-200 text-sm" @click.stop />
                                        </div>
                                        <div class="max-h-48 overflow-y-auto">
                                            <div v-for="spec in filteredSpecializations" :key="spec.id" @click="toggleSpecialization(spec.id)"
                                                :class="[
                                                    'flex cursor-pointer items-center justify-between px-3 py-2 text-sm transition-colors duration-150 hover:bg-gray-50',
                                                    isSpecializationSelected(spec.id) ? 'bg-teal-50 text-teal-700' : 'text-gray-700'
                                                ]">
                                                <span>{{ spec.name }}</span>
                                                <Checkbox :checked="isSpecializationSelected(spec.id)" class="pointer-events-none" />
                                            </div>
                                            <div v-if="filteredSpecializations.length === 0" class="px-3 py-2 text-center text-sm text-gray-500">
                                                Nenhuma especialização encontrada
                                            </div>
                                        </div>
                                        <div class="border-t border-gray-100 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                            {{ formData.specializations.length }} de 5 especializações selecionadas
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                            <Transition name="fade-slide">
                                <p v-if="hasFieldError('specializations') && isFieldTouched('specializations')" id="specializations-error" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                    <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ getFriendlyFieldError('specializations') }}
                                </p>
                            </Transition>
                        </div>

                        <div>
                            <Label for="email" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">E-mail profissional</Label>
                            <Input id="email" type="email" required :tabindex="5" autocomplete="email" name="email"
                                placeholder="medico@telemedicina.com" v-model="formData.email" @blur="touchField('email')"
                                :class="[
                                    'h-11 rounded-full border-0 bg-gray-100 px-4 text-sm shadow-inner placeholder:text-gray-400 transition-all duration-200 placeholder:transition-colors focus-visible:ring-2 focus-visible:ring-teal-500',
                                    hasFieldError('email') && isFieldTouched('email') ? 'bg-red-50/70 ring-2 ring-red-300' : ''
                                ]"
                                :aria-invalid="hasFieldError('email') && isFieldTouched('email')"
                                :aria-describedby="hasFieldError('email') && isFieldTouched('email') ? 'email-error' : undefined" />
                            <Transition name="fade-slide">
                                <p v-if="hasFieldError('email') && isFieldTouched('email')" id="email-error" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                    <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ getFriendlyFieldError('email') }}
                                </p>
                            </Transition>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="password" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">Senha</Label>
                                <Input id="password" type="password" required :tabindex="6" autocomplete="new-password" name="password"
                                    placeholder="••••••••" v-model="formData.password" @blur="touchField('password')"
                                    :class="[
                                        'h-11 rounded-full border-0 bg-gray-100 px-4 text-sm shadow-inner placeholder:text-gray-400 transition-all duration-200 placeholder:transition-colors focus-visible:ring-2 focus-visible:ring-teal-500',
                                        hasFieldError('password') && isFieldTouched('password') ? 'bg-red-50/70 ring-2 ring-red-300' : ''
                                    ]"
                                    :aria-invalid="hasFieldError('password') && isFieldTouched('password')"
                                    :aria-describedby="hasFieldError('password') && isFieldTouched('password') ? 'password-error' : undefined" />
                                <Transition name="fade-slide">
                                    <p v-if="hasFieldError('password') && isFieldTouched('password')" id="password-error" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                        <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ getFriendlyFieldError('password') }}
                                    </p>
                                </Transition>
                            </div>

                            <div>
                                <Label for="password_confirmation" class="mb-2 block text-[11px] font-bold uppercase tracking-[0.08em] text-gray-500">Confirmar senha</Label>
                                <Input id="password_confirmation" type="password" required :tabindex="7" autocomplete="new-password" name="password_confirmation"
                                    placeholder="••••••••" v-model="formData.password_confirmation" @blur="touchField('password_confirmation')"
                                    :class="[
                                        'h-11 rounded-full border-0 bg-gray-100 px-4 text-sm shadow-inner placeholder:text-gray-400 transition-all duration-200 placeholder:transition-colors focus-visible:ring-2 focus-visible:ring-teal-500',
                                        hasFieldError('password_confirmation') && isFieldTouched('password_confirmation') ? 'bg-red-50/70 ring-2 ring-red-300' : ''
                                    ]"
                                    :aria-invalid="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')"
                                    :aria-describedby="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation') ? 'password_confirmation-error' : undefined" />
                                <Transition name="fade-slide">
                                    <p v-if="hasFieldError('password_confirmation') && isFieldTouched('password_confirmation')" id="password_confirmation-error" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                        <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ getFriendlyFieldError('password_confirmation') }}
                                    </p>
                                </Transition>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-start gap-3">
                                <input id="terms_accepted" type="checkbox" :tabindex="8" v-model="formData.terms_accepted"
                                    @blur="termsTouched = true"
                                    @change="termsTouched = true"
                                    :aria-invalid="!!termsError"
                                    :aria-describedby="termsError ? 'terms_accepted-error' : undefined"
                                    :class="[
                                        'mt-1 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500',
                                        termsError ? 'border-red-400 ring-1 ring-red-300' : ''
                                    ]" />
                                <div class="text-xs leading-relaxed text-gray-500">
                                    <label for="terms_accepted" class="cursor-pointer">
                                        Eu aceito os
                                        <a href="/terms" target="_blank" class="font-semibold text-teal-700 hover:text-teal-800">
                                            Termos de Uso
                                        </a>
                                        e a
                                        <a href="/privacy" target="_blank" class="font-semibold text-teal-700 hover:text-teal-800">
                                            Política de Privacidade
                                        </a>.
                                    </label>
                                </div>
                            </div>
                            <Transition name="fade-slide">
                                <p v-if="termsError" id="terms_accepted-error" class="ml-7 flex items-center gap-1 text-xs text-red-600">
                                    <span class="material-icons shrink-0 text-[14px] leading-none text-red-500" aria-hidden="true">error_outline</span>{{ termsError }}
                                </p>
                            </Transition>
                        </div>

                        <div class="pt-1">
                            <!--
                                :disabled="isSubmitting" (não mais !canSubmit): queremos que o clique
                                sempre dispare submitForm() — que agora emite toast explicando o motivo
                                quando há problema (rate limit, campos faltando, etc.), evitando o
                                "botão apagado sem explicação" que frustrava o usuário.
                            -->
                            <Button type="submit" :disabled="isSubmitting" :tabindex="9"
                                class="h-12 w-full rounded-full border-0 bg-teal-700 text-sm font-bold text-white shadow-lg transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                                :aria-describedby="rateLimit.remainingAttempts < 2 ? 'rate-limit-warning' : undefined">
                                <div class="flex items-center justify-center gap-2">
                                    <LoaderCircle v-if="isSubmitting" class="h-4 w-4 animate-spin" aria-hidden="true" />
                                    <span>{{ isSubmitting ? 'Registrando médico...' : 'Registrar como Médico' }}</span>
                                </div>
                            </Button>

                            <div v-if="rateLimit.remainingAttempts < 2 && rateLimit.remainingAttempts > 0" id="rate-limit-warning"
                                class="mt-2 flex items-center justify-center gap-1 text-center text-sm text-orange-600" role="alert" aria-live="polite">
                                <span class="material-icons shrink-0 text-[16px] leading-none text-orange-500" aria-hidden="true">warning</span>
                                <span>Restam {{ rateLimit.remainingAttempts }} tentativa{{ rateLimit.remainingAttempts > 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                    </form>

                    <div class="mt-5 text-center text-sm text-gray-500">
                        Já tem uma conta?
                        <TextLink :href="login()" class="ml-1 font-semibold text-teal-700 hover:text-teal-800" :tabindex="10">
                            Faça login aqui
                        </TextLink>
                    </div>
                </div>

                <div class="animate-fade-left relative hidden h-full min-h-[660px] rounded-[28px] p-6 lg:block">
                    <div class="absolute inset-0 overflow-hidden rounded-[28px]">
                        <div class="absolute -left-10 top-24 h-40 w-40 rounded-3xl bg-white/20"></div>
                        <div class="absolute -right-12 bottom-16 h-56 w-56 rounded-3xl bg-white/20"></div>
                    </div>
                    <div class="relative flex h-full items-end justify-center">
                        <img :src="doctorDoodleImage" alt="Ilustração de médico" class="h-[620px] w-auto object-contain drop-shadow-xl" />
                        <div class="absolute bottom-8 left-8 right-8 rounded-3xl bg-white/90 p-6 shadow-lg backdrop-blur">
                            <p class="text-3xl font-extrabold leading-tight text-gray-900">O futuro da medicina é humano.</p>
                            <p class="mt-2 text-base text-gray-600">
                                Sua jornada para uma prática clínica mais eficiente e conectada começa aqui.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthBase>
</template>

<style scoped>
.animate-fade-up {
    animation: fadeUp 550ms cubic-bezier(0.22, 1, 0.36, 1);
}

.animate-fade-left {
    animation: fadeLeft 650ms cubic-bezier(0.22, 1, 0.36, 1) 120ms both;
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

@keyframes fadeLeft {
    from {
        opacity: 0;
        transform: translateX(24px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 220ms ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

.dropdown-pop-enter-active,
.dropdown-pop-leave-active {
    transition: all 180ms ease;
    transform-origin: top center;
}

.dropdown-pop-enter-from,
.dropdown-pop-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
}

.chip-pop-enter-active,
.chip-pop-leave-active {
    transition: all 180ms ease;
}

.chip-pop-enter-from,
.chip-pop-leave-to {
    opacity: 0;
    transform: scale(0.9);
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-up,
    .animate-fade-left {
        animation: none;
    }

    .fade-slide-enter-active,
    .fade-slide-leave-active,
    .dropdown-pop-enter-active,
    .dropdown-pop-leave-active,
    .chip-pop-enter-active,
    .chip-pop-leave-active {
        transition: none;
    }
}
</style>
