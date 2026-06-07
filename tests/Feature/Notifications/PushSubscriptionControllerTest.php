<?php

namespace Tests\Feature\Notifications;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_browser_push_subscription(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/notifications/push-subscriptions', [
                'endpoint' => 'https://push.example.com/subscription/123',
                'keys' => [
                    'p256dh' => 'public-key',
                    'auth' => 'auth-token',
                ],
                'content_encoding' => 'aes128gcm',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.endpoint', 'https://push.example.com/subscription/123');

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/subscription/123',
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_storing_same_endpoint_updates_existing_subscription(): void
    {
        $user = User::factory()->create();

        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/subscription/123',
            'public_key' => 'old-public-key',
            'auth_token' => 'old-auth-token',
            'content_encoding' => 'aesgcm',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson('/api/notifications/push-subscriptions', [
                'endpoint' => 'https://push.example.com/subscription/123',
                'public_key' => 'new-public-key',
                'auth_token' => 'new-auth-token',
                'content_encoding' => 'aes128gcm',
            ]);

        $response->assertOk();

        $this->assertSame(1, PushSubscription::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/subscription/123',
            'public_key' => 'new-public-key',
            'auth_token' => 'new-auth-token',
        ]);
    }

    public function test_user_can_delete_only_own_subscription_by_endpoint(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $subscription = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.com/subscription/own',
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
            'content_encoding' => 'aes128gcm',
        ]);

        $otherSubscription = PushSubscription::create([
            'user_id' => $otherUser->id,
            'endpoint' => 'https://push.example.com/subscription/other',
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
            'content_encoding' => 'aes128gcm',
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson('/api/notifications/push-subscriptions', [
                'endpoint' => $subscription->endpoint,
            ]);

        $response->assertNoContent();

        $this->assertDatabaseMissing('push_subscriptions', [
            'id' => $subscription->id,
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'id' => $otherSubscription->id,
        ]);
    }
}
