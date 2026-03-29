<?php

namespace Tests\Unit\Integrations\Services;

use App\Integrations\Services\CircuitBreaker;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

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
}
