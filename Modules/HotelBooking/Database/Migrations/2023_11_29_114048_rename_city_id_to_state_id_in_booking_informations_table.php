<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameCityIdToStateIdInBookingInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_informations', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->renameColumn('city_id', 'state_id');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_informations', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
        });
    }
}
