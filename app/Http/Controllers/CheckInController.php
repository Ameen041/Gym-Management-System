<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Subscription;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Weekly check-ins: the trainee sends one, the coach replies. */
class CheckInController extends Controller
{
    /** Trainee: this week's form, progress chart and earlier check-ins with the coach's replies. */
    public function show(Subscription $subscription)
    {
        $this->authorizeTrainee($subscription);
        $subscription->load('coach');

        $checkIns = CheckIn::between($subscription->trainee_id, $subscription->coach_id)->get();
        $schedule = $subscription->checkInSchedule($checkIns->last());

        return view('check-ins.show', [
            'subscription' => $subscription,
            'checkIns'     => $checkIns->reverse()->values(),
            'points'       => self::chartPoints($subscription, $checkIns),
            'schedule'     => $schedule,
            'canSend'      => $subscription->isRunning() && !today()->lt($schedule['opens']),
        ]);
    }

    public function store(Request $request, Subscription $subscription)
    {
        $this->authorizeTrainee($subscription);
        abort_unless($subscription->isRunning(), 403);

        $schedule = $subscription->checkInSchedule();
        if (today()->lt($schedule['opens'])) {
            return back()->with('error', __('Your next check-in opens on :date.', ['date' => $schedule['opens']->translatedFormat('l j M')]));
        }

        $data = $request->validate([
            'weight'         => ['required', 'numeric', 'min:20', 'max:300'],
            'waist'          => ['nullable', 'numeric', 'min:30', 'max:250'],
            'workouts_done'  => ['nullable', 'integer', 'min:0', 'max:7'],
            'diet_adherence' => ['nullable', 'integer', 'min:1', 'max:5'],
            'energy'         => ['nullable', 'integer', 'min:1', 'max:5'],
            'sleep'          => ['nullable', 'integer', 'min:1', 'max:5'],
            'note'           => ['nullable', 'string', 'max:2000'],
            'photos'         => ['nullable', 'array', 'max:3'],
            'photos.*'       => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photos = [];
        foreach ($request->file('photos', []) as $file) {
            $photos[] = $file->store('check_ins', Subscription::FILES_DISK);
        }

        $checkIn = CheckIn::create([
            'subscription_id' => $subscription->id,
            'trainee_id'      => $subscription->trainee_id,
            'coach_id'        => $subscription->coach_id,
            'photos'          => $photos ?: null,
            // questions that don't apply to this package are not kept
            'workouts_done'   => $subscription->includes_workout ? ($data['workouts_done'] ?? null) : null,
            'diet_adherence'  => $subscription->includes_nutrition ? ($data['diet_adherence'] ?? null) : null,
        ] + collect($data)->only(['weight', 'waist', 'energy', 'sleep', 'note'])->all());

        // keep the profile weight current
        $subscription->trainee->update(['weight' => $checkIn->weight]);

        Notify::user($subscription->coach, ':name sent a weekly check-in: :weight kg.', [
            'name'   => $subscription->trainee->name,
            'weight' => self::kg($checkIn->weight),
        ], Notify::link('coach.clients.show', $subscription) . '#progress', 'fa-clipboard-check');

        return redirect()->route('check-ins.show', $subscription)
            ->with('success', __('Check-in sent. Your coach will reply soon.'));
    }

    /** Coach: reply to a check-in (or edit the reply). */
    public function reply(Request $request, CheckIn $checkIn)
    {
        abort_unless((int) $checkIn->coach_id === (int) auth()->id(), 404);

        $data = $request->validate(['coach_reply' => ['required', 'string', 'max:2000']]);
        $firstReply = $checkIn->replied_at === null;

        $checkIn->update(['coach_reply' => $data['coach_reply'], 'replied_at' => now()]);

        if ($firstReply) {
            Notify::user($checkIn->trainee, ':coach replied to your check-in.', [
                'coach' => auth()->user()->name,
            ], Notify::link('check-ins.show', $checkIn->subscription_id), 'fa-comment-dots');
        }

        return redirect(route('coach.clients.show', $checkIn->subscription_id) . '#check-in-' . $checkIn->id)
            ->with('success', __('Reply sent.'));
    }

    /** A check-in photo, for the trainee, their coach and the admin only. */
    public function photo(CheckIn $checkIn, int $index)
    {
        abort_unless($checkIn->visibleTo(auth()->user()), 403);

        $path = $checkIn->photos[$index] ?? null;
        $disk = Storage::disk(Subscription::FILES_DISK);
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    /** Weight over time for the chart: the starting weight, then every check-in. */
    public static function chartPoints(Subscription $subscription, $checkIns): array
    {
        $points = [];
        if ($start = $subscription->startingPoint()) {
            $points[] = ['date' => $start['date'], 'weight' => $start['weight'], 'label' => __('Start')];
        }
        foreach ($checkIns as $i => $c) {
            $points[] = ['date' => $c->created_at, 'weight' => $c->weight, 'label' => __('Check-in :n', ['n' => $i + 1])];
        }

        return $points;
    }

    /** 72.0 -> "72", 71.5 -> "71.5" */
    public static function kg(?float $value): string
    {
        return $value === null ? '—' : rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    private function authorizeTrainee(Subscription $subscription): void
    {
        abort_unless((int) $subscription->trainee_id === (int) auth()->id() && $subscription->status === 'active', 404);
    }
}
