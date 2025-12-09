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

        Schema::create('address', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('country_id');
            $table->bigInteger('state_id');
            $table->string('city');
            $table->string('street');
            $table->string('postal_code');
            $table->string('email');
            $table->string('mobile');
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
        Schema::dropIfExists('address');
    }
};
