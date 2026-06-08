<script setup lang="ts">
import { update as updateRoute } from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/password';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ hasPassword?: boolean }>();

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { CheckCircle2, Laptop, Lock, ShieldCheck, Smartphone, Tablet } from 'lucide-vue-next';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurações de Senha',
        href: edit().url,
    },
];

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const page = usePage();
const auth = computed(() => page.props.auth as { isDoctor?: boolean; role?: string | null } | undefined);
const isDoctor = computed(() => auth.value?.isDoctor === true || auth.value?.role === 'doctor');

const hasPassword = computed(() => props.hasPassword !== false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Formulário para conta social-only (sem senha atual)
const createForm = useForm({
    password: '',
    password_confirmation: '',
});

// Estado para mensagem de sucesso
const recentlySuccessful = ref(false);

const passwordStrength = computed(() => {
    const password = form.password;
    let score = 0;

    if (password.length >= 8) score += 1;
    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score += 1;
    if (/\d/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    if (password.length >= 14) score = Math.min(4, score + 1);

    const labels = ['Muito fraca', 'Fraca', 'Razoável', 'Forte', 'Excelente'];
    const classes = ['text-rose-700', 'text-rose-700', 'text-amber-700', 'text-yellow-700', 'text-emerald-700'];

    return {
        score,
        label: labels[score] ?? labels[0],
        class: classes[score] ?? classes[0],
    };
});

const passwordsMatch = computed(() => !form.password_confirmation || !form.password || form.password === form.password_confirmation);

const activeSessions = [
    {
        id: 'current',
        device: 'Navegador atual',
        location: 'Sessão ativa neste dispositivo',
        current: true,
        icon: Laptop,
    },
    {
        id: 'mobile',
        device: 'Aplicativo Telemedicina',
        location: 'Última atividade há 2 horas',
        current: false,
        icon: Smartphone,
    },
    {
        id: 'tablet',
        device: 'Tablet / Safari',
        location: 'Última atividade há 3 dias',
        current: false,
        icon: Tablet,
    },
];

const submit = () => {
    form.put(updateRoute.url(), {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => {
                recentlySuccessful.value = false;
            }, 2000);
            form.reset('password', 'password_confirmation', 'current_password');
        },
    });
};

