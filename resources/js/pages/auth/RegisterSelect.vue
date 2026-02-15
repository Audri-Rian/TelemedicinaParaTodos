<template>
  <AuthBase
    title="Seja Bem-Vindo(a)"
    description="Escolha seu perfil para continuar"
  >
    <Head title="Seleção de Registro" />

    <!-- Container principal - fundo branco, layout limpo -->
    <div class="relative w-full max-w-4xl mx-auto z-10 flex flex-col items-center justify-center px-4 py-2">
      <!-- Cards de seleção lado a lado -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-4 w-full">
        <!-- Card Paciente -->
        <div
          @click="selectedProfile = 'patient'"
          :class="[
            'group cursor-pointer bg-white rounded-2xl border-2 transition-all duration-300 p-8 text-center',
            selectedProfile === 'patient'
              ? 'border-primary shadow-md ring-2 ring-primary/20'
              : 'border-primary/60 hover:border-primary hover:shadow-md'
          ]"
        >
          <!-- Imagem: mesma do registro de paciente -->
          <div class="flex justify-center mb-6">
            <img
              :src="patientDoodleImage"
              alt="Paciente"
              class="w-32 h-32 object-contain drop-shadow-lg"
            />
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-3">Paciente</h3>
          <p class="text-gray-600 text-sm leading-relaxed">
            Acesse seus exames, agende consultas e cuide da sua saúde.
          </p>
        </div>

        <!-- Card Doutor(a) -->
        <div
          @click="selectedProfile = 'doctor'"
          :class="[
            'group cursor-pointer bg-white rounded-2xl border-2 transition-all duration-300 p-8 text-center',
            selectedProfile === 'doctor'
              ? 'border-primary shadow-md ring-2 ring-primary/20'
              : 'border-primary/60 hover:border-primary hover:shadow-md'
          ]"
        >
          <!-- Imagem: mesma do registro de doutor -->
          <div class="flex justify-center mb-6">
            <img
              :src="doctorDoodleImage"
              alt="Doutor(a)"
              class="w-32 h-32 object-contain drop-shadow-lg"
            />
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-3">Doutor(a)</h3>
          <p class="text-gray-600 text-sm leading-relaxed">
            Gerencie seus pacientes, agende consultas e acesse prontuários.
          </p>
        </div>
      </div>

      <!-- Botão Continuar -->
      <div class="text-center mb-4">
        <button
          @click="continueToRegister"
          :disabled="!selectedProfile"
          :class="[
            'px-12 py-4 rounded-xl text-lg font-semibold text-white transition-all duration-300',
            selectedProfile
              ? 'bg-primary hover:opacity-90 hover:shadow-lg'
              : 'bg-primary/50 cursor-not-allowed'
          ]"
        >
          Continuar
        </button>
      </div>

      <!-- Link para login -->
      <div class="text-center">
        <p class="text-gray-600 text-sm">
          Já tem uma conta?
          <Link href="/login" class="text-primary font-semibold hover:underline">
            Faça login aqui
          </Link>
        </p>
      </div>
    </div>
  </AuthBase>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Link, Head } from '@inertiajs/vue3'
import AuthBase from '@/layouts/AuthLayout.vue'
import patientDoodleImage from '@images/PatientDoodle.png'
import doctorDoodleImage from '@images/DoctorDoodle.png'

type Profile = 'patient' | 'doctor' | null

const selectedProfile = ref<Profile>(null)

function continueToRegister() {
  if (selectedProfile.value === 'patient') {
    router.visit('/register/patient')
  } else if (selectedProfile.value === 'doctor') {
    router.visit('/register/doctor')
  }
}
</script>
