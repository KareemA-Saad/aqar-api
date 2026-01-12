<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddKeyColumnToAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('appointment_days') && !Schema::hasColumn('appointment_days', 'key')) {
            Schema::table('appointment_days', function (Blueprint $table) {
                $table->string("key")->nullable()->after("day");
            });
        }
    }
}
