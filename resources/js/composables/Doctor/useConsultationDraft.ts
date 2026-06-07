import type { AutoSaveStatus } from '@/types/consultation-detail';
import { onUnmounted, ref, type ComputedRef } from 'vue';

interface DraftForm {
    post: (url: string, options: object) => void;
}

export function useConsultationDraft(form: DraftForm, isInProgress: ComputedRef<boolean>, getUrl: () => string) {
    const isSaving = ref(false);
    const autoSaveStatus = ref<AutoSaveStatus>('idle');
    const hasUnsavedChanges = ref(false);
    const lastSaved = ref<Date | null>(null);
    const showSuccessNotification = ref(false);

    let autoSaveTimer: ReturnType<typeof setInterval> | null = null;
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    let notificationTimer: ReturnType<typeof setTimeout> | null = null;
    let statusResetTimer: ReturnType<typeof setTimeout> | null = null;

    const saveDraft = async (isAutoSave = false) => {
        if (isSaving.value) return;
        if (isAutoSave && !hasUnsavedChanges.value) return;

        isSaving.value = true;
        autoSaveStatus.value = 'saving';

        try {
            await form.post(getUrl(), {
                preserveScroll: true,
                preserveState: true,
                only: [],
                onSuccess: () => {
                    lastSaved.value = new Date();
                    hasUnsavedChanges.value = false;
                    autoSaveStatus.value = 'saved';

                    if (!isAutoSave) {
                        showSuccessNotification.value = true;
                        if (notificationTimer) clearTimeout(notificationTimer);
                        notificationTimer = setTimeout(() => (showSuccessNotification.value = false), 3000);
                    }

                    if (statusResetTimer) clearTimeout(statusResetTimer);
                    statusResetTimer = setTimeout(() => {
                        if (autoSaveStatus.value === 'saved') autoSaveStatus.value = 'idle';
                    }, 2000);
                },
                onError: () => {
                    autoSaveStatus.value = 'error';
                    if (statusResetTimer) clearTimeout(statusResetTimer);
                    statusResetTimer = setTimeout(() => (autoSaveStatus.value = 'idle'), 3000);
                },
            });
        } catch {
            autoSaveStatus.value = 'error';
            if (statusResetTimer) clearTimeout(statusResetTimer);
            statusResetTimer = setTimeout(() => (autoSaveStatus.value = 'idle'), 3000);
        } finally {
            isSaving.value = false;
        }
    };

    const triggerAutoSave = () => {
        hasUnsavedChanges.value = true;
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            if (isInProgress.value && hasUnsavedChanges.value) saveDraft(true);
        }, 3000);
    };

    const startAutoSave = () => {
        if (autoSaveTimer) clearInterval(autoSaveTimer);
        autoSaveTimer = setInterval(() => {
            if (isInProgress.value && hasUnsavedChanges.value && !isSaving.value) saveDraft(true);
        }, 30000);
    };

    const stopAutoSave = () => {
        if (autoSaveTimer) {
            clearInterval(autoSaveTimer);
            autoSaveTimer = null;
        }
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        if (notificationTimer) {
            clearTimeout(notificationTimer);
            notificationTimer = null;
        }
        if (statusResetTimer) {
            clearTimeout(statusResetTimer);
            statusResetTimer = null;
        }
    };

    onUnmounted(() => {
        stopAutoSave();
        // Fire-and-forget: best-effort save on unmount; navigator.sendBeacon would be safer
        // but requires a dedicated plain-body endpoint. Void suppresses the floating promise.
        if (hasUnsavedChanges.value && isInProgress.value) void saveDraft(true);
    });

    return {
        isSaving,
        autoSaveStatus,
        hasUnsavedChanges,
        lastSaved,
        showSuccessNotification,
        saveDraft,
        triggerAutoSave,
        startAutoSave,
        stopAutoSave,
    };
}
