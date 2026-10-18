<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chat between a coach and their client (instead of WhatsApp), and phone
 * notifications (Web Push) for it and for every other notification.
 */
return new class extends Migration
{
    public function up(): void
    {
        // one conversation per coach and client, kept across renewals
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['coach_id', 'trainee_id']);
            $table->index('trainee_id');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'read_at']);
        });

        // messages that were not sent because they held a number, a link or another app
        Schema::create('chat_blocked_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('reason', 20);
            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
        });

        // the admin can read a conversation only once someone in it reported it
        Schema::create('chat_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique();
            $table->string('public_key', 120);
            $table->string('auth_token', 60);
            $table->timestamps();
        });

        // language of phone notifications
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable();
        });

        // every coach and client who already have a paid subscription can talk
        $pairs = DB::table('subscriptions')->where('status', 'active')
            ->select('coach_id', 'trainee_id')->distinct()->get();
        foreach ($pairs as $pair) {
            DB::table('conversations')->insertOrIgnore([
                'coach_id' => $pair->coach_id, 'trainee_id' => $pair->trainee_id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('chat_reports');
        Schema::dropIfExists('chat_blocked_attempts');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversations');
    }
};
