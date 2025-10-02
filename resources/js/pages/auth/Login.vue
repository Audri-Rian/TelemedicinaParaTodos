<script setup lang="ts">
import { useLogin } from '@/composables/useLogin';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// Composables
const {
    formData,
    isSubmitting,
    hasErrors,
    canSubmit,
    submitError,
    rateLimit,
    updateField,
    touchField,
    submitForm,
    getFieldError,
    hasFieldError,
    isFieldTouched
} = useLogin();
</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Mensagem de erro de rate limit -->
        <div v-if="submitError" 
            class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"
            role="alert"
            aria-live="assertive">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ submitError }}
            </div>
        </div>

        <form @submit.prevent="submitForm" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        :model-value="formData.email"
                        @update:model-value="updateField('email', $event)"
                        @blur="touchField('email')"
                        :class="[
                            hasFieldError('email') && isFieldTouched('email')
                                ? 'border-red-500 focus:border-red-500' 
                                : ''
                        ]"
                        :aria-invalid="hasFieldError('email') && isFieldTouched('email')"
                        :aria-describedby="hasFieldError('email') && isFieldTouched('email') ? 'email-error' : undefined"
                    />
                    <InputError 
                        v-if="hasFieldError('email') && isFieldTouched('email')"
                        :message="getFieldError('email')" 
                        id="email-error"
                    />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink v-if="canResetPassword" :href="request()" class="text-sm" :tabindex="3"> 
                            Forgot password? 
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                        :model-value="formData.password"
                        @update:model-value="updateField('password', $event)"
                        @blur="touchField('password')"
                        :class="[
                            hasFieldError('password') && isFieldTouched('password')
                                ? 'border-red-500 focus:border-red-500' 
                                : ''
                        ]"
                        :aria-invalid="hasFieldError('password') && isFieldTouched('password')"
                        :aria-describedby="hasFieldError('password') && isFieldTouched('password') ? 'password-error' : undefined"
                    />
                    <InputError 
                        v-if="hasFieldError('password') && isFieldTouched('password')"
                        :message="getFieldError('password')" 
                        id="password-error"
                    />
                </div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="!canSubmit">
                    <LoaderCircle v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                    {{ isSubmitting ? 'Logging in...' : 'Log in' }}
                </Button>
            </div>

            <!-- Aviso de rate limit -->
            <div v-if="rateLimit.remainingAttempts < 3 && rateLimit.remainingAttempts > 0" 
                class="text-sm text-orange-600 text-center"
                role="alert"
                aria-live="polite">
                ⚠️ Restam {{ rateLimit.remainingAttempts }} tentativa{{ rateLimit.remainingAttempts > 1 ? 's' : '' }}
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
