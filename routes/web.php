<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminTemplateController;



Route::get('/', [AuthController::class, 'showLoginForm'])->name('home'); // الصفحة الرئيسية

Route::post('/login', [AuthController::class, 'login'])->name('login'); // لمعالجة تسجيل الدخول

Route::post('/register', [AuthController::class, 'register'])->name('register'); // تسجيل حساب جديد

Route::post('/trainer-dashboard', [AuthController::class, 'trainer-dashboard'])->name('trainer-dashboard');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/trainer-dashboard', [TrainerController::class, 'dashboard'])->name('trainer.dashboard');

Route::get('/trainee-dashboard', [AuthController::class, 'traineeDashboard'])->name('trainee.dashboard');

Route::middleware(['auth','active', 'trainer'])->group(function () {
 Route::post('/trainer/profile/update', [TrainerController::class, 'updateProfile'])->name('trainer.profile.update');
Route::post('/trainer/add-workout', [TrainerController::class, 'addWorkout'])->name('trainer.addWorkout');
Route::get('/trainer/create-workout/{user_id}', [TrainerController::class, 'showWorkoutForm'])->name('trainer.createWorkout');
Route::delete('/trainer/reject-workout/{id}', [TrainerController::class, 'rejectWorkout'])->name('trainer.rejectWorkout');
Route::delete('/trainer/reject-nutrition/{id}', [TrainerController::class, 'rejectNutrition'])->name('trainer.rejectNutrition');
Route::post('/trainer/add-nutrition', [TrainerController::class, 'addNutrition'])->name('trainer.addNutrition');
});



Route::post('/trainee/request-workout', [TraineeController::class, 'requestWorkout'])->name('trainee.requestWorkout');

Route::get('/trainer/requests', [TrainerController::class, 'showRequests'])->name('trainer.requests');

Route::get('/trainers', [TraineeController::class, 'showTrainers'])->name('trainers');

Route::get('/workout-plan', [TraineeController::class, 'showWorkoutPlan'])->name('trainee.schedule');

Route::get('/nutrition-plan', [TraineeController::class, 'showNutritionPlan'])->name('trainee.diet');

Route::post('/trainee/request-nutrition', [TraineeController::class, 'requestNutrition'])->name('trainee.requestNutrition');

Route::get('/trainer/nutrition-requests', [TrainerController::class, 'showNutritionRequests'])->name('trainer.nutritionRequests');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');

Route::get('/trainee/profile/edit', [TraineeController::class, 'editProfile'])->name('trainee.profile.edit');

Route::match(['POST','PUT','PATCH'], '/trainee/profile/update', [TraineeController::class, 'updateProfile'])
    ->name('trainee.profile.update');




Route::middleware(['auth','active','role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users',            [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create',     [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users',           [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit',  [AdminUserController::class, 'edit'])->name('users.edit');
        Route::post('/users/{id}',      [AdminUserController::class, 'update'])->name('users.update');
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
        Route::get('/templates',           [AdminTemplateController::class,'index'])->name('templates.index');
        Route::get('/templates/create',    [AdminTemplateController::class,'create'])->name('templates.create');
        Route::post('/templates',          [AdminTemplateController::class,'store'])->name('templates.store');
        Route::get('/templates/{id}/edit', [AdminTemplateController::class,'edit'])->name('templates.edit');
        Route::post('/templates/{id}',     [AdminTemplateController::class,'update'])->name('templates.update');
        Route::delete('/templates/{id}',   [AdminTemplateController::class,'destroy'])->name('templates.destroy');
        Route::get('/payments', [AdminController::class, 'paymentsDashboard'])->name('payments.dashboard');
        Route::get('/subscriptions/expired', [AdminController::class, 'expiredSubscriptions'])
    ->name('subscriptions.expired');

    });

    
