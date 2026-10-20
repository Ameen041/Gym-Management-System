<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plans are stored as JSON text. On MySQL a TEXT column holds 64 KB, and a full week with
 * 6 meals of 8 foods a day already passes that, so the plan would not save. LONGTEXT,
 * as the plan templates already use, has room for any plan (SQLite has no such limit).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['workout_plans', 'nutrition_plans'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->longText('plan_details')->change());
        }
    }

    public function down(): void
    {
        foreach (['workout_plans', 'nutrition_plans'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->text('plan_details')->change());
        }
    }
};
