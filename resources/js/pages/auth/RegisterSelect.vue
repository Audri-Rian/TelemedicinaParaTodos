<template>
  <AuthBase>
    <Head title="Seleção de Registro" />

    <!-- Background decorativo moderno -->
    <BackgroundDecorativo variant="patient" intensity="medium" :enable-animations="true" />

    <!-- Container principal com layout responsivo -->
    <div class="relative w-full max-w-6xl mx-auto z-10 min-h-screen flex flex-col items-center justify-center px-4">
      
      <!-- Cabeçalho -->
      <div class="text-center mb-12">
        <h1 class="text-4xl lg:text-5xl font-bold text-gray-800 mb-4">
          Seja Bem-Vindo(a)
        </h1>
        <p class="text-lg lg:text-xl text-gray-600">
          Escolha seu perfil para continuar
        </p>
      </div>

      <!-- Cards de seleção lado a lado -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-12 w-full max-w-4xl">
        
        <!-- Card Paciente -->
        <div 
          @click="goToPatientRegister"
          class="group cursor-pointer bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 p-8 text-center"
        >
          <!-- Ícone -->
          <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center shadow-lg">
              <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
          </div>
          
          <!-- Conteúdo -->
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Paciente</h3>
          <p class="text-gray-600 leading-relaxed">
            Acesse seus exames, agende consultas e cuide da sua saúde.
          </p>
        </div>

        <!-- Card Médico -->
        <div 
          @click="goToDoctorRegister"
          class="group cursor-pointer bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 p-8 text-center"
        >
          <!-- Ícone -->
          <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center shadow-lg">
              <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
          </div>
          
          <!-- Conteúdo -->
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Doutor(a)</h3>
          <p class="text-gray-600 leading-relaxed">
            Gerencie seus pacientes, agende consultas e acesse prontuários.
          </p>
        </div>
      </div>

      <!-- Botão Continuar -->
      <div class="text-center">
        <button 
          @click="goToPatientRegister"
          class="bg-primary text-white px-12 py-4 rounded-xl text-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105"
        >
          Continuar
        </button>
      </div>

      <!-- Link para login -->
      <div class="text-center mt-12 relative z-10">
        <div class="inline-flex items-center gap-2 bg-white/80 backdrop-blur-md rounded-lg lg:rounded-xl px-3 lg:px-4 py-2 lg:py-3 shadow-lg border border-white/30 relative">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
          </svg>
          <p class="text-gray-600 text-sm lg:text-base font-medium">
            Já tem uma conta?
            <Link href="/login" class="text-black hover:text-black/80 font-bold underline underline-offset-4 hover:underline-offset-2 transition-all duration-300 ml-1">
              Faça login aqui
            </Link>
          </p>
        </div>
      </div>
    </div>
  </AuthBase>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Link, Head } from '@inertiajs/vue3'
import AuthBase from '@/layouts/AuthLayout.vue'
import BackgroundDecorativo from '@/components/BackgroundDecorativo.vue'

// Função para redirecionar para registro de paciente
const goToPatientRegister = () => {
  router.visit('/register/patient')
}

// Função para redirecionar para registro de médico
const goToDoctorRegister = () => {
  router.visit('/register/doctor')
}
</script>
