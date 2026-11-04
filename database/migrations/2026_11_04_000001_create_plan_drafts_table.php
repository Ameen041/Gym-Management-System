<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// What a coach typed in a plan builder and has not sent yet, saved as they go
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // workout | nutrition
            $table->longText('data');
            $table->timestamps();
            $table->unique(['subscription_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_drafts');
    }
};
