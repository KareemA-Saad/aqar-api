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

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->json("name");
            $table->string("slug");
            $table->unsignedBigInteger("room_type_id");
            $table->decimal("base_cost");
            $table->string("duration")->nullable();
            $table->integer("share_value")->nullable();
            $table->text("description");
            $table->foreign("room_type_id")->references("id")->on("room_types")->noActionOnDelete();
            $table->integer('country_id');
            $table->integer('state_id');
            $table->json("location");
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->boolean('status')->default(1);
            $table->string('type');
            $table->boolean('is_featured');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
