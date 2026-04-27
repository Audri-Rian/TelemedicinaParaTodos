import { computed, onBeforeUnmount, ref } from 'vue';

export type ViewStatus = 'idle' | 'loading' | 'success' | 'error';

interface UseLoadStateOptions {
    hasInitialData?: boolean;
    skeletonDelayMs?: number;
    defaultErrorMessage?: string;
}

interface StartLoadingOptions {
    forceSkeleton?: boolean;
    minLoadingMs?: number;
}

export function useLoadState(options: UseLoadStateOptions = {}) {
    const { hasInitialData = false, skeletonDelayMs = 150, defaultErrorMessage = 'Não foi possível carregar os dados.' } = options;

    const status = ref<ViewStatus>(hasInitialData ? 'success' : 'idle');
    const showSkeleton = ref(false);
    const hasResolvedInitialLoad = ref(hasInitialData);
    const errorMessage = ref(defaultErrorMessage);

    let skeletonDelayTimer: ReturnType<typeof setTimeout> | null = null;
    let loadingCycleOngoing = false;
    let loadingStartedAt: number | null = null;
    let minLoadingMs = 0;

    const isLoading = computed(() => status.value === 'loading');
    const isError = computed(() => status.value === 'error');
    const isSuccess = computed(() => status.value === 'success');

    const clearSkeletonDelay = () => {
        if (skeletonDelayTimer) {
            clearTimeout(skeletonDelayTimer);
            skeletonDelayTimer = null;
        }
    };

    const startLoading = (startOptions: StartLoadingOptions = {}) => {
        const { forceSkeleton = false, minLoadingMs: nextMinLoadingMs = 0 } = startOptions;

        status.value = 'loading';
        loadingCycleOngoing = true;
        loadingStartedAt = Date.now();
        minLoadingMs = nextMinLoadingMs;
        clearSkeletonDelay();

        if (forceSkeleton) {
            showSkeleton.value = true;
            return;
        }

        // Exibe skeleton apenas na primeira carga e com atraso para evitar flicker.
        if (!hasResolvedInitialLoad.value) {
            skeletonDelayTimer = setTimeout(() => {
                if (loadingCycleOngoing && status.value === 'loading') {
                    showSkeleton.value = true;
                }
            }, skeletonDelayMs);
            return;
        }

        showSkeleton.value = false;
    };

    const completeSuccess = () => {
        const elapsedMs = loadingStartedAt ? Date.now() - loadingStartedAt : 0;
        const remainingMs = Math.max(0, minLoadingMs - elapsedMs);

        if (remainingMs > 0) {
            setTimeout(completeSuccess, remainingMs);
            return;
        }

        loadingCycleOngoing = false;
        clearSkeletonDelay();
        showSkeleton.value = false;
        status.value = 'success';
        hasResolvedInitialLoad.value = true;
        loadingStartedAt = null;
        minLoadingMs = 0;
    };

    const completeError = (message?: string) => {
        loadingCycleOngoing = false;
        clearSkeletonDelay();
        showSkeleton.value = false;
        status.value = 'error';
        errorMessage.value = message ?? defaultErrorMessage;
        loadingStartedAt = null;
        minLoadingMs = 0;
    };

    const cleanup = () => {
        clearSkeletonDelay();
        loadingCycleOngoing = false;
        loadingStartedAt = null;
        minLoadingMs = 0;
    };

    onBeforeUnmount(cleanup);

    return {
        status,
        showSkeleton,
        hasResolvedInitialLoad,
        errorMessage,
        isLoading,
        isError,
        isSuccess,
        startLoading,
        completeSuccess,
        completeError,
        cleanup,
    };
}
