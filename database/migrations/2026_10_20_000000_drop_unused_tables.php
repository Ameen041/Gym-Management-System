<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Tables of the old graduation-project flow (plan requests and the first
 * payments table). The marketplace replaced them with subscriptions, which
 * hold the Sham Cash receipt, so nothing reads or writes them any more.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('workout_requests');
        Schema::dropIfExists('nutrition_requests');
        Schema::dropIfExists('payments');
    }

    public function down(): void
    {
        // not restored: the features that used these tables are gone
    }
};
