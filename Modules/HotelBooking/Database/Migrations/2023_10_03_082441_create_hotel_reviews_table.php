<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('hotel_reviews')) {
            Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId("hotel_id")->constrained();
            $table->foreignId("room_id")->constrained();
            $table->foreignId("user_id")->constrained();
            $table->float("ratting");
            $table->float("cleanliness");
            $table->float("comfort");
            $table->float("staff");
            $table->float("facilities");
            $table->tinyText("description");
            $table->timestamps();
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
        Schema::dropIfExists('hotel_reviews');
    }
}
