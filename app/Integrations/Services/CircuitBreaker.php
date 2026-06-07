<?php

namespace App\Integrations\Services;

use App\Models\PartnerIntegration;
use Illuminate\Support\Facades\Redis;

/**
 * Circuit Breaker para integrações com parceiros.
 *
 * Estados: closed (normal) → open (bloqueado) → half-open (teste).
 * Estado armazenado em Redis para compartilhamento entre workers.
 *
 * Referência: execute/ResilienciaOperacional.md
 */
class CircuitBreaker
{
    private const PREFIX = 'circuit_breaker:';

    /**
     * Verifica se o parceiro está disponível para receber chamadas.
     */
    public function isAvailable(string $partnerId): bool
    {
        $state = $this->getState($partnerId);

        return match ($state) {
            'open' => $this->shouldTryHalfOpen($partnerId),
            'half-open' => true,
            default => true, // closed ou inexistente
        };
    }

    /**
     * Registra sucesso — fecha o circuito.
     */
    public function recordSuccess(string $partnerId): void
    {
        Redis::del(self::PREFIX . $partnerId);
        Redis::del(self::PREFIX . $partnerId . ':failures');
        Redis::del(self::PREFIX . $partnerId . ':half_open');
    }

    /**
     * Registra falha — abre o circuito se threshold atingido.
     */
    public function recordFailure(string $partnerId): void
    {
        $key = self::PREFIX . $partnerId . ':failures';
        $failures = Redis::incr($key);

        // Expirar contagem após a janela
        $config = $this->getConfig($partnerId);
        Redis::expire($key, $config['cooling_timeout'] * 2);

        if ($failures >= $config['failure_threshold']) {
            $this->open($partnerId, $config['cooling_timeout']);
        }
    }

    /**
     * Retorna o estado atual do circuito.
     */
    public function getState(string $partnerId): string
    {
        return Redis::get(self::PREFIX . $partnerId) ?? 'closed';
    }

    /**
     * Retorna o cooling timeout para um parceiro.
     */
    public function getCoolingTimeout(string $partnerId): int
    {
        return $this->getConfig($partnerId)['cooling_timeout'];
    }

    /**
     * Abre o circuito.
     */
    private function open(string $partnerId, int $coolingTimeout): void
    {
        Redis::setex(self::PREFIX . $partnerId, $coolingTimeout, 'open');
    }

    /**
     * Verifica se deve tentar half-open (teste após cooling).
     */
    private function shouldTryHalfOpen(string $partnerId): bool
    {
        $halfOpenKey = self::PREFIX . $partnerId . ':half_open';

        // Permitir apenas N tentativas em half-open
        $config = $this->getConfig($partnerId);
        $attempts = (int) Redis::get($halfOpenKey);

        if ($attempts >= $config['half_open_attempts']) {
            return false;
        }

        Redis::incr($halfOpenKey);
        Redis::expire($halfOpenKey, $config['cooling_timeout']);

        return true;
    }

    /**
     * Obtém configuração do circuit breaker para o parceiro.
     */
    private function getConfig(string $partnerId): array
    {
        $partner = PartnerIntegration::find($partnerId);
        $type = $partner?->type ?? 'laboratory';

        return config("integrations.circuit_breaker.{$type}", [
            'failure_threshold' => 5,
            'cooling_timeout' => 60,
            'half_open_attempts' => 1,
        ]);
    }
}
