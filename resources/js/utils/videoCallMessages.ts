/**
 * Catálogo de mensagens da videochamada e mapeamento de erros HTTP → texto amigável.
 *
 * Regra de UX (RF-05): nunca exibir código HTTP, stack trace ou mensagem técnica
 * crua do backend. Todo feedback ao usuário passa por aqui.
 */

export type VideoCallMessageKey =
    | 'call.ended.generic'
    | 'call.ended.by_doctor'
    | 'call.ended.time_expired'
    | 'call.ended.inactivity'
    | 'call.ended.doctor_unavailable'
    | 'call.left.patient'
    | 'call.left.doctor'
    | 'call.left.self'
    | 'doctor.reconnecting'
    | 'connection.reconnecting'
    | 'connection.failed';

const MESSAGES: Record<VideoCallMessageKey, string> = {
    'call.ended.generic': 'A consulta foi encerrada.',
    'call.ended.by_doctor': 'O médico encerrou a chamada.',
    'call.ended.time_expired': 'O tempo da consulta foi finalizado.',
    'call.ended.inactivity': 'A reunião foi encerrada por inatividade.',
    'call.ended.doctor_unavailable': 'O médico não conseguiu retornar à chamada.',
    'call.left.patient': 'O paciente saiu da chamada.',
    'call.left.doctor': 'O médico saiu temporariamente da chamada.',
    'call.left.self': 'Você saiu da consulta.',
    'doctor.reconnecting': 'O médico está reconectando…',
    'connection.reconnecting': 'Sua conexão foi interrompida. Tentando reconectar…',
    'connection.failed': 'Não foi possível restabelecer a conexão.',
};

const DEFAULT_END_MESSAGE = MESSAGES['call.ended.generic'];

/**
 * Resolve um message_key (vindo do backend ou interno) para texto PT-BR.
 * Faz fallback para mensagem genérica de encerramento em chave desconhecida.
 */
export function videoCallMessage(key: string | null | undefined): string {
    if (key && key in MESSAGES) {
        return MESSAGES[key as VideoCallMessageKey];
    }
    return DEFAULT_END_MESSAGE;
}

interface HttpErrorShape {
    response?: { status?: number };
    request?: unknown;
    code?: string;
}

/**
 * Mapeia um erro de requisição (Axios ou similar) para mensagem amigável.
 * Nunca retorna o corpo técnico do backend.
 */
export function mapVideoCallError(error: unknown): string {
    const err = (error ?? {}) as HttpErrorShape;
    const status = err.response?.status;

    if (status) {
        switch (status) {
            case 401:
                return 'Sua sessão expirou. Faça login novamente.';
            case 403:
                return 'Você não tem permissão para esta ação.';
            case 404:
                return 'Esta chamada não está mais disponível.';
            case 409:
                return 'Já existe uma chamada em andamento.';
            case 422:
                return 'Não foi possível concluir a operação. Tente novamente.';
            case 500:
            case 502:
            case 503:
                return 'Serviço temporariamente indisponível. Tente em instantes.';
            default:
                return 'Não foi possível concluir a operação. Tente novamente.';
        }
    }

    // Sem response → falha de rede / requisição não chegou ao servidor.
    if (err.request || err.code === 'ERR_NETWORK') {
        return 'Verifique sua conexão com a internet.';
    }

    return 'Não foi possível concluir a operação. Tente novamente.';
}
