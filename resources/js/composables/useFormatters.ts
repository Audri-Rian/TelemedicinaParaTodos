const _dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const _dateTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export function useFormatters() {
    function formatDate(value?: string | null, withTime = false): string {
        if (!value) return '—';
        try {
            return (withTime ? _dateTimeFormatter : _dateFormatter).format(new Date(value));
        } catch {
            return value;
        }
    }

    function formatGender(gender: string): string {
        const map: Record<string, string> = { male: 'Masculino', female: 'Feminino', other: 'Outro' };
        return map[gender] ?? gender;
    }

    function formatStatus(status?: string | null): string {
        if (!status) return '—';
        return status.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function formatFileSize(size?: number | null): string {
        if (!size) return '—';
        if (size < 1024 * 1024) return `${Math.round(size / 1024)} KB`;
        return `${(size / 1024 / 1024).toFixed(1)} MB`;
    }

    function getDocumentUrl(path: string): string {
        return `/storage/${path}`;
    }

    function isSafeUrl(url?: string | null): boolean {
        if (!url) return false;
        try {
            const parsed = new URL(url, window.location.href);
            return parsed.protocol === 'https:' || parsed.protocol === 'http:';
        } catch {
            return false;
        }
    }

    function formatDatePortuguese(dateString: string): string {
        if (!dateString) return '';
        const date = new Date(dateString);
        const months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        const day = String(date.getDate()).padStart(2, '0');
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day} de ${month} de ${year} às ${hours}:${minutes}`;
    }

    function formatTime(date: Date): string {
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${hours}:${minutes}:${seconds}`;
    }

    return { formatDate, formatGender, formatStatus, formatFileSize, getDocumentUrl, isSafeUrl, formatDatePortuguese, formatTime };
}
