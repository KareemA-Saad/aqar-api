<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Temporary room holds during checkout process.
     * Holds expire after 15 minutes and are cleaned up lazily.
     */
    public function up(): void
    {
        Schema::create('room_holds', function (Blueprint $table) {
            $table->id();
            $table->string('hold_token', 64)->unique()->comment('Unique token for the hold session');
            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['room_type_id', 'check_in_date', 'check_out_date']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_holds');
    }
};
