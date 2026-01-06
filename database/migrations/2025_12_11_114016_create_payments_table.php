<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // لأي مستخدم هالدفعة؟
            $table->unsignedBigInteger('user_id');

            // كم دفع؟
            $table->decimal('amount', 8, 2); // مثال: 49.99

            // طريقة الدفع: Cash / Bank / Transfer ...
            $table->string('method', 50)->nullable();

            // تاريخ الدفع الفعلي
            $table->date('paid_at');

            // مدة الاشتراك اللي غطّتها هالدفعة
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // رقم إيصال / مرجع (اختياري)
            $table->string('reference', 100)->nullable();

            // ملاحظات إضافية
            $table->text('notes')->nullable();

            $table->timestamps();

            // علاقة مع users
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};