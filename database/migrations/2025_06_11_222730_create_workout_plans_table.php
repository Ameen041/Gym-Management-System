<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id')->nullable(false);
            $table->unsignedBigInteger('user_id');
            $table->text('plan_details');
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('workout_plans');
    }
};
