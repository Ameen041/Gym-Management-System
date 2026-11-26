<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Exercise;
use App\Models\NutritionPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the phone keeps for when there is no internet (public/sw.js, js/offline.js): the person's main pages and,
 * for a trainee, their plans with every exercise in them and its pictures, before they open them (a gym basement
 * often has no signal). The version changes when a plan or an exercise in it changes, so the phone fetches the
 * pages again only then.
 */
class OfflinePackController extends Controller
{
    /** The plans of the latest subscriptions are kept (the dashboard links to them). */
    private const SUBSCRIPTIONS = 3;

    /** The coach's running clients whose pages are kept. */
    private const CLIENTS = 30;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $pack = ['pages' => [route('exercises.index', [], false), route('chat.index', [], false), route('notifications.index', [], false)], 'files' => [], 'stamps' => []];

        $pack = $user->role === 'trainer' ? $this->coach($user, $pack) : $this->trainee($user, $pack);

        // the library page shows every exercise with its small picture
        foreach (Exercise::active()->get() as $exercise) {
            $pack['files'][] = $exercise->thumbnail;
        }

        $pages = array_values(array_unique($pack['pages']));
        $files = array_values(array_unique(array_filter(array_map([$this, 'path'], $pack['files']))));

        return response()->json([
            'version' => sha1(json_encode([$pages, $files, $pack['stamps']])),
            'pages'   => $pages,
            'files'   => $files,
        ]);
    }

    private function trainee(User $user, array $pack): array
    {
        $pack['pages'][] = route('trainee.dashboard', [], false);
        $pack['files'][] = $user->photo_url;
        $exerciseIds = [];

        foreach ([[WorkoutPlan::class, 'trainee.schedule'], [NutritionPlan::class, 'trainee.diet']] as [$model, $route]) {
            $plans = $model::with('trainer')->where('user_id', $user->id)->latest()->get()
                ->unique('subscription_id')->take(self::SUBSCRIPTIONS);
            if ($plans->isNotEmpty()) {
                $pack['pages'][] = route($route, [], false);
            }
            foreach ($plans as $plan) {
                $pack['pages'][] = route($route, ['subscription' => $plan->subscription_id], false);
                $pack['files'][] = $plan->trainer?->photo_url;
                $pack['stamps'][] = $plan->updated_at?->timestamp;
                if ($model === WorkoutPlan::class) {
                    foreach ((array) json_decode((string) $plan->plan_details, true) as $day) {
                        foreach ((array) $day as $row) {
                            $exerciseIds[] = (int) ($row['exercise_id'] ?? 0);
                        }
                    }
                }
            }
        }

        // every exercise in the plans, with its pictures (its page shows both positions)
        foreach (Exercise::active()->whereIn('id', array_filter(array_unique($exerciseIds)))->orderBy('id')->get() as $exercise) {
            $pack['pages'][] = route('exercises.show', $exercise, false);
            array_push($pack['files'], ...$exercise->frame_urls);
            $pack['stamps'][] = $exercise->updated_at?->timestamp;
        }

        foreach (Conversation::with('coach')->where('trainee_id', $user->id)->latest('last_message_at')->take(self::SUBSCRIPTIONS)->get() as $chat) {
            $pack['pages'][] = route('chat.show', $chat, false);
            $pack['files'][] = $chat->coach?->photo_url;
        }

        return $pack;
    }

    private function coach(User $coach, array $pack): array
    {
        $pack['pages'][] = route('trainer.dashboard', [], false);
        $pack['files'][] = $coach->photo_url;

        $clients = Subscription::with('trainee')->where('coach_id', $coach->id)->where('status', 'active')
            ->where('ends_at', '>', now())->latest()->take(self::CLIENTS)->get()->filter->isRunning();
        foreach ($clients as $subscription) {
            $pack['pages'][] = route('coach.clients.show', $subscription, false);
            $pack['files'][] = $subscription->trainee?->photo_url;
        }

        return $pack;
    }

    /** "https://host/uploads/x.webp" => "/uploads/x.webp": the phone fetches it from the site it is on. */
    private function path(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $parts = parse_url($url);

        return ($parts['path'] ?? null) ? $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '') : null;
    }
}
