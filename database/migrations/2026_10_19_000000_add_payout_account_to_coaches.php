<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Where the admin sends a coach's earnings (Sham Cash), and which account each payout went to. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->string('shamcash_name', 100)->nullable();
            $table->string('shamcash_account', 100)->nullable();
            $table->string('shamcash_qr')->nullable();          // private disk path
            $table->timestamp('payout_account_updated_at')->nullable();
        });

        Schema::table('coach_payouts', function (Blueprint $table) {
            $table->string('account')->nullable();              // snapshot: "name · account"
        });
    }

    public function down(): void
    {
        Schema::table('coach_payouts', function (Blueprint $table) {
            $table->dropColumn('account');
        });
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->dropColumn(['shamcash_name', 'shamcash_account', 'shamcash_qr', 'payout_account_updated_at']);
        });
    }
};
