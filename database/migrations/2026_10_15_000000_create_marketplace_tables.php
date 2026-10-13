<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online coaching marketplace: coaches publish packages, trainees subscribe
 * and pay with Sham Cash, the platform keeps a commission.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email'); // WhatsApp number
        });

        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->json('specialties')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('city', 50)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->json('certificates')->nullable(); // uploaded image paths
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
        });

        Schema::create('coach_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('includes_workout')->default(true);
            $table->boolean('includes_nutrition')->default(false);
            $table->decimal('price', 8, 2);
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('coach_packages')->nullOnDelete();
            // snapshot of the package at the time of purchase
            $table->string('package_title');
            $table->boolean('includes_workout')->default(true);
            $table->boolean('includes_nutrition')->default(false);
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->decimal('price', 8, 2);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 8, 2)->nullable();
            $table->decimal('coach_amount', 8, 2)->nullable();
            $table->enum('status', ['awaiting_payment', 'payment_review', 'active', 'rejected', 'cancelled'])
                  ->default('awaiting_payment');
            $table->json('intake')->nullable();
            $table->json('body_photos')->nullable();
            $table->string('payment_method', 30)->default('sham_cash');
            $table->string('payment_reference')->nullable();
            $table->string('payment_proof')->nullable();
            $table->string('admin_note')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'status']);
            $table->index(['trainee_id', 'status']);
        });

        Schema::create('coach_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 30)->default('sham_cash');
            $table->string('reference')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        foreach (['workout_plans', 'nutrition_plans'] as $plans) {
            Schema::table($plans, function (Blueprint $table) {
                $table->foreignId('subscription_id')->nullable()->after('trainer_id')
                      ->constrained('subscriptions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['workout_plans', 'nutrition_plans'] as $plans) {
            Schema::table($plans, function (Blueprint $table) {
                $table->dropConstrainedForeignId('subscription_id');
            });
        }
        Schema::dropIfExists('settings');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('coach_payouts');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('coach_packages');
        Schema::dropIfExists('coach_profiles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
