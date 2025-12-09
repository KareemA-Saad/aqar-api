<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->cascadeOnDelete();
            $table->timestamp("date")->nullable()->default(null);
            $table->integer("available_room");
            $table->integer("booked_room")->nullable();
            $table->integer("total_room")->nullable();
            $table->decimal("extra_base_charge")->nullable();
            $table->decimal("extra_adult")->nullable();
            $table->decimal("extra_child")->nullable();
            $table->integer("min_night")->nullable();
            $table->string("bookable")->nullable();
            $table->string("refundable")->nullable();
            $table->integer("status")->comment("0 = not bookable and 1 = bookable");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventories');
    }
};
