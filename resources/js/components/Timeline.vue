<script setup lang="ts">
import { computed } from 'vue';
import { GraduationCap, BookOpen, Award, Briefcase, Calendar, MapPin, FileText, ExternalLink } from 'lucide-vue-next';

interface TimelineEvent {
    id: string;
    type: 'education' | 'course' | 'certificate' | 'project';
    type_label: string;
    title: string;
    subtitle?: string;
    start_date: string;
    end_date?: string;
    formatted_start_date: string;
    formatted_end_date?: string;
    date_range: string;
    duration?: string;
    description?: string;
    media_url?: string;
    extra_data?: Record<string, any>;
    order_priority: number;
    is_in_progress: boolean;
}

interface Props {
    events: TimelineEvent[];
    showActions?: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: false,
    loading: false,
});

const emit = defineEmits<{
    (event: 'edit', eventId: string): void;
    (event: 'delete', eventId: string): void;
}>();

// Ícones por tipo
const getTypeIcon = (type: string) => {
    switch (type) {
        case 'education':
            return GraduationCap;
        case 'course':
            return BookOpen;
        case 'certificate':
            return Award;
        case 'project':
            return Briefcase;
        default:
            return Calendar;
    }
};

// Cores por tipo
const getTypeColor = (type: string) => {
    switch (type) {
        case 'education':
            return 'bg-blue-100 text-blue-700 border-blue-300';
        case 'course':
            return 'bg-green-100 text-green-700 border-green-300';
        case 'certificate':
            return 'bg-yellow-100 text-yellow-700 border-yellow-300';
        case 'project':
            return 'bg-purple-100 text-purple-700 border-purple-300';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-300';
    }
};

// Badge de status
const getStatusBadge = (event: TimelineEvent) => {
    if (event.is_in_progress) {
        return {
            text: 'Em andamento',
            class: 'bg-primary/10 text-primary border-primary/30',
        };
    }
    return null;
};

// Ordenar eventos por prioridade e data
const sortedEvents = computed(() => {
    return [...props.events].sort((a, b) => {
        // Primeiro por order_priority (maior primeiro)
        if (a.order_priority !== b.order_priority) {
            return b.order_priority - a.order_priority;
        }
        // Depois por start_date (mais recente primeiro)
        const dateA = new Date(a.start_date);
        const dateB = new Date(b.start_date);
        return dateB.getTime() - dateA.getTime();
    });
});

const handleEdit = (eventId: string) => {
    emit('edit', eventId);
};

const handleDelete = (eventId: string) => {
    if (confirm('Tem certeza que deseja deletar este evento?')) {
        emit('delete', eventId);
    }
};
</script>

<template>
    <div class="space-y-6">
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="sortedEvents.length === 0" class="text-center py-12 text-gray-500">
            <Calendar class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>Nenhum evento registrado ainda.</p>
        </div>

        <div v-else class="relative">
            <!-- Linha vertical da timeline -->
            <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            <!-- Lista de eventos -->
            <div class="space-y-8">
                <div
                    v-for="(event, index) in sortedEvents"
                    :key="event.id"
                    class="relative flex gap-4"
                >
                    <!-- Ícone do tipo -->
                    <div
                        :class="[
                            'relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2',
                            getTypeColor(event.type)
                        ]"
                    >
                        <component :is="getTypeIcon(event.type)" class="h-5 w-5" />
                    </div>

                    <!-- Conteúdo do evento -->
                    <div class="flex-1 min-w-0 pb-8">
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
                            <!-- Header -->
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1 min-w-0">
                                    <!-- Tipo e Título -->
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border',
                                                getTypeColor(event.type)
                                            ]"
                                        >
                                            {{ event.type_label }}
                                        </span>
                                        <h3 class="text-lg font-bold text-gray-900 truncate">
                                            {{ event.title }}
                                        </h3>
                                    </div>

                                    <!-- Subtítulo (Instituição/Empresa) -->
                                    <div v-if="event.subtitle" class="flex items-center gap-1 text-sm text-gray-600 mb-2">
                                        <MapPin class="w-4 h-4" />
                                        <span>{{ event.subtitle }}</span>
                                    </div>

                                    <!-- Data e Status -->
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <div class="flex items-center gap-1 text-sm text-gray-500">
                                            <Calendar class="w-4 h-4" />
                                            <span>{{ event.date_range }}</span>
                                        </div>
                                        <span
                                            v-if="getStatusBadge(event)"
                                            :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border',
                                                getStatusBadge(event)?.class
                                            ]"
                                        >
                                            {{ getStatusBadge(event)?.text }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Ações (se habilitado) -->
                                <div v-if="showActions" class="flex items-center gap-2">
                                    <button
                                        @click="handleEdit(event.id)"
                                        class="p-1.5 text-gray-400 hover:text-primary transition-colors"
                                        title="Editar evento"
                                    >
                                        <FileText class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="handleDelete(event.id)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                                        title="Deletar evento"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Descrição -->
                            <p v-if="event.description" class="text-sm text-gray-700 mb-3">
                                {{ event.description }}
                            </p>

                            <!-- Extra Data -->
                            <div v-if="event.extra_data && Object.keys(event.extra_data).length > 0" class="mb-3">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(value, key) in event.extra_data"
                                        :key="key"
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700"
                                    >
                                        <span class="font-semibold mr-1">{{ key }}:</span>
                                        <span>{{ Array.isArray(value) ? value.join(', ') : value }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Mídia (Certificado, PDF, etc.) -->
                            <div v-if="event.media_url" class="mt-3 pt-3 border-t border-gray-200">
                                <a
                                    :href="event.media_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-sm text-primary hover:text-primary/80 transition-colors"
                                >
                                    <ExternalLink class="w-4 h-4" />
                                    <span>Ver certificado/documento</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>



