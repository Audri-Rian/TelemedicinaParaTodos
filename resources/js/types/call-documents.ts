export type CallDocumentCategory = 'exam' | 'prescription' | 'report' | 'other';
export type CallDocumentVisibility = 'patient' | 'doctor' | 'shared';

export interface CallSharedDocument {
    id: string;
    category: CallDocumentCategory;
    name: string;
    file_type: string | null;
    file_size: number | null;
    visibility: CallDocumentVisibility;
    created_at: string;
    download_url?: string;
    view_url?: string;
}

export const CALL_DOCUMENT_CATEGORY_LABELS: Record<CallDocumentCategory, string> = {
    exam: 'Solicitação de exame',
    prescription: 'Prescrição médica',
    report: 'Laudo médico',
    other: 'Documento da consulta',
};

export function formatCallDocumentSize(bytes: number | null): string {
    if (!bytes || bytes <= 0) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function formatCallDocumentTime(isoDate: string): string {
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return '—';
    const diffMinutes = Math.floor((Date.now() - date.getTime()) / 60_000);
    if (diffMinutes < 1) return 'agora';
    if (diffMinutes < 60) return `há ${diffMinutes} min`;
    return (
        date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) +
        ' ' +
        date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
    );
}
