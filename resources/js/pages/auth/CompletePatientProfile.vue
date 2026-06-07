<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    name: string;
    email: string;
    avatarUrl?: string | null;
}>();

const form = useForm({
    gender: '',
    date_of_birth: '',
    phone_number: '',
    consent_telemedicine: false,
});

const applyDateMask = (value: string) => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 2) return numbers;
    if (numbers.length <= 4) return `${numbers.slice(0, 2)}/${numbers.slice(2)}`;
    return `${numbers.slice(0, 2)}/${numbers.slice(2, 4)}/${numbers.slice(4, 8)}`;
};

const handleDateInput = (value: string | number) => {
    form.date_of_birth = applyDateMask(String(value));
};

const toIsoDate = (masked: string) => {
    const parts = masked.split('/');
    if (parts.length !== 3 || parts[2].length !== 4) return masked;
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
};

const submit = () => {
    form.transform((data) => ({ ...data, date_of_birth: toIsoDate(data.date_of_birth) })).post('/register/patient/complete');
};
</script>

<template>
    <AuthBase title="" description="">
        <Head title="Completar Perfil" />

        <div class="mx-auto w-full max-w-lg space-y-6">
            <div class="text-center">
                <img v-if="props.avatarUrl" :src="props.avatarUrl" :alt="props.name" class="mx-auto mb-3 h-16 w-16 rounded-full object-cover" />
                <h1 class="text-2xl font-bold text-gray-900">Quase lá, {{ props.name.split(' ')[0] }}!</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Precisamos de mais alguns dados para criar seu prontuário.<br />
                    <span class="font-medium text-gray-700">{{ props.email }}</span>
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="date_of_birth">Data de nascimento</Label>
                        <Input
                            id="date_of_birth"
                            type="text"
                            placeholder="12/05/1992"
                            maxlength="10"
                            :model-value="form.date_of_birth"
                            @update:model-value="handleDateInput"
                            autocomplete="bday"
                        />
                        <InputError :message="form.errors.date_of_birth" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="gender">Gênero</Label>
                        <select
                            id="gender"
                            v-model="form.gender"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Selecione</option>
                            <option value="male">Masculino</option>
                            <option value="female">Feminino</option>
                            <option value="other">Outro</option>
                        </select>
                        <InputError :message="form.errors.gender" />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="phone_number">Telefone</Label>
                    <Input id="phone_number" type="tel" placeholder="(11) 98765-4321" v-model="form.phone_number" autocomplete="tel" />
                    <InputError :message="form.errors.phone_number" />
                </div>

                <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-3">
                    <input
                        id="consent_telemedicine"
                        type="checkbox"
                        v-model="form.consent_telemedicine"
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-teal-700 focus:ring-teal-500"
                    />
                    <label for="consent_telemedicine" class="cursor-pointer text-sm leading-relaxed text-gray-600">
                        Concordo com os
                        <a href="/terms" target="_blank" class="font-semibold text-teal-700 hover:text-teal-800">Termos de Serviço</a>
                        e autorizo o uso da telemedicina conforme a LGPD.
                    </label>
                </div>
                <InputError :message="form.errors.consent_telemedicine" />

                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Concluir cadastro
                </Button>
            </form>
        </div>
    </AuthBase>
</template>
