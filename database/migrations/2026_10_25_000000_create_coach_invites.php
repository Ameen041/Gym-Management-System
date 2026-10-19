<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coach invite links: a coach shares their link, people who sign up from it
 * are the coach's own clients, and Himma takes a lower commission on them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->string('invite_code', 16)->nullable()->unique();
            $table->unsignedInteger('invite_visits')->default(0);
        });

        // the coach whose link the person signed up from
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // himma: found on Himma; coach_link: the coach's own client (lower commission)
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('source', 20)->default('himma');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('source');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
        });
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->dropUnique(['invite_code']);
            $table->dropColumn(['invite_code', 'invite_visits']);
        });
    }
};
