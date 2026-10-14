<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Weekly check-ins: the trainee reports weight and how the week went, the coach replies. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('weight', 5, 1);
            $table->decimal('waist', 5, 1)->nullable();
            $table->unsignedTinyInteger('workouts_done')->nullable();   // 0-7
            $table->unsignedTinyInteger('diet_adherence')->nullable();  // 1-5
            $table->unsignedTinyInteger('energy')->nullable();          // 1-5
            $table->unsignedTinyInteger('sleep')->nullable();           // 1-5
            $table->text('note')->nullable();
            $table->json('photos')->nullable();                         // private disk paths
            $table->text('coach_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'trainee_id', 'created_at']);
            $table->index(['coach_id', 'replied_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
