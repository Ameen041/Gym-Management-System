<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('muscle_group', 50);
            $table->string('equipment', 100)->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable(); // one step per line
            $table->text('tips')->nullable();         // one tip per line
            $table->string('video_url')->nullable();  // YouTube / Vimeo / direct link
            $table->string('video_path')->nullable(); // uploaded video file
            $table->string('image_path')->nullable(); // thumbnail
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['muscle_group', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
