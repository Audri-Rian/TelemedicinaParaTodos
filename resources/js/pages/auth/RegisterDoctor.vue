<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useDoctorRegistration } from '@/composables/Doctor/useDoctorRegistration';
import BackgroundDecorativo from '@/components/BackgroundDecorativo.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, ChevronDown, X } from 'lucide-vue-next';

// Props do Inertia
const props = defineProps<{
  specializations: Array<{ id: string; name: string }>;
}>();

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
} = useDoctorRegistration();

// Estado local para especializações
const availableSpecializations = ref<Array<{ id: string; name: string }>>([]);
const isDropdownOpen = ref(false);
const searchTerm = ref('');
const filteredSpecializations = ref<Array<{ id: string; name: string }>>([]);

// Estado local
const showSuccessMessage = ref(false);

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
                                        <Input 
                                            id="name" 
                                            type="text" 
                                            required 
                                            autofocus 
                                            :tabindex="1"
                                            autocomplete="name" 
                                            name="name" 
                                            placeholder="Dr. Seu Nome Completo"
                                            v-model="formData.name"
                                            @blur="touchField('name')"
                                            :class="[
                                                'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
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

                                <!-- Campos CRM e Especializações lado a lado -->
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
                                            <Input 
                                                id="crm" 
                                                type="text" 
                                                required 
                                                :tabindex="2"
                                                autocomplete="off" 
                                                name="crm" 
                                                placeholder="Ex: 123456SP"
                                                v-model="formData.crm"
                                                @blur="touchField('crm')"
                                                :class="[
                                                    'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
                                                    hasFieldError('crm') && isFieldTouched('crm') 
                                                        ? 'border-red-500 focus:border-red-500' 
                                                        : 'border-gray-200/50 focus:border-primary'
                                                ]"
                                                :aria-invalid="hasFieldError('crm') && isFieldTouched('crm')"
                                                :aria-describedby="hasFieldError('crm') && isFieldTouched('crm') ? 'crm-error' : undefined"
                                            />
                                        </div>
                                        <InputError 
                                            v-if="hasFieldError('crm') && isFieldTouched('crm')"
                                            :message="getFieldError('crm')" 
                                            id="crm-error"
                                        />
                                    </div>

                                    <!-- Campo Especializações -->
                                    <div class="space-y-0.5">
                                        <Label class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                            <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                                </path>
                                            </svg>
                                            Especializações
                                        </Label>
                                        
                                        <!-- Multi-select de especializações -->
                                        <div class="relative specialization-dropdown">
                                            <!-- Campo de entrada com tags selecionadas -->
                                            <div 
                                                @click="isDropdownOpen = !isDropdownOpen"
                                                @blur="touchField('specializations')"
                                                :class="[
                                                    'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md w-full cursor-pointer flex items-center justify-between',
                                                    hasFieldError('specializations') && isFieldTouched('specializations') 
                                                        ? 'border-red-500 focus:border-red-500' 
                                                        : 'border-gray-200/50 focus:border-primary'
                                                ]"
                                                :aria-invalid="hasFieldError('specializations') && isFieldTouched('specializations')"
                                                :aria-describedby="hasFieldError('specializations') && isFieldTouched('specializations') ? 'specializations-error' : undefined"
                                                tabindex="3"
                                            >
                                                <div class="flex flex-wrap items-center gap-1 flex-1 min-w-0">
                                                    <!-- Tags das especializações selecionadas -->
                                                    <span 
                                                        v-for="spec in getSelectedSpecializations()" 
                                                        :key="spec.id"
                                                        class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-black text-xs rounded-md"
                                                    >
                                                        {{ spec.name }}
                                                        <button 
                                                            type="button"
                                                            @click.stop="removeSpecialization(spec.id)"
                                                            class="hover:text-gray-600 text-gray-500"
                                                        >
                                                            <X class="w-3 h-3" />
                                                        </button>
                                                    </span>
                                                    
                                                    <!-- Placeholder quando nenhuma especialização está selecionada -->
                                                    <span 
                                                        v-if="formData.specializations.length === 0"
                                                        class="text-gray-400"
                                                    >
                                                        Selecione suas especializações
                                                    </span>
                                                </div>
                                                
                                                <!-- Ícone de dropdown -->
                                                <ChevronDown 
                                                    :class="[
                                                        'w-4 h-4 text-gray-400 transition-transform duration-200',
                                                        isDropdownOpen ? 'rotate-180' : ''
                                                    ]"
                                                />
                                            </div>

                                            <!-- Dropdown de especializações -->
                                            <div 
                                                v-if="isDropdownOpen"
                                                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl lg:rounded-2xl shadow-lg max-h-60 overflow-hidden"
                                            >
                                                <!-- Campo de busca -->
                                                <div class="p-3 border-b border-gray-100">
                                                    <Input 
                                                        v-model="searchTerm"
                                                        placeholder="Buscar especializações..."
                                                        class="h-8 text-sm"
                                                        @click.stop
                                                    />
                                                </div>
                                                
                                                <!-- Lista de especializações -->
                                                <div class="max-h-48 overflow-y-auto">
                                                    <div 
                                                        v-for="spec in filteredSpecializations" 
                                                        :key="spec.id"
                                                        @click="toggleSpecialization(spec.id)"
                                                        :class="[
                                                            'px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 flex items-center justify-between',
                                                            isSpecializationSelected(spec.id) ? 'bg-primary/5 text-primary' : 'text-gray-700'
                                                        ]"
                                                    >
                                                        <span>{{ spec.name }}</span>
                                                        <Checkbox 
                                                            :checked="isSpecializationSelected(spec.id)"
                                                            class="pointer-events-none"
                                                        />
                                                    </div>
                                                    
                                                    <!-- Mensagem quando não há resultados -->
                                                    <div 
                                                        v-if="filteredSpecializations.length === 0"
                                                        class="px-3 py-2 text-sm text-gray-500 text-center"
                                                    >
                                                        Nenhuma especialização encontrada
                                                    </div>
                                                </div>
                                                
                                                <!-- Contador de seleções -->
                                                <div class="px-3 py-2 text-xs text-gray-500 border-t border-gray-100 bg-gray-50">
                                                    {{ formData.specializations.length }} de 5 especializações selecionadas
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <InputError 
                                            v-if="hasFieldError('specializations') && isFieldTouched('specializations')"
                                            :message="getFieldError('specializations')" 
                                            id="specializations-error"
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
                                        <Input 
                                            id="email" 
                                            type="email" 
                                            required 
                                            :tabindex="4" 
                                            autocomplete="email"
                                            name="email" 
                                            placeholder="seu@email.com"
                                            v-model="formData.email"
                                            @blur="touchField('email')"
                                            :class="[
                                                'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
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
                                        <Input 
                                            id="password" 
                                            type="password" 
                                            required 
                                            :tabindex="5"
                                            autocomplete="new-password" 
                                            name="password"
                                            placeholder="Mínimo 8 caracteres"
                                            v-model="formData.password"
                                            @blur="touchField('password')"
                                            :class="[
                                                'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
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
                                            viewBox="0 0 24 24">
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
                                            :tabindex="6"
                                            autocomplete="new-password" 
                                            name="password_confirmation"
                                            placeholder="Digite a senha novamente"
                                            v-model="formData.password_confirmation"
                                            @blur="touchField('password_confirmation')"
                                            :class="[
                                                'h-9 lg:h-11 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 rounded-xl lg:rounded-2xl px-4 text-sm placeholder:text-gray-400 focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md',
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

                                <!-- Termos de Serviço e LGPD -->
                                <div class="space-y-2">
                                    <div class="flex items-start gap-3">
                                        <input 
                                            id="terms_accepted" 
                                            type="checkbox" 
                                            :tabindex="7"
                                            v-model="formData.terms_accepted"
                                            class="mt-1 h-4 w-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2"
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
                                                conforme a LGPD.
                                            </label>
                                        </div>
                                    </div>
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
                        :tabindex="9">
                        Faça login aqui
                    </TextLink>
                </p>
            </div>
        </div>
    </AuthBase>
</template>
