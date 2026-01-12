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

        if (!Schema::hasTable('hotel_benifits')) {
            Schema::create('hotel_benifits', function (Blueprint $table) {
            $table->increments('id')->foreign('hotels.id');
            $table->string('icon');
            $table->longText('description');
            $table->integer('hotel_id');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_benifits');
    }
};
