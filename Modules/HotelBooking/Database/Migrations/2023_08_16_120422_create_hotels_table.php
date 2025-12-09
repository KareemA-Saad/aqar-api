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

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->integer('state_id');
            $table->integer('country_id');
            $table->text('name');
            $table->text('slug');
            $table->text('distance')->nullable();
            $table->string("restaurant_inside")->nullable();
            $table->text("location",'500');
            $table->text("about");
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
        Schema::dropIfExists('hotels');
    }
};
