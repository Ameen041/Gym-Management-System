<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Support\Facades\Storage;

/** Serves a subscription's private files (payment receipt, body photos) to the people allowed to see them. */
class SubscriptionFileController extends Controller
{
    public function show(Subscription $subscription, string $kind, int $index = 0)
    {
        abort_unless($subscription->fileVisibleTo(auth()->user(), $kind), 403);

        $path = $kind === 'proof' ? $subscription->payment_proof : ($subscription->body_photos[$index] ?? null);
        $disk = Storage::disk(Subscription::FILES_DISK);
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
