<?php

namespace Tests\Unit\Integrations\Services;

use App\Integrations\Services\CircuitBreaker;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * @group redis
 */
class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private CircuitBreaker $cb;
    private string $partnerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cb = new CircuitBreaker();

        try {
            Redis::ping();
        } catch (\Throwable) {
            $this->markTestSkipped('Redis not available');
        }

        // Usar um UUID real de um parceiro para evitar erro de cast
        $partner = PartnerIntegration::factory()->laboratory()->create();
        $this->partnerId = $partner->id;

        $this->cleanupRedis();
    }

    protected function tearDown(): void
    {
        $this->cleanupRedis();
        parent::tearDown();
    }

    private function cleanupRedis(): void
    {
        Redis::del("circuit_breaker:{$this->partnerId}");
        Redis::del("circuit_breaker:{$this->partnerId}:failures");
        Redis::del("circuit_breaker:{$this->partnerId}:half_open");
    }

    public function test_initial_state_is_closed(): void
    {
        $this->assertEquals('closed', $this->cb->getState($this->partnerId));
    }

    public function test_is_available_when_closed(): void
    {
        $this->assertTrue($this->cb->isAvailable($this->partnerId));
    }

    public function test_success_resets_state(): void
    {
        Redis::setex("circuit_breaker:{$this->partnerId}", 60, 'open');
        Redis::set("circuit_breaker:{$this->partnerId}:failures", 10);

        $this->cb->recordSuccess($this->partnerId);

        $this->assertEquals('closed', $this->cb->getState($this->partnerId));
    }

    public function test_failures_accumulate(): void
    {
        $this->cb->recordFailure($this->partnerId);
        $this->cb->recordFailure($this->partnerId);

        $count = (int) Redis::get("circuit_breaker:{$this->partnerId}:failures");
        $this->assertEquals(2, $count);
    }

    public function test_open_with_exhausted_half_open_blocks(): void
    {
        Redis::setex("circuit_breaker:{$this->partnerId}", 300, 'open');
        Redis::setex("circuit_breaker:{$this->partnerId}:half_open", 300, '999');

        $this->assertFalse($this->cb->isAvailable($this->partnerId));
    }

    public function test_expired_open_key_becomes_available(): void
    {
        Redis::setex("circuit_breaker:{$this->partnerId}", 1, 'open');
        sleep(2);

        $this->assertTrue($this->cb->isAvailable($this->partnerId));
    }

    public function test_circuit_opens_after_failure_threshold_is_reached(): void
    {
        $threshold = config('integrations.circuit_breaker.laboratory.failure_threshold', 5);

        for ($i = 0; $i < $threshold; $i++) {
            $this->cb->recordFailure($this->partnerId);
        }

        $this->assertEquals('open', $this->cb->getState($this->partnerId));
    }

    public function test_circuit_stays_closed_below_threshold(): void
    {
        $threshold = config('integrations.circuit_breaker.laboratory.failure_threshold', 5);

        // Garante pelo menos 1 falha registrada mesmo se threshold for 1 —
        // sem isso, a função de recordFailure nunca seria exercitada.
        $iterations = max(1, $threshold - 1);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cb->recordFailure($this->partnerId);
        }

        $this->assertEquals('closed', $this->cb->getState($this->partnerId));
        $this->assertTrue($this->cb->isAvailable($this->partnerId));
    }

    public function test_half_open_success_closes_circuit(): void
    {
        // Simula circuito aberto em estado half-open (tentativa permitida)
        Redis::setex("circuit_breaker:{$this->partnerId}", 300, 'open');
        // isAvailable em 'open' com half-open livre permite 1 tentativa
        $this->assertTrue($this->cb->isAvailable($this->partnerId));

        // Se a tentativa é bem-sucedida, o circuito fecha
        $this->cb->recordSuccess($this->partnerId);

        $this->assertEquals('closed', $this->cb->getState($this->partnerId));
        // Failures counter também zerado
        $this->assertNull(Redis::get("circuit_breaker:{$this->partnerId}:failures"));
    }

    public function test_half_open_attempts_are_limited(): void
    {
        // Torna a dependência de config explícita: fixa half_open_attempts=1
        // para este teste, em vez de depender do valor default. Evita falha
        // se o default mudar (ex.: aumentar para 2 tentativas).
        $originalConfig = config('integrations.circuit_breaker.laboratory');
        config(['integrations.circuit_breaker.laboratory' => array_merge(
            $originalConfig ?? [],
            ['half_open_attempts' => 1],
        )]);

        try {
            // Circuito aberto — primeira tentativa é permitida (half-open)
            Redis::setex("circuit_breaker:{$this->partnerId}", 300, 'open');

            $this->assertTrue($this->cb->isAvailable($this->partnerId));

            // Segunda chamada consecutiva a isAvailable sem sucesso prévio deve bloquear
            $this->assertFalse($this->cb->isAvailable($this->partnerId));
        } finally {
            // Isolar a mudança de config: restaurar o valor original para
            // não vazar estado para outros testes.
            config(['integrations.circuit_breaker.laboratory' => $originalConfig]);
        }
    }
}
