<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('room_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("inventory_id");
            $table->unsignedBigInteger("room_type_id");
            $table->unsignedBigInteger("room_id");
            $table->timestamp("booked_date");
            $table->integer("status")->comment("now it will be empty in future we will make it better");
            $table->foreign("inventory_id")->references("id")->on("inventories")->cascadeOnDelete();
            $table->foreign("room_type_id")->references("id")->on("room_types")->cascadeOnDelete();
            $table->foreign("room_id")->references("id")->on("rooms")->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_inventories');
    }
};
