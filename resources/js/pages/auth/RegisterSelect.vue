<template>
  <AuthBase>

    <Head title="Seleção de Registro" />

    <!-- Background decorativo moderno -->
    <BackgroundDecorativo variant="patient" intensity="medium" :enable-animations="true" />

    <!-- Container principal com layout responsivo -->
    <div class="relative w-full max-w-4xl mx-auto z-10">
      <div class="flex flex-col items-center justify-center">
        
        <!-- Formulário -->
        <div class="w-full border rounded-3xl">
          <div>
            <!-- Card do formulário -->
            <div class="bg-white/95 backdrop-blur-lg rounded-2xl lg:rounded-3xl p-4 lg:p-6 shadow-2xl border border-white/40 hover:shadow-3xl transition-all duration-500 relative z-20">
              <!-- Header com ícone -->
              <div class="text-center relative">
                <!-- Elementos decorativos -->
                <div class="absolute -top-2 -left-2 w-4 h-4 bg-orange-200 rounded-full opacity-60 animate-pulse"></div>
                <div class="absolute -top-1 -right-3 w-3 h-3 bg-primary/30 rounded-full opacity-70 animate-pulse delay-1000"></div>
                <div class="absolute top-2 -left-4 w-2 h-2 bg-orange-300 rounded-full opacity-50 animate-pulse delay-2000"></div>
                
                <div class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-primary/20 to-primary/10 rounded-lg mb-1 shadow-lg">
                  <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                  </svg>
                </div>
                <h1 class="text-lg lg:text-xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                  Como você quer se registrar?
                </h1>
                <p class="text-sm lg:text-base text-gray-500 font-medium mb-6">Escolha o tipo de conta para continuar sua jornada.</p>
                
                <!-- Linha decorativa -->
                <div class="flex items-center justify-center mb-4">
                  <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent w-20"></div>
                  <div class="mx-3 w-2 h-2 bg-primary/40 rounded-full"></div>
                  <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent w-20"></div>
                </div>
              </div>

              <!-- Cards de seleção -->
              <div class="space-y-4 lg:space-y-5">
                <!-- Card Paciente -->
                <button
                  @click="goToPatientRegister"
                  class="group w-full relative overflow-hidden rounded-xl lg:rounded-2xl bg-gradient-to-r from-orange-50/80 to-orange-100/80 border-2 border-orange-200/60 px-5 lg:px-7 py-3 lg:py-4 text-left shadow-lg hover:shadow-xl hover:border-orange-300/80 transform hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-orange-200/60"
                >
                  <!-- Efeito de brilho no hover -->
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-orange-50/40 to-transparent transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                  
                  <div class="relative flex items-center space-x-4 lg:space-x-5">
                    <div class="flex-shrink-0">
                      <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl lg:rounded-2xl flex items-center justify-center">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1">
                      <h3 class="text-sm lg:text-xl font-bold text-orange-800 mb-1 lg:mb-2">Sou Paciente</h3>
                      <p class="text-orange-600 text-xs lg:text-lg">
                        Agende consultas online, acesse seu histórico médico completo, receba lembretes de medicamentos e tenha acesso a profissionais qualificados 24/7
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                      <svg class="w-5 h-5 lg:w-6 lg:h-6 text-orange-400 group-hover:text-orange-600 group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                    </div>
                  </div>
                </button>

                <!-- Separador decorativo -->
                <div class="flex items-center justify-center py-2">
                  <div class="flex items-center space-x-2">
                    <div class="w-8 h-px bg-gradient-to-r from-transparent via-gray-300 to-gray-400"></div>
                    <div class="flex items-center space-x-1">
                      <div class="w-1 h-1 bg-orange-300 rounded-full"></div>
                      <div class="w-1 h-1 bg-primary/50 rounded-full"></div>
                      <div class="w-1 h-1 bg-orange-300 rounded-full"></div>
                    </div>
                    <div class="w-8 h-px bg-gradient-to-r from-gray-400 via-gray-300 to-transparent"></div>
                  </div>
                </div>

                <!-- Card Médico -->
                <button
                  @click="goToDoctorRegister"
                  class="group w-full relative overflow-hidden rounded-xl lg:rounded-2xl bg-white border-2 border-gray-200/50 px-5 lg:px-7 py-3 lg:py-4 text-left shadow-lg hover:shadow-xl hover:border-primary/30 transform hover:scale-[1.02] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                  <!-- Efeito de brilho no hover -->
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/5 to-transparent transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                  
                  <div class="relative flex items-center space-x-4 lg:space-x-5">
                    <div class="flex-shrink-0">
                      <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-r from-primary/20 to-primary/10 rounded-xl lg:rounded-2xl flex items-center justify-center">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1">
                      <h3 class="text-sm lg:text-xl font-bold text-gray-800 mb-1 lg:mb-2">Sou Médico</h3>
                      <p class="text-gray-600 text-xs lg:text-lg">
                        Gerencie sua agenda profissional, atenda pacientes remotamente, mantenha prontuários digitais e tenha acesso a ferramentas avançadas de diagnóstico
                      </p>
                    </div>
                    <div class="flex-shrink-0">
                      <svg class="w-5 h-5 lg:w-6 lg:h-6 text-gray-400 group-hover:text-primary group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                    </div>
                  </div>
                </button>
              </div>

              <!-- Elementos decorativos no final -->
              <div class="mt-6 flex justify-center space-x-4">
                <div class="flex items-center space-x-2 text-xs text-gray-400">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                  </svg>
                  <span>100% Seguro</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-gray-400">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                  </svg>
                  <span>Rápido</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-gray-400">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                  </svg>
                  <span>Confiável</span>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Link para login -->
    <div class="text-center relative z-10">
      <!-- Elementos decorativos ao redor -->
      <div class="absolute -top-4 -left-8 w-6 h-6 bg-orange-100 rounded-full opacity-40 animate-pulse"></div>
      <div class="absolute -bottom-2 -right-6 w-4 h-4 bg-primary/20 rounded-full opacity-50 animate-pulse delay-1000"></div>
      
      <div class="inline-flex items-center gap-2 bg-white/80 backdrop-blur-md rounded-lg lg:rounded-xl px-3 lg:px-4 py-2 lg:py-3 shadow-lg border border-white/30 relative">
        <!-- Elemento decorativo interno -->
        <div class="absolute -inset-1 bg-gradient-to-r from-orange-100/20 to-primary/20 rounded-lg lg:rounded-xl blur-sm -z-10"></div>
        
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
