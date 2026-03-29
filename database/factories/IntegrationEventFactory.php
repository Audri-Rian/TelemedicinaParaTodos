<?php

namespace Database\Factories;

use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationEventFactory extends Factory
{
    protected $model = IntegrationEvent::class;

    public function definition(): array
    {
        return [
            'partner_integration_id' => PartnerIntegration::factory(),
            'direction' => fake()->randomElement([IntegrationEvent::DIRECTION_OUTBOUND, IntegrationEvent::DIRECTION_INBOUND]),
            'event_type' => fake()->randomElement([
                IntegrationEvent::EVENT_EXAM_ORDER_SENT,
                IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
            ]),
            'status' => IntegrationEvent::STATUS_SUCCESS,
            'resource_type' => 'examination',
            'resource_id' => fake()->uuid(),
            'external_id' => 'ext-' . fake()->uuid(),
            'fhir_resource_type' => 'ServiceRequest',
            'duration_ms' => fake()->numberBetween(50, 2000),
            'error_message' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => ['status' => IntegrationEvent::STATUS_SUCCESS]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationEvent::STATUS_FAILED,
            'error_message' => 'Connection timeout after 15000ms',
        ]);
    }

    public function outbound(): static
    {
        return $this->state(fn () => [
            'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_ORDER_SENT,
        ]);
    }

    public function inbound(): static
    {
        return $this->state(fn () => [
            'direction' => IntegrationEvent::DIRECTION_INBOUND,
            'event_type' => IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED,
        ]);
    }
}
