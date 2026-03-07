<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->post('email/verification-notification')
            ->assertRedirect('/');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_does_not_send_verification_notification_if_email_is_verified(): void
    {
        Notification::fake();

        $doctor = \App\Models\Doctor::factory()->create();
        $user = $doctor->user;

        $this->actingAs($user)
            ->post('email/verification-notification')
            ->assertRedirect(route('doctor.dashboard', absolute: false));

        Notification::assertNothingSent();
    }
}
