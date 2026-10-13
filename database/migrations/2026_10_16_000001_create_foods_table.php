<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Food library for the nutrition-plan calculator. Values are per 100 g.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('category', 30)->default('other');
            $table->decimal('calories', 7, 2);   // kcal / 100 g
            $table->decimal('protein', 6, 2);    // g / 100 g
            $table->decimal('carbs', 6, 2);      // g / 100 g
            $table->decimal('fat', 6, 2);        // g / 100 g
            $table->decimal('fiber', 6, 2)->nullable();
            $table->decimal('serving_grams', 7, 2)->nullable(); // e.g. 50 g = "1 large egg"
            $table->string('serving_name')->nullable();
            $table->string('serving_name_ar')->nullable();
            $table->string('usda_ndb', 10)->nullable()->unique(); // USDA SR28 food number
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
