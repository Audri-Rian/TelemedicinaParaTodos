import { onMounted, onUnmounted, type Ref } from 'vue';

export function useConsultationHotkeys(collapsedCards: Ref<Record<string, boolean>>, expandCard: (id: string) => void, saveDraft: () => void) {
    const cardKeyMap: Record<string, string> = {
        d: 'diagnosis',
        p: 'prescription',
        x: 'examinations',
        q: 'chief_complaint',
    };

    const domIdMap: Record<string, string> = {
        d: 'diagnosis-card',
        p: 'prescription-card',
        x: 'examinations-card',
        q: 'chief-complaint-card',
    };

    const handleKeyDown = (e: KeyboardEvent) => {
        if (!e.ctrlKey && !e.metaKey) return;

        const cardId = cardKeyMap[e.key];
        if (cardId) {
            e.preventDefault();
            expandCard(cardId);
            document.getElementById(domIdMap[e.key])?.scrollIntoView({ behavior: 'smooth' });
            return;
        }

        if (e.key === 's') {
            e.preventDefault();
            saveDraft();
        }
    };

    onMounted(() => window.addEventListener('keydown', handleKeyDown));
    onUnmounted(() => window.removeEventListener('keydown', handleKeyDown));
}
