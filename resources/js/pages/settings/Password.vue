<script setup lang="ts">
import { update as updateRoute } from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/password';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurações de Senha',
        href: edit().url,
    },
];

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

// Criar o formulário usando useForm do Inertia
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Estado para mensagem de sucesso
const recentlySuccessful = ref(false);

// Função para enviar o formulário
const submit = () => {
    form.put(updateRoute.url(), {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => {
                recentlySuccessful.value = false;
            }, 2000);
            // Resetar apenas os campos de senha
            form.reset('password', 'password_confirmation', 'current_password');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações de Senha" />

        <SettingsLayout>
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <HeadingSmall title="Senha" description="Certifique-se de que sua conta está usando uma senha longa e aleatória para manter-se segura" />
                <form @submit.prevent="submit" class="mt-4 space-y-6">
                    <div class="grid gap-2">
                        <Label for="current_password" class="text-gray-800">Senha atual</Label>
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            name="current_password"
                            type="password"
                            v-model="form.current_password"
                            class="mt-1 block w-full rounded-xl border-primary/30 focus-visible:ring-primary/30"
                            autocomplete="current-password"
                            placeholder="Senha atual"
                        />
                        <InputError :message="form.errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password" class="text-gray-800">Nova senha</Label>
                        <Input
                            id="password"
                            ref="passwordInput"
                            name="password"
                            type="password"
                            v-model="form.password"
                            class="mt-1 block w-full rounded-xl border-primary/30 focus-visible:ring-primary/30"
                            autocomplete="new-password"
                            placeholder="Nova senha"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-gray-800">Confirmar senha</Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            v-model="form.password_confirmation"
                            class="mt-1 block w-full rounded-xl border-primary/30 focus-visible:ring-primary/30"
                            autocomplete="new-password"
                            placeholder="Confirmar senha"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing" class="rounded-xl bg-primary text-white hover:bg-primary/90">
                            Salvar Alterações
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Salvo.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
