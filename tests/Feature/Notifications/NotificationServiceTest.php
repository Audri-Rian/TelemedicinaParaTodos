<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_created_notifications_are_idempotent_per_user_and_appointment(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $metadata = [
            'appointment_id' => 'appt-123',
            'doctor_name' => 'Audri Doutor',
            'scheduled_at' => now()->toIso8601String(),
        ];

        $first = $service->create(NotificationType::APPOINTMENT_CREATED, $metadata, $user, ['in_app']);
        $second = $service->create(NotificationType::APPOINTMENT_CREATED, $metadata, $user, ['in_app']);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertEquals(
            1,
            Notification::where('user_id', $user->id)
                ->where('type', NotificationType::APPOINTMENT_CREATED->value)
                ->where('metadata->appointment_id', 'appt-123')
                ->count()
        );
    }
}
