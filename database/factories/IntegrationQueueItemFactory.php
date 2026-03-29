<?php

namespace Database\Factories;

use App\Models\IntegrationQueueItem;
use App\Models\PartnerIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationQueueItemFactory extends Factory
{
    protected $model = IntegrationQueueItem::class;

    public function definition(): array
    {
        return [
            'partner_integration_id' => PartnerIntegration::factory(),
            'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER,
            'payload' => ['examination_id' => fake()->uuid()],
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'attempts' => 0,
            'max_attempts' => 5,
            'scheduled_at' => now()->subMinute(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'scheduled_at' => now()->subMinute(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationQueueItem::STATUS_FAILED,
            'attempts' => 5,
            'last_error' => 'Max attempts reached',
        ]);
    }

    public function futureRetry(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationQueueItem::STATUS_QUEUED,
            'scheduled_at' => now()->addHour(),
        ]);
    }
}
