<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Drawings of the start and end position (images/exercises/*.webp) and the muscles each exercise works
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->json('frames')->nullable()->after('image_path');
            $table->json('primary_muscles')->nullable()->after('muscle_group');
            $table->json('secondary_muscles')->nullable()->after('primary_muscles');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['frames', 'primary_muscles', 'secondary_muscles']);
        });
    }
};