const submitCreate = () => {
    createForm.post('/settings/password/create', {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => {
                recentlySuccessful.value = false;
            }, 2000);
            createForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações de Senha" />

        <SettingsLayout>
            <div v-if="isDoctor" class="space-y-5">
                <section id="password" class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Acesso</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Trocar senha</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">Use uma senha forte que você não use em outros sites.</p>
                        </div>
                        <span
                            class="inline-flex h-7 w-fit items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-3 text-xs font-medium text-teal-900"
                        >
                            <Lock class="size-3.5" />
                            Proteção da conta
                        </span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="current_password" class="text-[13px] font-medium text-slate-950">
                                    Senha atual <span class="text-rose-700">*</span>
                                </Label>
                                <Input
                                    id="current_password"
                                    ref="currentPasswordInput"
                                    name="current_password"
                                    type="password"
                                    v-model="form.current_password"
                                    class="h-10 rounded-[9px] border-slate-300 bg-white text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    autocomplete="current-password"
                                    placeholder="Sua senha atual"
                                />
                                <InputError :message="form.errors.current_password" />
                            </div>
                            <div class="hidden md:block" />

                            <div class="grid gap-1.5">
                                <Label for="password" class="text-[13px] font-medium text-slate-950">
                                    Nova senha <span class="text-rose-700">*</span>
                                </Label>
                                <Input
                                    id="password"
                                    ref="passwordInput"
                                    name="password"
                                    type="password"
                                    v-model="form.password"
                                    class="h-10 rounded-[9px] border-slate-300 bg-white text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    autocomplete="new-password"
                                    placeholder="Nova senha"
                                />
                                <div v-if="form.password" class="mt-1 space-y-1">
                                    <div class="flex gap-1">
                                        <span
                                            v-for="index in 4"
                                            :key="index"
                                            :class="[
                                                'h-1 flex-1 rounded-full bg-slate-100',
                                                passwordStrength.score >= index && passwordStrength.score <= 1 ? 'bg-rose-700' : '',
                                                passwordStrength.score >= index && passwordStrength.score === 2 ? 'bg-amber-600' : '',
                                                passwordStrength.score >= index && passwordStrength.score === 3 ? 'bg-yellow-500' : '',
                                                passwordStrength.score >= index && passwordStrength.score >= 4 ? 'bg-emerald-700' : '',
                                            ]"
                                        />
                                    </div>
                                    <p class="text-[11.5px] text-slate-500">
                                        Força:
                                        <span :class="['font-medium', passwordStrength.class]">{{ passwordStrength.label }}</span>
                                    </p>
                                </div>
                                <p v-else class="text-xs text-slate-500">Mínimo 8 caracteres com letras, números e símbolo.</p>
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="password_confirmation" class="text-[13px] font-medium text-slate-950">
                                    Confirmar nova senha <span class="text-rose-700">*</span>
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    v-model="form.password_confirmation"
                                    class="h-10 rounded-[9px] border-slate-300 bg-white text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    autocomplete="new-password"
                                    placeholder="Repita a nova senha"
                                />
                                <p v-if="!passwordsMatch" class="text-xs text-rose-700">As senhas não coincidem.</p>
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="h-9 rounded-[9px] bg-teal-700 px-4 text-[13.5px] font-medium text-white hover:bg-teal-800"
                            >
                                Atualizar senha
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                    <CheckCircle2 class="size-4 text-teal-700" />
                                    Senha atualizada.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </section>

                <section id="2fa" class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-4">
                        <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Segurança</p>
                        <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Verificação em dois passos</h2>
                        <p class="mt-1 text-[13.5px] text-slate-500">Reforce sua conta com uma etapa adicional além da senha.</p>
                    </div>

                    <div class="divide-y divide-slate-200">
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div>
                                <div class="flex items-center gap-2 text-[13.5px] font-medium text-slate-950">
                                    Aplicativo autenticador
                                    <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                        Recomendado
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[12.5px] text-slate-500">Use Google Authenticator, 1Password ou similar.</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 p-0.5"
                                aria-checked="false"
                                role="switch"
                            >
                                <span class="size-4 rounded-full bg-white shadow-sm" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div>
                                <p class="text-[13.5px] font-medium text-slate-950">SMS</p>
                                <p class="mt-0.5 text-[12.5px] text-slate-500">Receber código por mensagem no telefone cadastrado.</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 p-0.5"
                                aria-checked="false"
                                role="switch"
                            >
                                <span class="size-4 rounded-full bg-white shadow-sm" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div>
                                <p class="text-[13.5px] font-medium text-slate-950">Chaves de segurança</p>
                                <p class="mt-0.5 text-[12.5px] text-slate-500">YubiKey, Touch ID, Windows Hello.</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 p-0.5"
                                aria-checked="false"
                                role="switch"
                            >
                                <span class="size-4 rounded-full bg-white shadow-sm" />
                            </button>
                        </div>
                    </div>
                </section>

                <section id="sessions" class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Dispositivos</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Sessões ativas</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">Aparelhos atualmente logados na sua conta.</p>
                        </div>
                        <Button type="button" variant="outline" class="h-8 rounded-[7px] border-slate-300 px-3 text-xs"
                            >Encerrar todas as outras</Button
                        >
                    </div>

                    <div class="divide-y divide-slate-200">
                        <div v-for="session in activeSessions" :key="session.id" class="flex items-center gap-3.5 py-3.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-slate-100 text-slate-500">
                                <component :is="session.icon" class="size-[18px]" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-slate-950">{{ session.device }}</p>
                                    <span
                                        v-if="session.current"
                                        class="rounded-full border border-teal-200 bg-teal-50 px-2 py-0.5 text-[11.5px] font-medium text-teal-900"
                                    >
                                        Este dispositivo
                                    </span>
                                </div>
                                <p class="text-[12.5px] text-slate-500">{{ session.location }}</p>
                            </div>
                            <Button v-if="!session.current" type="button" variant="outline" class="h-8 rounded-[7px] border-slate-300 px-3 text-xs">
                                Encerrar
                            </Button>
                            <ShieldCheck v-else class="size-4 text-teal-700" />
                        </div>
                    </div>
                </section>
            </div>

            <!-- Criar senha (conta social-only) -->
            <div v-else-if="!hasPassword" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <HeadingSmall title="Criar senha" description="Sua conta foi criada via Google. Defina uma senha para ter mais formas de acesso." />
                <form @submit.prevent="submitCreate" class="mt-4 space-y-6">
                    <div class="grid gap-2">
                        <Label for="create_password" class="text-gray-800">Nova senha</Label>
                        <Input
                            id="create_password"
                            type="password"
                            v-model="createForm.password"
                            class="mt-1 block w-full rounded-xl border-primary/30 focus-visible:ring-primary/30"
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                        />
                        <InputError :message="createForm.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create_password_confirmation" class="text-gray-800">Confirmar senha</Label>
                        <Input
                            id="create_password_confirmation"
                            type="password"
                            v-model="createForm.password_confirmation"
                            class="mt-1 block w-full rounded-xl border-primary/30 focus-visible:ring-primary/30"
                            autocomplete="new-password"
                            placeholder="Confirmar senha"
                        />
                        <InputError :message="createForm.errors.password_confirmation" />
                    </div>
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="createForm.processing" class="rounded-xl bg-primary text-white hover:bg-primary/90">
                            Criar senha
                        </Button>
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Senha criada.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <!-- Trocar senha (conta com senha) -->
            <div v-else class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <HeadingSmall
                    title="Senha"
                    description="Certifique-se de que sua conta está usando uma senha longa e aleatória para manter-se segura"
                />
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
