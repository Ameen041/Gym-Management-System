<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What the client should eat each day (calories, protein, carbs, fat); the coach fills the plan up to it. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            $table->json('targets')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nutrition_plans', function (Blueprint $table) {
            $table->dropColumn('targets');
        });
    }
};
