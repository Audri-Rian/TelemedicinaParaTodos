import { ref, readonly } from 'vue';

/**
 * Tipos de toast disponíveis. Cada um mapeia para um estilo visual distinto.
 */
export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: number;
    type: ToastType;
    title?: string;
    message: string;
    durationMs: number;
    dismissible: boolean;
}

export interface ToastOptions {
    title?: string;
    /** Duração em ms. Use 0 para não auto-fechar. Default: 5000 (5s) */
    durationMs?: number;
    /** Se o usuário pode fechar manualmente. Default: true */
    dismissible?: boolean;
}

/**
 * Store global de toasts — estado compartilhado entre todas as chamadas.
 * Fica fora da função para que `useToast()` sempre retorne a mesma lista.
 */
const toasts = ref<Toast[]>([]);
/**
 * Map de timers de auto-dismiss, chaveado pelo id do toast. Mantemos aqui (e
 * não dentro de cada toast) para não poluir o shape público de `Toast`.
 * Precisamos rastreá-los para podermos cancelar ao fechar manualmente ou
 * limpar toda a fila — caso contrário, o setTimeout continua "vivo" e dispara
 * um dismiss zombie depois.
 */
const timers = new Map<number, ReturnType<typeof setTimeout>>();
let nextId = 1;

const DEFAULT_DURATION_MS = 5000;

/**
 * Composable de toasts/alertas globais.
 *
 * Uso:
 *   const { success, error, warning, info } = useToast();
 *   error('Credenciais inválidas.');
 *   success('Conta criada!', { title: 'Sucesso', durationMs: 3000 });
 *
 * O container {@link ToastContainer} deve estar presente em algum layout raiz
 * para que os toasts sejam visíveis.
 */
export function useToast() {
    const push = (type: ToastType, message: string, options: ToastOptions = {}): number => {
        const id = nextId++;
        const toast: Toast = {
            id,
            type,
            title: options.title,
            message,
            durationMs: options.durationMs ?? DEFAULT_DURATION_MS,
            dismissible: options.dismissible ?? true,
        };

        toasts.value = [...toasts.value, toast];

        if (toast.durationMs > 0) {
            const handle = setTimeout(() => dismiss(id), toast.durationMs);
            timers.set(id, handle);
        }

        return id;
    };

    const dismiss = (id: number) => {
        const handle = timers.get(id);
        if (handle !== undefined) {
            clearTimeout(handle);
            timers.delete(id);
        }
        toasts.value = toasts.value.filter((t) => t.id !== id);
    };

    const clear = () => {
        timers.forEach((handle) => clearTimeout(handle));
        timers.clear();
        toasts.value = [];
    };

    return {
        toasts: readonly(toasts),
        success: (message: string, options?: ToastOptions) => push('success', message, options),
        error: (message: string, options?: ToastOptions) => push('error', message, options),
        warning: (message: string, options?: ToastOptions) => push('warning', message, options),
        info: (message: string, options?: ToastOptions) => push('info', message, options),
        dismiss,
        clear,
    };
}
