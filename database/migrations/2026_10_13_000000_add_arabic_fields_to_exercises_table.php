<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('equipment_ar', 100)->nullable()->after('equipment');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('instructions_ar')->nullable()->after('instructions');
            $table->text('tips_ar')->nullable()->after('tips');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'equipment_ar', 'description_ar', 'instructions_ar', 'tips_ar']);
        });
    }
};
