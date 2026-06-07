<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationTestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();

        $count = Notification::where('user_id', $user->id)->count();

        return response()->json([
            'user_id' => $user->id,
            'total_notifications' => $count,
        ]);
    }
}
