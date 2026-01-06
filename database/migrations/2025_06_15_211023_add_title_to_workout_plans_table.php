<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTitleToWorkoutPlansTable extends Migration
{
    public function up()
    {
        Schema::table('workout_plans', function (Blueprint $table) {
            $table->string('title')->after('user_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('workout_plans', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
}