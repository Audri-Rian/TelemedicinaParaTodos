<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    enabled: boolean;
    hasPassword: boolean;
    qrSvg?: string;
    setupKey?: string;
    recoveryCodes?: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Verificação em dois fatores', href: '/settings/two-factor' }];

const page = usePage();
const flashRecoveryCodes = computed(() => (page.props.flash as Record<string, unknown>)?.recoveryCodes as string[] | undefined);
const displayCodes = computed(() => props.recoveryCodes ?? flashRecoveryCodes.value);
const flashStatus = computed(() => (page.props.flash as Record<string, unknown>)?.status as string | undefined);

const confirmForm = useForm({ code: '' });

const startEnable = () => {
    window.location.href = '/settings/two-factor/enable';
};

const confirmCode = () => {
    confirmForm.post('/settings/two-factor/confirm', {
        onSuccess: () => confirmForm.reset('code'),
    });
};

const disable = () => {
    if (!confirm('Tem certeza que deseja desativar a verificação em dois fatores?')) return;
    useForm({}).delete('/settings/two-factor');
};

const regenerateCodes = () => {
    useForm({}).post('/settings/two-factor/recovery-codes');
};

const copyCode = (code: string) => {
    navigator.clipboard.writeText(code);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Verificação em dois fatores" />
        <SettingsLayout>
            <div class="space-y-5">
                <!-- Aviso: sem senha -->
                <div v-if="!hasPassword" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <strong>Atenção:</strong> Para ativar o 2FA você precisa primeiro
                    <a href="/settings/password" class="font-semibold underline">criar uma senha</a> para sua conta.
                </div>

                <!-- Status card -->
                <section class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Segurança</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">Verificação em dois fatores</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">
                                Adicione uma camada extra de segurança usando um aplicativo autenticador (Google Authenticator, Authy, etc.).
                            </p>
                        </div>
                        <span
                            :class="[
                                'inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium',
                                enabled ? 'border border-teal-200 bg-teal-50 text-teal-900' : 'border border-slate-200 bg-slate-50 text-slate-600',
                            ]"
                        >
                            <ShieldCheck v-if="enabled" class="h-3.5 w-3.5" />
                            <ShieldOff v-else class="h-3.5 w-3.5" />
                            {{ enabled ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    <!-- Flash status -->
                    <div v-if="flashStatus" class="mt-4 flex items-center gap-2 text-sm text-teal-700">
                        <CheckCircle2 class="h-4 w-4" />
                        {{ flashStatus }}
                    </div>

                    <div class="mt-5 flex gap-3">
                        <template v-if="!enabled && !qrSvg">
                            <Button :disabled="!hasPassword" @click="startEnable" size="sm"> Ativar 2FA </Button>
                        </template>
                        <template v-else-if="enabled">
                            <Button variant="destructive" size="sm" @click="disable">Desativar</Button>
                        </template>
                    </div>
                </section>

                <!-- Setup: QR code + confirm -->
                <section v-if="qrSvg && !enabled" class="rounded-[14px] border border-teal-200 bg-teal-50/40 px-5 py-6 shadow-xs sm:px-7">
                    <h3 class="mb-4 font-semibold text-slate-950">Configure seu aplicativo autenticador</h3>

                    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="inline-block rounded-xl border border-slate-200 bg-white p-2 shadow-xs" v-html="qrSvg" />
                        <div class="space-y-2 text-sm text-slate-600">
                            <p>1. Abra seu aplicativo autenticador.</p>
                            <p>2. Escaneie o QR code ou insira a chave manualmente:</p>
                            <code class="block rounded bg-slate-100 px-2 py-1 font-mono text-xs break-all text-slate-700">{{ setupKey }}</code>
                            <p>3. Insira o código gerado abaixo para confirmar.</p>
                        </div>
                    </div>

                    <form @submit.prevent="confirmCode" class="flex items-end gap-3">
                        <div class="grid gap-1.5">
                            <Label for="confirm_code">Código de confirmação</Label>
                            <Input
                                id="confirm_code"
                                v-model="confirmForm.code"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="000000"
                                autofocus
                                class="w-36"
                            />
                            <InputError :message="confirmForm.errors.code" />
                        </div>
                        <Button type="submit" :disabled="confirmForm.processing" size="sm">Confirmar</Button>
                    </form>
                </section>

                <!-- Recovery codes -->
                <section v-if="displayCodes?.length" class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-950">Códigos de recuperação</h3>
                            <p class="mt-1 text-[13px] text-slate-500">
                                Guarde estes códigos em local seguro. Cada um pode ser usado uma única vez se você perder acesso ao autenticador.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" @click="regenerateCodes">Regenerar</Button>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        <button
                            v-for="code in displayCodes"
                            :key="code"
                            type="button"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 font-mono text-xs text-slate-700 hover:bg-slate-100"
                            :title="`Copiar ${code}`"
                            @click="copyCode(code)"
                        >
                            {{ code }}
                        </button>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
