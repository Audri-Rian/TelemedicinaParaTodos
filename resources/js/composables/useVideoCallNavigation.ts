import { useVideoCallStore } from '@/stores/videoCall';
import { router } from '@inertiajs/vue3';

export function isSafeInternalPath(path: string): boolean {
    if (!path.startsWith('/') || path.startsWith('//')) {
        return false;
    }

    try {
        const url = new URL(path, window.location.origin);

        return url.origin === window.location.origin;
    } catch {
        return false;
    }
}

export function useVideoCallNavigation() {
    const store = useVideoCallStore();

    function enterCall(): void {
        const target = store.videoCallRoute;
        const fallback = store.role === 'doctor' ? '/doctor/video-call' : '/patient/video-call';

        if (target && isSafeInternalPath(target)) {
            router.visit(target);
        } else {
            router.visit(fallback);
        }
    }

    return { enterCall };
}
