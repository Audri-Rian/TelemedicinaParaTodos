<?php

namespace Database\Factories;

use App\Models\PartnerIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PartnerIntegrationFactory extends Factory
{
    protected $model = PartnerIntegration::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => PartnerIntegration::TYPE_LABORATORY,
            'status' => PartnerIntegration::STATUS_ACTIVE,
            'base_url' => 'https://' . Str::slug($name) . '.example.com/fhir/r4',
            'webhook_url' => null,
            'capabilities' => ['send_exam_order', 'receive_exam_result', 'webhook_result'],
            'fhir_version' => 'R4',
            'contact_email' => fake()->safeEmail(),
            'last_sync_at' => null,
        ];
    }

    public function laboratory(): static
    {
        return $this->state(fn () => ['type' => PartnerIntegration::TYPE_LABORATORY]);
    }

    public function pharmacy(): static
    {
        return $this->state(fn () => ['type' => PartnerIntegration::TYPE_PHARMACY]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => PartnerIntegration::STATUS_ACTIVE]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => PartnerIntegration::STATUS_INACTIVE]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => PartnerIntegration::STATUS_PENDING]);
    }

    public function error(): static
    {
        return $this->state(fn () => ['status' => PartnerIntegration::STATUS_ERROR]);
    }

    public function withCredential(string $authType = 'api_key'): static
    {
        return $this->afterCreating(function (PartnerIntegration $partner) use ($authType) {
            $partner->credential()->create([
                'auth_type' => $authType,
                'client_id' => 'test-client-' . Str::random(8),
                'client_secret' => bcrypt('test-secret'),
            ]);
        });
    }

    public function withOAuth(): static
    {
        return $this->afterCreating(function (PartnerIntegration $partner) {
            $partner->credential()->create([
                'auth_type' => 'oauth2_client_credentials',
                'client_id' => 'oauth-client-' . Str::random(8),
                'client_secret' => bcrypt('oauth-secret-123'),
                'access_token' => hash('sha256', 'valid-test-token'),
                'token_expires_at' => now()->addHour(),
                'scopes' => ['lab:read', 'lab:write', 'webhook:send'],
            ]);
        });
    }
}
