<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A package price set by the admin: the coach cannot change its price or duration
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_packages', function (Blueprint $table) {
            $table->boolean('price_locked')->default(false)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('coach_packages', function (Blueprint $table) {
            $table->dropColumn('price_locked');
        });
    }
};
