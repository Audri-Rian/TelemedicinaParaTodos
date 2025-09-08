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
        description="Sua jornada de saúde começa aqui. Registre-se para começar.">

        <Head title="Register" />

        <!-- Container principal com layout responsivo -->
        <div class="w-full max-w-8xl mx-auto">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8 lg:gap-20">

                <!-- Formulário -->
                <div class="w-full lg:w-200 border rounded-3xl">
                    <div>
                        <!-- Card do formulário -->
                        <div
                            class="bg-white/90 backdrop-blur-md rounded-2xl lg:rounded-3xl p-4 lg:p-5 shadow-2xl border border-white/30 hover:shadow-3xl transition-all duration-500">
                            <!-- Header com ícone -->
                            <div class="text-center">
                                <div
                                    class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-primary/20 to-primary/10 rounded-xl mb-2 shadow-lg">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <h1
                                    class="text-xl lg:text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                                    Crie sua conta</h1>
                                <p class="text-xs lg:text-sm text-gray-500 mt-2 font-medium">Junte-se à nossa comunidade
                                    médica</p>
                            </div>

                            <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']"
                                v-slot="{ errors, processing }" class="space-y-4 lg:space-y-5">

                                <!-- Campo Nome -->
                                <div class="space-y-1">
                                    <Label for="name" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                        Nome completo
                                    </Label>
                                    <div class="relative">
                                        <Input id="name" type="text" required autofocus :tabindex="1"
                                            autocomplete="name" name="name" placeholder="Digite seu nome completo"
                                            class="h-12 lg:h-14 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-base lg:text-lg placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.name" />
                                </div>

                                <!-- Campo Email -->
                                <div class="space-y-1">
                                    <Label for="email" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        E-mail
                                    </Label>
                                    <div class="relative">
                                        <Input id="email" type="email" required :tabindex="2" autocomplete="email"
                                            name="email" placeholder="seu@email.com"
                                            class="h-12 lg:h-14 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-base lg:text-lg placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.email" />
                                </div>

                                <!-- Campo Senha -->
                                <div class="space-y-1">
                                    <Label for="password"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                            </path>
                                        </svg>
                                        Senha
                                    </Label>
                                    <div class="relative">
                                        <Input id="password" type="password" required :tabindex="3"
                                            autocomplete="new-password" name="password"
                                            placeholder="Mínimo 8 caracteres"
                                            class="h-12 lg:h-14 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-base lg:text-lg placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.password" />
                                </div>

                                <!-- Campo Confirmar Senha -->
                                <div class="space-y-1">
                                    <Label for="password_confirmation"
                                        class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Confirmar senha
                                    </Label>
                                    <div class="relative">
                                        <Input id="password_confirmation" type="password" required :tabindex="4"
                                            autocomplete="new-password" name="password_confirmation"
                                            placeholder="Digite a senha novamente"
                                            class="h-12 lg:h-14 bg-gradient-to-r from-gray-50/90 to-white/90 border-2 border-gray-200/50 rounded-xl lg:rounded-2xl px-4 text-base lg:text-lg placeholder:text-gray-400 focus:border-primary focus:bg-white focus:shadow-lg focus:shadow-primary/10 transition-all duration-300 hover:border-gray-300 hover:shadow-md" />
                                    </div>
                                    <InputError :message="errors.password_confirmation" />
                                </div>

                                <!-- Botão de Registro -->
                                <div class="">
                                    <Button type="submit"
                                        class="w-full h-12 lg:h-14 bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary text-black text-base lg:text-lg font-bold rounded-xl lg:rounded-2xl shadow-xl hover:shadow-2xl transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border-2 border-primary/20 hover:border-primary/30"
                                        tabindex="5" :disabled="processing">
                                        <div class="flex items-center justify-center gap-2">
                                            <LoaderCircle v-if="processing" class="w-5 h-5 animate-spin" />
                                            <svg v-if="!processing" class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span class="font-bold">{{ processing ? 'Criando conta...' : 'Criar conta'
                                                }}</span>
                                        </div>
                                    </Button>
                                </div>
                            </Form>
                        </div>
                    </div>
                </div>

                <!-- Imagem central - apenas em desktop -->
                <div class="hidden lg:flex relative w-[600px] h-[600px] items-center justify-center">
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
                            <img src="/storage/photos/doctordoodle.png" alt="Doctor Doodle"
                                class="w-[620px] h-[620px] object-contain drop-shadow-2xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link para login -->
        <div class="text-center mt-6 lg:mt-1">
            <div
                class="inline-flex items-center gap-2 bg-white/60 backdrop-blur-sm rounded-xl lg:rounded-2xl px-3 lg:px-4 py-2 lg:py-3 shadow-lg border border-white/20">
                <svg class="w-3 h-3 lg:w-4 lg:h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg>
                <p class="text-gray-600 text-sm lg:text-base font-medium">
                    Já tem uma conta?
                    <TextLink :href="login()"
                        class="text-primary hover:text-primary/80 font-bold underline underline-offset-4 hover:underline-offset-2 transition-all duration-300 ml-1"
                        :tabindex="6">
                        Faça login aqui
                    </TextLink>
                </p>
            </div>
        </div>
    </AuthBase>
</template>
