<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_endpoint_returns_presented_notifications_and_count(): void
    {
        $user = User::factory()->create();
        Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::APPOINTMENT_CREATED,
            'title' => 'Consulta Agendada',
            'message' => 'Consulta criada.',
            'metadata' => ['appointment_id' => 'appt-1'],
        ]);
        Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::APPOINTMENT_CANCELLED,
            'title' => 'Consulta Cancelada',
            'message' => 'Consulta cancelada.',
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/notifications/unread');

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.type', NotificationType::APPOINTMENT_CREATED->value)
            ->assertJsonPath('data.0.icon', 'calendar-plus')
            ->assertJsonPath('data.0.color', 'blue')
            ->assertJsonPath('data.0.metadata.appointment_id', 'appt-1');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::APPOINTMENT_REMINDER,
            'title' => 'Lembrete',
            'message' => 'Voce tem consulta.',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_only_updates_authenticated_user_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Notification::create([
            'user_id' => $user->id,
            'type' => NotificationType::APPOINTMENT_REMINDER,
            'title' => 'Lembrete',
            'message' => 'Voce tem consulta.',
        ]);
        $otherNotification = Notification::create([
            'user_id' => $otherUser->id,
            'type' => NotificationType::APPOINTMENT_REMINDER,
            'title' => 'Lembrete',
            'message' => 'Voce tem consulta.',
        ]);

        $response = $this->actingAs($user)->postJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('count', 1);
        $this->assertNull($otherNotification->fresh()->read_at);
    }
}
