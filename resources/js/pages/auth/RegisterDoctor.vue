<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
</script>

<template>
    <AuthBase title="Seja bem-vindo a Telemedicina para Todos"
        description="Conecte-se com pacientes e ofereça cuidados médicos de qualidade.">

        <Head title="Registro de Médico" />

        <!-- Background decorativo moderno -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <!-- Gradiente base sutil -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-primary/10"></div>
            
            <!-- Formas abstratas flutuantes -->
            <!-- Canto superior esquerdo -->
            <div class="absolute -top-20 -left-20 w-80 h-80 bg-primary/20 rounded-full blur-3xl opacity-30 animate-pulse"></div>
            <div class="absolute top-10 left-10 w-40 h-40 bg-primary/15 rounded-full blur-2xl opacity-25"></div>
            
            <!-- Canto superior direito -->
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-primary/25 rounded-full blur-3xl opacity-20"></div>
            <div class="absolute top-20 right-20 w-32 h-32 bg-primary/20 rounded-full blur-xl opacity-30"></div>
            
            <!-- Canto inferior esquerdo -->
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-primary/18 rounded-full blur-3xl opacity-25"></div>
            <div class="absolute bottom-16 left-16 w-48 h-48 bg-primary/12 rounded-full blur-2xl opacity-30"></div>
            
            <!-- Canto inferior direito -->
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-primary/22 rounded-full blur-3xl opacity-20"></div>
            <div class="absolute bottom-12 right-12 w-36 h-36 bg-primary/15 rounded-full blur-xl opacity-25"></div>
            
            <!-- Centro da tela - luz difusa -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl opacity-20"></div>
            
            <!-- Formas menores espalhadas -->
            <div class="absolute top-1/3 left-1/4 w-24 h-24 bg-primary/20 rounded-full blur-xl opacity-25"></div>
            <div class="absolute top-2/3 right-1/3 w-20 h-20 bg-primary/15 rounded-full blur-lg opacity-30"></div>
            <div class="absolute top-1/4 right-1/4 w-28 h-28 bg-primary/18 rounded-full blur-2xl opacity-20"></div>
            <div class="absolute bottom-1/3 left-1/3 w-16 h-16 bg-primary/25 rounded-full blur-lg opacity-25"></div>
        </div>

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

                            <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']"
                                v-slot="{ errors, processing }" class="space-y-3 lg:space-y-4">

                                <!-- Campo Nome -->
                                <div class="space-y-1">
                                    <Label for="name" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
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
                                            class="h-10 lg:h-12 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm lg:text-base placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.name" />
                                </div>

                                <!-- Campo CRM -->
                                <div class="space-y-1">
                                    <Label for="crm" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        CRM (Conselho Regional de Medicina)
                                    </Label>
                                    <div class="relative">
                                        <Input id="crm" type="text" required :tabindex="2"
                                            autocomplete="off" name="crm" placeholder="Ex: 123456-SP"
                                            class="h-10 lg:h-12 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm lg:text-base placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.crm" />
                                </div>

                                <!-- Campo Especialidade -->
                                <div class="space-y-1">
                                    <Label for="specialty" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                            </path>
                                        </svg>
                                        Especialidade Principal
                                    </Label>
                                    <div class="relative">
                                        <select id="specialty" name="specialty" required :tabindex="3"
                                            class="h-10 lg:h-12 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm lg:text-base text-gray-700 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md w-full">
                                            <option value="">Selecione sua especialidade</option>
                                            <option value="cardiologia">Cardiologia</option>
                                            <option value="dermatologia">Dermatologia</option>
                                            <option value="endocrinologia">Endocrinologia</option>
                                            <option value="gastroenterologia">Gastroenterologia</option>
                                            <option value="ginecologia">Ginecologia</option>
                                            <option value="neurologia">Neurologia</option>
                                            <option value="oftalmologia">Oftalmologia</option>
                                            <option value="ortopedia">Ortopedia</option>
                                            <option value="pediatria">Pediatria</option>
                                            <option value="psiquiatria">Psiquiatria</option>
                                            <option value="urologia">Urologia</option>
                                            <option value="clinica_geral">Clínica Geral</option>
                                            <option value="outras">Outras</option>
                                        </select>
                                    </div>
                                    <InputError :message="errors.specialty" />
                                </div>

                                <!-- Campo Email -->
                                <div class="space-y-1">
                                    <Label for="email" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
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
                                            class="h-10 lg:h-12 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm lg:text-base placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.email" />
                                </div>

                                <!-- Campo Senha -->
                                <div class="space-y-1">
                                    <Label for="password"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
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
                                            class="h-10 lg:h-12 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm lg:text-base placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.password" />
                                </div>

                                <!-- Campo Confirmar Senha -->
                                <div class="space-y-1">
                                    <Label for="password_confirmation"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor"
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
                                            class="h-10 lg:h-12 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-sm lg:text-base placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.password_confirmation" />
                                </div>

                                <!-- Botão de Registro -->
                                <div class="">
                                    <Button type="submit"
                                        class="w-full h-10 lg:h-12 bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary text-black text-sm lg:text-base font-bold rounded-xl lg:rounded-2xl shadow-xl hover:shadow-2xl transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border-2 border-primary/20 hover:border-primary/30"
                                        tabindex="7" :disabled="processing">
                                        <div class="flex items-center justify-center gap-2">
                                            <LoaderCircle v-if="processing" class="w-5 h-5 animate-spin" />
                                            <svg v-if="!processing" class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <span class="font-bold">{{ processing ? 'Registrando médico...' : 'Registrar como Médico'
                                                }}</span>
                                        </div>
                                    </Button>
                                </div>
                            </Form>
                        </div>
                    </div>
                </div>

                <!-- Imagem central - apenas em desktop -->
                <div class="hidden lg:flex relative w-[500px] h-[500px] items-center justify-center">
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
                                class="w-[520px] h-[520px] object-contain drop-shadow-2xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link para login -->
        <div class="text-center mt-6 lg:mt-1 relative z-10">
            <div
                class="inline-flex items-center gap-2 bg-white/80 backdrop-blur-md rounded-xl lg:rounded-2xl px-3 lg:px-4 py-2 lg:py-3 shadow-lg border border-white/30">
                <svg class="w-3 h-3 lg:w-4 lg:h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg>
                <p class="text-gray-600 text-sm lg:text-base font-medium">
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
