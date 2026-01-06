<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['workout', 'nutrition']); 
            $table->string('title');                         
            $table->text('description')->nullable();   
            $table->longText('plan_details')->nullable();    
            $table->boolean('is_active')->default(true); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_templates');
    }
};