<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::storeFromBrowser(
            payload: $validated,
            userId: $request->user()?->id,
            userAgent: substr((string) $request->userAgent(), 0, 255),
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint');

        if ($endpoint !== '') {
            PushSubscription::query()
                ->where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))
                ->delete();
        }

        return response()->json(['ok' => true]);
    }
}
