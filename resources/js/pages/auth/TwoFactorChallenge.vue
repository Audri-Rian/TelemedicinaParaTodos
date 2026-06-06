<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTwoFactor } from '@/composables/auth/useTwoFactor';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { LoaderCircle, ShieldCheck } from 'lucide-vue-next';

defineProps<{
    recoveryAvailable: boolean;
}>();

const { form, useRecovery, toggleRecovery, submit } = useTwoFactor();
</script>

<template>
    <AuthBase title="" description="">
        <Head title="Verificação em dois fatores" />

        <div class="mx-auto w-full max-w-sm space-y-6">
            <div class="flex flex-col items-center gap-2 text-center">
                <span class="grid h-12 w-12 place-items-center rounded-full bg-teal-50">
                    <ShieldCheck class="h-6 w-6 text-teal-700" />
                </span>
                <h1 class="text-xl font-bold text-gray-900">
                    {{ useRecovery ? 'Código de recuperação' : 'Verificação em dois fatores' }}
                </h1>
                <p class="text-sm text-gray-500">
                    <template v-if="useRecovery"> Insira um dos seus códigos de recuperação de 8 caracteres. </template>
                    <template v-else> Insira o código de 6 dígitos do seu aplicativo autenticador. </template>
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="grid gap-1.5">
                    <Label for="code">{{ useRecovery ? 'Código de recuperação' : 'Código de autenticação' }}</Label>
                    <Input
                        id="code"
                        v-model="form.code"
                        :type="useRecovery ? 'text' : 'text'"
                        :placeholder="useRecovery ? 'xxxxxxxxxx-xxxxxxxxxx' : '000000'"
                        :inputmode="useRecovery ? undefined : 'numeric'"
                        :maxlength="useRecovery ? 21 : 6"
                        autocomplete="one-time-code"
                        autofocus
                    />
                    <InputError :message="form.errors.code" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Verificar
                </Button>
            </form>

            <div class="text-center">
                <button
                    v-if="recoveryAvailable || useRecovery"
                    type="button"
                    class="text-sm text-teal-700 underline-offset-4 hover:underline"
                    @click="toggleRecovery"
                >
                    {{ useRecovery ? 'Usar código do autenticador' : 'Usar código de recuperação' }}
                </button>
            </div>

            <div class="text-center">
                <a href="/login" class="text-sm text-gray-500 hover:text-gray-700">Voltar ao login</a>
            </div>
        </div>
    </AuthBase>
</template>
