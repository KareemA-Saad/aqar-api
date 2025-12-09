<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->text("name");
            $table->boolean("smoking")->default(false);
            $table->integer("max_guest")->nullable()->default(0);
            $table->integer("max_adult")->nullable()->default(0);
            $table->integer("max_child")->nullable()->default(0);
            $table->integer("no_bedroom")->nullable()->default(0);
            $table->integer("no_living_room")->nullable()->default(0);
            $table->integer("no_bathrooms")->nullable()->default(0);
            $table->decimal("base_charge")->nullable();
            $table->integer("extra_adult")->nullable();
            $table->integer("extra_child")->nullable();
            $table->decimal("breakfast_price")->nullable();
            $table->decimal("lunch_price")->nullable();
            $table->decimal("dinner_price")->nullable();
            $table->unsignedBigInteger("bed_type_id")->nullable();
            $table->unsignedBigInteger("extra_bed_type_id")->nullable();
            $table->unsignedBigInteger("hotel_id")->nullable();
            $table->text("description")->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
