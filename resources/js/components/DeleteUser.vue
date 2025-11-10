<script setup lang="ts">
import { destroy } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = ref<InstanceType<typeof Input> | null>(null);
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Excluir conta" description="Exclua sua conta e todos os seus recursos" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4">
            <div class="relative space-y-0.5 text-red-600">
                <p class="font-medium">Aviso</p>
                <p class="text-sm">Por favor, proceda com cautela, esta ação não pode ser desfeita.</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Excluir conta</Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.$el?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Tem certeza de que deseja excluir sua conta?</DialogTitle>
                            <DialogDescription>
                                Uma vez que sua conta for excluída, todos os seus recursos e dados também serão permanentemente excluídos. Por favor, digite sua
                                senha para confirmar que deseja excluir permanentemente sua conta.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Senha</Label>
                            <Input id="password" type="password" name="password" ref="passwordInput" placeholder="Senha" />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Cancelar
                                </Button>
                            </DialogClose>

                            <Button type="submit" variant="destructive" :disabled="processing"> Excluir conta </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
