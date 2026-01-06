<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkoutRequest;
use App\Models\NutritionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use Carbon\Carbon;



class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users'   => User::count(),
            'trainers'=> User::where('role','trainer')->count(),
            'trainees'=> User::where('role','trainee')->count(),
            'pending_requests' =>
                WorkoutRequest::where('status','pending')->count() +
                NutritionRequest::where('status','pending')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }


    

    public function requests(Request $request)
    {
        
        $view = $request->query('view', 'pending');
    
        $statusFilter = $view === 'history'
            ? ['approved', 'rejected']
            : ['pending'];
    
       
        $name = trim((string) $request->query('name', ''));
        $hasName = $name !== '';
    
        // workout
        $w = DB::table('workout_requests as r')
            ->join('users as trainees', 'r.user_id', '=', 'trainees.id')
            ->join('users as trainers', 'r.trainer_id', '=', 'trainers.id')
            ->whereIn('r.status', $statusFilter)
            ->when($hasName, function ($q) use ($name) {
                $q->where('trainees.name', 'like', "%{$name}%");
            })
            ->select(
                'r.id',
                'r.status',
                'r.created_at',
                DB::raw("'workout' as type"),
                'trainees.name as trainee_name',
                'trainers.name as trainer_name'
            );
    
        // nutrition
        $n = DB::table('nutrition_requests as r')
            ->join('users as trainees', 'r.user_id', '=', 'trainees.id')
            ->join('users as trainers', 'r.trainer_id', '=', 'trainers.id')
            ->whereIn('r.status', $statusFilter)
            ->when($hasName, function ($q) use ($name) {
                $q->where('trainees.name', 'like', "%{$name}%");
            })
            ->select(
                'r.id',
                'r.status',
                'r.created_at',
                DB::raw("'nutrition' as type"),
                'trainees.name as trainee_name',
                'trainers.name as trainer_name'
            );
    
       
        $union = $w->unionAll($n);
    
        $requests = DB::query()
            ->fromSub($union, 'u')
            ->orderByDesc('created_at')
            ->get();
    
        return view('admin.requests.index', [
            'requests' => $requests,
            'view'     => $view,
            'name'     => $name,   
        ]);
    }


public function deleteRequest(string $type, int $id)
{
    $table = $type === 'workout' ? 'workout_requests' : 'nutrition_requests';

    $req = DB::table($table)->where('id',$id)->first();
    if (!$req) abort(404);

    
    if ($req->status !== 'pending') {
        return back();
    }

    DB::table($table)->where('id',$id)->delete();
    return back();
}

public function templates() { return view('admin.templates.index'); 
}


public function paymentsDashboard(Request $request)
{
    $now = Carbon::now();

    // ✅ Filters (optional)
    $from   = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
    $to     = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()   : null;
    $method = $request->filled('method') ? $request->method : null;

    // Base query helper
    $baseQuery = Payment::query();
    if ($from && $to) {
        $baseQuery->whereBetween('paid_at', [$from, $to]);
    }
    if ($method) {
        $baseQuery->where('method', $method);
    }

    // ✅ Daily (today) - keep your original meaning (today) BUT respect method filter if provided
    $today = Carbon::today();

    $dailyQuery = Payment::query()->whereDate('paid_at', $today);
    if ($method) $dailyQuery->where('method', $method);

    $dailyTotal = (clone $dailyQuery)->sum('amount');
    $dailyCount = (clone $dailyQuery)->count();

    // Latest payments: better to show latest overall (filtered) not only today
    $dailyLatest = (clone $baseQuery)
        ->with('user:id,name,email')
        ->orderByDesc('paid_at')
        ->limit(15)
        ->get();

    // ✅ Monthly (current month) - respect method filter
    $monthStart = $now->copy()->startOfMonth();
    $monthEnd   = $now->copy()->endOfMonth();

    $monthlyQuery = Payment::query()->whereBetween('paid_at', [$monthStart, $monthEnd]);
    if ($method) $monthlyQuery->where('method', $method);

    $monthlyTotal = (clone $monthlyQuery)->sum('amount');
    $monthlyCount = (clone $monthlyQuery)->count();

    // limit breakdown (top days) to avoid "report feeling"
    $monthlyByDay = (clone $monthlyQuery)
        ->selectRaw('DATE(paid_at) as day, SUM(amount) as total, COUNT(*) as count')
        ->groupBy(DB::raw('DATE(paid_at)'))
        ->orderByDesc('total')
        ->limit(12)
        ->get();

    // ✅ Yearly (current year) - respect method filter
    $yearStart = $now->copy()->startOfYear();
    $yearEnd   = $now->copy()->endOfYear();

    $yearlyQuery = Payment::query()->whereBetween('paid_at', [$yearStart, $yearEnd]);
    if ($method) $yearlyQuery->where('method', $method);

    $yearlyTotal = (clone $yearlyQuery)->sum('amount');
    $yearlyCount = (clone $yearlyQuery)->count();

    $yearlyByMonth = (clone $yearlyQuery)
        ->selectRaw('MONTH(paid_at) as month, SUM(amount) as total, COUNT(*) as count')
        ->groupBy(DB::raw('MONTH(paid_at)'))
        ->orderBy('month')
        ->get();

    // ✅ by method (this month) - keep as your original, BUT if method filter set, still show all methods? عادةً الأفضل بدون فلتر method هون.
    $byMethod = Payment::selectRaw('COALESCE(method,"-") as method, SUM(amount) as total, COUNT(*) as count')
        ->whereBetween('paid_at', [$monthStart, $monthEnd])
        ->groupBy(DB::raw('COALESCE(method,"-")'))
        ->orderByDesc('total')
        ->get();



    // ✅ Methods list for filter select
    $methods = Payment::selectRaw('COALESCE(method,"-") as method')
        ->distinct()
        ->orderBy('method')
        ->pluck('method');

    return view('admin.payments.dashboard', compact(
        'dailyTotal','dailyCount','dailyLatest',
        'monthlyTotal','monthlyCount','monthlyByDay',
        'yearlyTotal','yearlyCount','yearlyByMonth',
        'byMethod','methods'
    ));
}



public function expiredSubscriptions(Request $request)
{
    $today = now()->toDateString();
    $name  = trim($request->get('name')); 

    $expiredTrainees = User::where('role', 'trainee')
        ->whereDoesntHave('payments', function ($q) use ($today) {
            $q->whereDate('period_start', '<=', $today)
              ->whereDate('period_end', '>=', $today);
        })
        ->when($name, function ($q) use ($name) {
            $q->where('name', 'like', "%{$name}%");
        })
        ->orderBy('name')
        ->get();

    return view('admin.subscriptions.expired', compact('expiredTrainees'));
}
}


