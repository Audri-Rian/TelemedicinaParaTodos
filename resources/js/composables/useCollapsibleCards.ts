import { ref } from 'vue';

export function useCollapsibleCards(initialState: Record<string, boolean>) {
    const collapsedCards = ref<Record<string, boolean>>({ ...initialState });

    function toggleCard(cardId: string) {
        collapsedCards.value[cardId] = !collapsedCards.value[cardId];
    }

    function expandCard(cardId: string) {
        collapsedCards.value[cardId] = false;
    }

    return { collapsedCards, toggleCard, expandCard };
}
