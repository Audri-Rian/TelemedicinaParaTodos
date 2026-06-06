import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useTwoFactor() {
    const useRecovery = ref(false);

    const form = useForm({
        code: '',
    });

    const toggleRecovery = () => {
        useRecovery.value = !useRecovery.value;
        form.reset('code');
    };

    const submit = () => {
        form.post('/auth/two-factor/challenge', {
            onFinish: () => form.reset('code'),
        });
    };

    return { form, useRecovery, toggleRecovery, submit };
}
