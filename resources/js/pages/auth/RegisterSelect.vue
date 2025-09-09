<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full space-y-8">
      <!-- Título principal -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          Como você quer se registrar?
        </h1>
        <p class="text-sm text-gray-600">
          Escolha o tipo de conta para continuar o registro
        </p>
      </div>

      <!-- Botões de seleção -->
      <div class="space-y-4">
        <!-- Botão Paciente -->
        <button
          @click="goToPatientRegister"
          class="w-full flex items-center justify-center px-6 py-4 border border-transparent text-base font-medium rounded-lg text-gray-900 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
        >
          <div class="text-center">
            <div class="text-lg font-semibold">Sou Paciente</div>
            <div class="text-sm text-gray-600 mt-1">
              Registre-se para agendar consultas
            </div>
          </div>
        </button>

        <!-- Botão Médico -->
        <button
          @click="goToDoctorRegister"
          class="w-full flex items-center justify-center px-6 py-4 border border-transparent text-base font-medium rounded-lg text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
        >
          <div class="text-center">
            <div class="text-lg font-semibold">Sou Médico</div>
            <div class="text-sm text-gray-300 mt-1">
              Registre-se para atender pacientes
            </div>
          </div>
        </button>
      </div>

      <!-- Link para voltar ao login -->
      <div class="text-center">
        <Link
          href="/login"
          class="text-sm text-gray-600 hover:text-gray-900 transition-colors duration-200"
        >
          Já tem uma conta? Faça login
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

// Função para redirecionar para registro de paciente
const goToPatientRegister = () => {
  router.visit('/register/patient')
}

// Função para redirecionar para registro de médico
const goToDoctorRegister = () => {
  router.visit('/register/doctor')
}
</script>
