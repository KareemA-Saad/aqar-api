<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCityColumnToBookingInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('booking_informations') && !Schema::hasColumn('booking_informations', 'city')) {
            Schema::table('booking_informations', function (Blueprint $table) {
                $table->text('city')->after('state')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_informations', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
}
