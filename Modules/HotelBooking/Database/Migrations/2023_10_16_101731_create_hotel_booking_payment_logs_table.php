<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelBookingPaymentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('hotel_booking_payment_logs')) {
            Schema::create('hotel_booking_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_information_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reservation_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->date('booking_date');
            $table->date('booking_expiry_date');
            $table->boolean('booking_status')->default(1);
            $table->boolean('payment_status')->default(1);
            $table->string('coupon_type')->nullable();
            $table->string('coupon_code')->nullable();
            $table->double('coupon_discount')->nullable();
            $table->double('tax_amount')->nullable();
            $table->double('subtotal')->nullable();
            $table->double('total_amount');
            $table->string('payment_gateway')->nullable();
            $table->string('status')->default("pending");
            $table->text('transaction_id')->nullable();
            $table->string('manual_payment_attachment')->nullable();
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
        Schema::dropIfExists('hotel_booking_payment_logs');
    }
}
