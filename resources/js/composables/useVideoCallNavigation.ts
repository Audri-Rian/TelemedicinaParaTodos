import { useVideoCallStore } from '@/stores/videoCall';
import { router } from '@inertiajs/vue3';

export function useVideoCallNavigation() {
    const store = useVideoCallStore();

    function enterCall(): void {
        const target = store.videoCallRoute;
        if (target) {
            router.visit(target);
        } else {
            const fallback = store.role === 'doctor' ? '/doctor/video-call' : '/patient/video-call';
            router.visit(fallback);
        }
    }

    return { enterCall };
}
