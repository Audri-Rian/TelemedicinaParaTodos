<?php

namespace Tests\Unit\Integrations\Listeners;

use App\Integrations\Events\IntegrationFailed;
use App\Integrations\Listeners\NotifyIntegrationFailure;
use App\Integrations\Services\IntegrationFailureAlerter;
use App\Mail\IntegrationFailureMail;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyIntegrationFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_email_alert_for_configured_recipients(): void
    {
        Mail::fake();
        Cache::flush();
        config([
            'integrations.alerts.emails' => ['ops@example.com', 'suporte@example.com'],
            'integrations.alerts.failure_throttle_seconds' => 900,
            'integrations.alerts.error_excerpt_length' => 80,
        ]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create([
            'name' => 'Laboratório Teste',
            'slug' => 'laboratorio-teste',
        ]);
        $integrationEvent = IntegrationEvent::factory()->failed()->outbound()->create([
            'partner_integration_id' => $partner->id,
        ]);

        app(NotifyIntegrationFailure::class)->handle(
            new IntegrationFailed(
                partner: $partner,
                event: $integrationEvent,
                errorMessage: '<b>Timeout</b> com paciente Nome Completo em payload grande '.str_repeat('x', 120),
            ),
            app(IntegrationFailureAlerter::class)
        );

        Mail::assertQueued(IntegrationFailureMail::class, function (IntegrationFailureMail $mail) use ($partner, $integrationEvent) {
            return $mail->hasTo('ops@example.com')
                && $mail->hasTo('suporte@example.com')
                && $mail->partner->is($partner)
                && $mail->integrationEvent->is($integrationEvent)
                && ! str_contains($mail->sanitizedError, '<b>')
                && mb_strlen($mail->sanitizedError) <= 83;
        });
    }

    public function test_it_throttles_repeated_alerts_for_same_partner_and_event_type(): void
    {
        Mail::fake();
        Cache::flush();
        config([
            'integrations.alerts.emails' => ['ops@example.com'],
            'integrations.alerts.failure_throttle_seconds' => 900,
        ]);

        $partner = PartnerIntegration::factory()->laboratory()->active()->create();
        $firstEvent = IntegrationEvent::factory()->failed()->outbound()->create([
            'partner_integration_id' => $partner->id,
        ]);
        $secondEvent = IntegrationEvent::factory()->failed()->outbound()->create([
            'partner_integration_id' => $partner->id,
        ]);
        $listener = app(NotifyIntegrationFailure::class);
        $alerter = app(IntegrationFailureAlerter::class);

        $listener->handle(new IntegrationFailed($partner, $firstEvent, 'Timeout 1'), $alerter);
        $listener->handle(new IntegrationFailed($partner, $secondEvent, 'Timeout 2'), $alerter);

        Mail::assertQueued(IntegrationFailureMail::class, 1);
    }
}
