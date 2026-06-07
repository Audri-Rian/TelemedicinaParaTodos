<script setup lang="ts">
import { store as storeRoute } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import BackgroundDecorativo from '@/components/BackgroundDecorativo.vue';
import GoogleLoginButton from '@/components/GoogleLoginButton.vue';
import InputError from '@/components/InputError.vue';
import LottieAnimation from '@/components/LottieAnimation.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useToast } from '@/composables/useToast';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const toast = useToast();

// Criar o formulário usando useForm do Inertia
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Função para enviar o formulário
const submit = () => {
    if (!form.email || !form.password) {
        toast.warning('Informe e-mail e senha para entrar.', {
            title: 'Campos obrigatórios',
        });
        return;
    }

    form.post(storeRoute.url(), {
        // Duração curta — o redirect do Inertia pode cortar o toast.
        // Para persistir entre navegações seria preciso mover o toast p/ flash
        // de session; aqui optamos por manter o UX simples.
        onSuccess: () => {
            toast.success('Login realizado. Redirecionando...', {
                title: 'Bem-vindo de volta',
                durationMs: 1800,
            });
        },
        onError: (errors: Record<string, string | string[]>) => {
            // Laravel retorna o erro em `email` via throw ValidationException.
            // Mensagens típicas: "Credenciais inválidas" ou "Muitas tentativas" (throttle).
            const firstError = errors.email ?? errors.password ?? Object.values(errors)[0];
            const message = Array.isArray(firstError) ? firstError[0] : firstError;
            toast.error(typeof message === 'string' && message ? message : 'Não foi possível entrar. Verifique suas credenciais.', {
                title: 'Falha no login',
            });
        },
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="" description="">
        <Head title="Entrar" />

        <!-- Background decorativo moderno -->
        <BackgroundDecorativo variant="patient" intensity="medium" :enable-animations="true" />

        <div class="relative z-10 flex flex-col gap-6">
            <Card class="overflow-hidden p-0">
                <CardContent class="grid p-0 md:grid-cols-2">
                    <form @submit.prevent="submit" class="p-6 md:p-8">
                        <div class="flex flex-col gap-6">
                            <!-- Header -->
                            <div class="flex flex-col items-center gap-2 text-center">
                                <h1 class="text-2xl font-bold">Bem-vindo de volta</h1>
                                <p class="text-balance text-muted-foreground">Entre na sua conta Telemedicina Para Todos</p>
                            </div>

                            <!-- Status Message -->
                            <div v-if="status" class="text-center text-sm font-medium text-green-600">
                                {{ status }}
                            </div>

                            <!-- Email Field -->
                            <div class="grid gap-2">
                                <Label for="email">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="email"
                                    placeholder="seu@email.com"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Password Field -->
                            <div class="grid gap-2">
                                <div class="flex items-center">
                                    <Label for="password">Senha</Label>
                                    <TextLink
                                        v-if="canResetPassword"
                                        :href="request()"
                                        class="ml-auto text-sm underline-offset-2 hover:underline"
                                        :tabindex="5"
                                    >
                                        Esqueceu sua senha?
                                    </TextLink>
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    :tabindex="2"
                                    autocomplete="current-password"
                                    placeholder="Digite sua senha"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Submit Button -->
                            <Button type="submit" class="w-full" :tabindex="4" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Entrar
                            </Button>

                            <!-- Separator -->
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <Separator />
                                </div>
                                <div class="relative flex justify-center text-xs uppercase">
                                    <span class="bg-card px-2 text-muted-foreground">Ou continue com</span>
                                </div>
                            </div>

                            <!-- Google Login (apenas para pacientes) -->
                            <GoogleLoginButton label="Entrar com Google" />

                            <!-- Sign Up Link -->
                            <div class="text-center text-sm text-muted-foreground">
                                Não tem uma conta?
                                <TextLink :href="register()" :tabindex="5">Cadastre-se</TextLink>
                            </div>
                        </div>
                    </form>

                    <!-- Animation Section -->
                    <div class="relative hidden items-center justify-center bg-muted p-8 md:flex">
                        <LottieAnimation src="/animations/Doctor.lottie" :width="350" :height="350" :autoplay="true" :loop="true" />
                    </div>
                </CardContent>
            </Card>

            <!-- Terms and Privacy -->
            <div class="px-6 text-center text-sm text-muted-foreground">
                Ao continuar, você concorda com nossos
                <a href="/terms" target="_blank" class="underline underline-offset-4 hover:text-primary">Termos de Serviço</a>
                e
                <a href="/privacy" target="_blank" class="underline underline-offset-4 hover:text-primary">Política de Privacidade</a>.
            </div>
        </div>
    </AuthBase>
</template>
