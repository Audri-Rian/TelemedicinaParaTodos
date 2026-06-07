<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\DestroyPushSubscriptionRequest;
use App\Http\Requests\Notifications\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('create', PushSubscription::class);

        $subscription = PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $request->validated('endpoint'),
            ],
            [
                'public_key' => $request->validated('public_key'),
                'auth_token' => $request->validated('auth_token'),
                'content_encoding' => $request->validated('content_encoding') ?: 'aes128gcm',
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'data' => [
                'id' => $subscription->id,
                'endpoint' => $subscription->endpoint,
                'content_encoding' => $subscription->content_encoding,
            ],
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $subscription = PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $request->validated('endpoint'))
            ->first();

        if ($subscription === null) {
            return response()->json(null, 204);
        }

        $this->authorize('delete', $subscription);
        $subscription->delete();

        return response()->json(null, 204);
    }
}
