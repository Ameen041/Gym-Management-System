<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workout_requests', function (Blueprint $table) {
            $table->text('body_photos')->nullable()->after('notes');
        });

        Schema::table('nutrition_requests', function (Blueprint $table) {
            $table->text('body_photos')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('workout_requests', function (Blueprint $table) {
            $table->dropColumn('body_photos');
        });

        Schema::table('nutrition_requests', function (Blueprint $table) {
            $table->dropColumn('body_photos');
        });
    }
};