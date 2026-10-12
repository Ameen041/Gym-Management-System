<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminTemplateController;
use App\Http\Controllers\AdminExerciseController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AdminMessageController;



Route::get('/', [AuthController::class, 'showLoginForm'])->name('home'); 

// Language switch (English / Arabic)
Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('lang.switch');

Route::post('/login', [AuthController::class, 'login'])->name('login'); 

Route::post('/register', [AuthController::class, 'register'])->name('register'); 


Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');

// Public list of trainers (choosing one requires a trainee account)
Route::get('/trainers', [TraineeController::class, 'showTrainers'])->name('trainers');

// Contact form on the home page
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

// Forgot / reset password
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Trainer area
Route::middleware(['auth', 'active', 'role:trainer'])->group(function () {
    Route::get('/trainer-dashboard', [TrainerController::class, 'dashboard'])->name('trainer.dashboard');
    Route::get('/trainer/requests', [TrainerController::class, 'showRequests'])->name('trainer.requests');
    Route::post('/trainer/profile/update', [TrainerController::class, 'updateProfile'])->name('trainer.profile.update');
    Route::post('/trainer/add-workout', [TrainerController::class, 'addWorkout'])->name('trainer.addWorkout');
    Route::post('/trainer/add-nutrition', [TrainerController::class, 'addNutrition'])->name('trainer.addNutrition');
    Route::delete('/trainer/reject-workout/{id}', [TrainerController::class, 'rejectWorkout'])->name('trainer.rejectWorkout');
    Route::delete('/trainer/reject-nutrition/{id}', [TrainerController::class, 'rejectNutrition'])->name('trainer.rejectNutrition');
});

// Trainee area
Route::middleware(['auth', 'active', 'role:trainee'])->group(function () {
    Route::get('/trainee-dashboard', [AuthController::class, 'traineeDashboard'])->name('trainee.dashboard');
    Route::get('/workout-plan', [TraineeController::class, 'showWorkoutPlan'])->name('trainee.schedule');
    Route::delete('/workout-plan', [TraineeController::class, 'deleteWorkoutPlan'])->name('trainee.schedule.destroy');
    Route::get('/nutrition-plan', [TraineeController::class, 'showNutritionPlan'])->name('trainee.diet');
    Route::delete('/nutrition-plan', [TraineeController::class, 'deleteNutritionPlan'])->name('trainee.diet.destroy');
    Route::post('/trainee/request-workout', [TraineeController::class, 'requestWorkout'])->name('trainee.requestWorkout');
    Route::post('/trainee/request-nutrition', [TraineeController::class, 'requestNutrition'])->name('trainee.requestNutrition');
    Route::match(['POST', 'PUT', 'PATCH'], '/trainee/profile/update', [TraineeController::class, 'updateProfile'])
        ->name('trainee.profile.update');
});

// Exercise library (weight training exercises with video explanations)
Route::middleware(['auth', 'active'])->group(function () {
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
        Route::get('/users/{id}/payments', [AdminUserController::class, 'payments'])
            ->name('users.payments');
        Route::post('/users/{id}/payments', [AdminUserController::class, 'storePayment'])
            ->name('users.payments.store');
        Route::delete('/requests/{type}/{id}', [AdminController::class,'deleteRequest'])
        ->whereIn('type',['workout','nutrition'])->whereNumber('id')
        ->name('requests.destroy');
        Route::get('/requests',  [AdminController::class,'requests'])->name('requests.index');
        Route::post('/users/{id}/toggle', [AdminUserController::class, 'toggleActive'])->name('users.toggle');
        Route::get('/templates',            [AdminTemplateController::class,'index'])->name('templates.index');
        Route::get('/templates/create',     [AdminTemplateController::class,'create'])->name('templates.create');
        Route::post('/templates',           [AdminTemplateController::class,'store'])->name('templates.store');
        Route::get('/templates/{id}/edit',  [AdminTemplateController::class,'edit'])->name('templates.edit');
        
        Route::put('/templates/{id}',       [AdminTemplateController::class,'update'])->name('templates.update'); // ✅ هنا
        
        Route::delete('/templates/{id}',    [AdminTemplateController::class,'destroy'])->name('templates.destroy');
        Route::get('/exercises',           [AdminExerciseController::class,'index'])->name('exercises.index');
        Route::get('/exercises/create',    [AdminExerciseController::class,'create'])->name('exercises.create');
        Route::post('/exercises',          [AdminExerciseController::class,'store'])->name('exercises.store');
        Route::get('/exercises/{id}/edit', [AdminExerciseController::class,'edit'])->name('exercises.edit');
        Route::put('/exercises/{id}',      [AdminExerciseController::class,'update'])->name('exercises.update');
        Route::delete('/exercises/{id}',   [AdminExerciseController::class,'destroy'])->name('exercises.destroy');
        Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/{message}/read', [AdminMessageController::class, 'markRead'])->name('messages.read');
        Route::delete('/messages/{message}', [AdminMessageController::class, 'destroy'])->name('messages.destroy');
        Route::get('/payments', [AdminController::class, 'paymentsDashboard'])->name('payments.dashboard');
        Route::get('/subscriptions/expired', [AdminController::class, 'expiredSubscriptions'])
    ->name('subscriptions.expired');

    });

    
