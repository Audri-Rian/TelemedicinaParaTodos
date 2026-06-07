import type { CallDocumentVisibility, CallSharedDocument } from '@/types/call-documents';
import { echo } from '@laravel/echo-vue';
import { onUnmounted, ref, watch, type Ref } from 'vue';

interface UseCallSharedDocumentsOptions {
    isInCall: () => boolean;
    appointmentId: () => string | null | undefined;
    initialDocuments: () => CallSharedDocument[] | undefined;
    hiddenVisibility: CallDocumentVisibility;
    onNewDocument?: (document: CallSharedDocument) => void;
}

export function useCallSharedDocuments(options: UseCallSharedDocumentsOptions): { documents: Ref<CallSharedDocument[]> } {
    const documents = ref<CallSharedDocument[]>([]);
    let channelName: string | null = null;

    const upsert = (doc: CallSharedDocument, notify: boolean) => {
        if (doc.visibility === options.hiddenVisibility) return;

        const index = documents.value.findIndex((existing) => existing.id === doc.id);
        if (index >= 0) {
            documents.value.splice(index, 1, { ...documents.value[index], ...doc });
        } else {
            documents.value.unshift(doc);
            if (notify) options.onNewDocument?.(doc);
        }
    };

    const seedFromProps = () => {
        (options.initialDocuments() ?? []).forEach((doc) => upsert(doc, false));
    };

    const subscribe = () => {
        const appointmentId = options.appointmentId();
        const echoInstance = echo();
        if (!appointmentId || !echoInstance || channelName) return;

        channelName = `appointments.${appointmentId}`;
        echoInstance.private(channelName).listen('.medical-document.shared', (payload: CallSharedDocument) => {
            upsert(payload, true);
        });
    };

    const unsubscribe = () => {
        if (!channelName) return;
        const echoInstance = echo();
        echoInstance?.private(channelName).stopListening('.medical-document.shared');
        echoInstance?.leave(channelName);
        channelName = null;
    };

    watch(
        () => options.isInCall(),
        (active) => {
            if (active) {
                documents.value = [];
                seedFromProps();
                subscribe();
            } else {
                unsubscribe();
            }
        },
        { immediate: true },
    );

    watch(
        () => options.initialDocuments(),
        () => {
            if (options.isInCall()) seedFromProps();
        },
    );

    onUnmounted(unsubscribe);

    return { documents };
}
