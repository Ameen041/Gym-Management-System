<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Models\ContactMessage;
use App\Models\Subscription;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        $monthStart = now()->startOfMonth();

        $stats = [
            'payments_to_review' => Subscription::where('status', 'payment_review')->count(),
            'coaches_pending'    => CoachProfile::where('status', 'pending')->count(),
            'active_subs'        => Subscription::running()->count(),
            'late_plans'         => Subscription::running()
                ->where('starts_at', '<=', now()->subDays(Subscription::PLAN_DUE_DAYS))
                ->get()->filter(fn ($s) => !empty($s->missingPlans()))->count(),
            'month_sales'        => (float) Subscription::confirmed()->where('confirmed_at', '>=', $monthStart)->sum('price'),
            'month_commission'   => (float) Subscription::confirmed()->where('confirmed_at', '>=', $monthStart)->sum('commission_amount'),
            'coaches'            => User::where('role', 'trainer')->count(),
            'trainees'           => User::where('role', 'trainee')->count(),
            'unread_messages'    => ContactMessage::where('is_read', false)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
