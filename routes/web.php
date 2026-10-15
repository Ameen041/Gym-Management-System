<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminTemplateController;
use App\Http\Controllers\AdminExerciseController;
use App\Http\Controllers\AdminFoodController;
use App\Http\Controllers\AdminCoachController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\AdminPayoutController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\AdminSystemController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CoachDirectoryController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionFileController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Coach\DashboardController as CoachDashboardController;
use App\Http\Controllers\Coach\ProfileController as CoachProfileController;
use App\Http\Controllers\Coach\PackageController as CoachPackageController;
use App\Http\Controllers\Coach\ClientController as CoachClientController;
use App\Http\Controllers\Coach\PlanController as CoachPlanController;


Route::get('/', [AuthController::class, 'showLoginForm'])->name('home');

// Language switch (English / Arabic)
Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('lang.switch');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');

// Coaches directory (public; subscribing requires a trainee account)
Route::get('/coaches', [CoachDirectoryController::class, 'index'])->name('coaches.index');
Route::get('/coaches/{coach}', [CoachDirectoryController::class, 'show'])->whereNumber('coach')->name('coaches.show');
Route::redirect('/trainers', '/coaches', 301)->name('trainers');

// Contact form on the home page
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

// Forgot / reset password
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Coach area
Route::middleware(['auth', 'active', 'role:trainer'])->group(function () {
    Route::get('/trainer-dashboard', [CoachDashboardController::class, 'index'])->name('trainer.dashboard');

    Route::prefix('coach')->name('coach.')->group(function () {
        Route::get('/profile', [CoachProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [CoachProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/payout', [CoachProfileController::class, 'updatePayout'])->name('profile.payout');

        Route::get('/packages', [CoachPackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [CoachPackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [CoachPackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [CoachPackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/clients/{subscription}', [CoachClientController::class, 'show'])->name('clients.show');
        Route::get('/clients/{subscription}/plan/{type}', [CoachPlanController::class, 'edit'])
            ->whereIn('type', ['workout', 'nutrition'])->name('plans.edit');
        Route::post('/clients/{subscription}/plan/{type}', [CoachPlanController::class, 'save'])
            ->whereIn('type', ['workout', 'nutrition'])->name('plans.save');
        Route::post('/clients/{subscription}/plan/{type}/repeat', [CoachPlanController::class, 'repeat'])
            ->whereIn('type', ['workout', 'nutrition'])->name('plans.repeat');

        Route::post('/check-ins/{checkIn}/reply', [CheckInController::class, 'reply'])->name('check-ins.reply');
    });
});

// Trainee area
Route::middleware(['auth', 'active', 'role:trainee'])->group(function () {
    Route::get('/trainee-dashboard', [TraineeController::class, 'dashboard'])->name('trainee.dashboard');
    Route::get('/workout-plan', [TraineeController::class, 'showWorkoutPlan'])->name('trainee.schedule');
    Route::get('/nutrition-plan', [TraineeController::class, 'showNutritionPlan'])->name('trainee.diet');
    Route::match(['POST', 'PUT', 'PATCH'], '/trainee/profile/update', [TraineeController::class, 'updateProfile'])
        ->name('trainee.profile.update');

    Route::get('/coaches/{coach}/packages/{package}/subscribe', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/coaches/{coach}/packages/{package}/subscribe', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/subscriptions/{subscription}/pay', [SubscriptionController::class, 'payForm'])->name('subscriptions.pay');
    Route::post('/subscriptions/{subscription}/pay', [SubscriptionController::class, 'submitPayment'])->name('subscriptions.pay.submit');
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/{subscription}/review', [SubscriptionController::class, 'review'])->name('subscriptions.review');

    // Weekly check-in
    Route::get('/subscriptions/{subscription}/check-in', [CheckInController::class, 'show'])->name('check-ins.show');
    Route::post('/subscriptions/{subscription}/check-in', [CheckInController::class, 'store'])->name('check-ins.store');
});

// Exercise library (weight training exercises with video explanations)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    // Private subscription files (payment receipt, body photos)
    Route::get('/subscriptions/{subscription}/files/{kind}/{index?}', [SubscriptionFileController::class, 'show'])
        ->whereIn('kind', ['proof', 'photo'])->whereNumber('index')->name('subscriptions.file');
    Route::get('/coaches/{coach}/payout-qr', [CoachProfileController::class, 'payoutQr'])->whereNumber('coach')->name('coaches.payout-qr');
    Route::get('/check-ins/{checkIn}/photos/{index}', [CheckInController::class, 'photo'])
        ->whereNumber('index')->name('check-ins.photo');

    Route::get('/exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::get('/exercises/{exercise}', [ExerciseController::class, 'show'])->whereNumber('exercise')->name('exercises.show');
});


Route::middleware(['auth','active','role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/users',            [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create',     [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users',           [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit',  [AdminUserController::class, 'edit'])->name('users.edit');
        Route::match(['post', 'put'], '/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}',    [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/toggle', [AdminUserController::class, 'toggleActive'])->name('users.toggle');
        Route::post('/users/{id}/reset-link', [AdminUserController::class, 'resetLink'])->name('users.resetLink');

        // Coaches: review profiles before they are listed
        Route::get('/coaches',                  [AdminCoachController::class, 'index'])->name('coaches.index');
        Route::get('/coaches/{coach}',          [AdminCoachController::class, 'show'])->name('coaches.show');
        Route::post('/coaches/{coach}/approve', [AdminCoachController::class, 'approve'])->name('coaches.approve');
        Route::post('/coaches/{coach}/reject',  [AdminCoachController::class, 'reject'])->name('coaches.reject');
        Route::post('/coaches/{coach}/feature', [AdminCoachController::class, 'toggleFeatured'])->name('coaches.feature');

        // Subscriptions & Sham Cash payments
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions/{subscription}/confirm', [AdminSubscriptionController::class, 'confirm'])->name('subscriptions.confirm');
        Route::post('/subscriptions/{subscription}/reject',  [AdminSubscriptionController::class, 'reject'])->name('subscriptions.reject');

        // Coach payouts
        Route::get('/payouts',  [AdminPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [AdminPayoutController::class, 'store'])->name('payouts.store');

        // Platform settings (commission, Sham Cash account)
        Route::get('/settings',  [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

        Route::get('/templates',            [AdminTemplateController::class,'index'])->name('templates.index');
        Route::get('/templates/create',     [AdminTemplateController::class,'create'])->name('templates.create');
        Route::post('/templates',           [AdminTemplateController::class,'store'])->name('templates.store');
        Route::get('/templates/{id}/edit',  [AdminTemplateController::class,'edit'])->name('templates.edit');
        Route::put('/templates/{id}',       [AdminTemplateController::class,'update'])->name('templates.update');
        Route::delete('/templates/{id}',    [AdminTemplateController::class,'destroy'])->name('templates.destroy');

        Route::get('/exercises',           [AdminExerciseController::class,'index'])->name('exercises.index');
        Route::get('/exercises/create',    [AdminExerciseController::class,'create'])->name('exercises.create');
        Route::post('/exercises',          [AdminExerciseController::class,'store'])->name('exercises.store');
        Route::get('/exercises/{id}/edit', [AdminExerciseController::class,'edit'])->name('exercises.edit');
        Route::put('/exercises/{id}',      [AdminExerciseController::class,'update'])->name('exercises.update');
        Route::delete('/exercises/{id}',   [AdminExerciseController::class,'destroy'])->name('exercises.destroy');
        Route::post('/exercises/{id}/video', [AdminExerciseController::class,'updateVideo'])->name('exercises.video');

        // Food library for the nutrition calculator
        Route::get('/foods',              [AdminFoodController::class, 'index'])->name('foods.index');
        Route::get('/foods/create',       [AdminFoodController::class, 'create'])->name('foods.create');
        Route::post('/foods',             [AdminFoodController::class, 'store'])->name('foods.store');
        Route::get('/foods/{food}/edit',  [AdminFoodController::class, 'edit'])->name('foods.edit');
        Route::put('/foods/{food}',       [AdminFoodController::class, 'update'])->name('foods.update');
        Route::delete('/foods/{food}',    [AdminFoodController::class, 'destroy'])->name('foods.destroy');

        // Launch checklist and backups
        Route::get('/system', [AdminSystemController::class, 'index'])->name('system.index');
        Route::post('/system/backups', [AdminSystemController::class, 'backup'])->name('system.backup');
        Route::get('/system/backups/{name}', [AdminSystemController::class, 'download'])->name('system.download');
        Route::delete('/system/backups/{name}', [AdminSystemController::class, 'destroy'])->name('system.destroy');

        Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/{message}/read', [AdminMessageController::class, 'markRead'])->name('messages.read');
        Route::delete('/messages/{message}', [AdminMessageController::class, 'destroy'])->name('messages.destroy');
    });
