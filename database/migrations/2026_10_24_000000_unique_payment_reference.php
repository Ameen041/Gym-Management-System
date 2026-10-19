<?php

use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A Sham Cash transaction number can pay for one subscription only. */
return new class extends Migration
{
    public function up(): void
    {
        // the same spelling everywhere first; an older duplicate keeps a mark so the index can be added
        $seen = [];
        foreach (DB::table('subscriptions')->whereNotNull('payment_reference')->orderBy('id')->get(['id', 'payment_reference']) as $row) {
            $reference = Subscription::normalizeReference($row->payment_reference) ?: null;
            if ($reference !== null && isset($seen[$reference])) {
                $reference .= '-DUP' . $row->id;
            }
            $seen[$reference] = true;
            DB::table('subscriptions')->where('id', $row->id)->update(['payment_reference' => $reference]);
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unique('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
        });
    }
};
