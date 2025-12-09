<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelBookingPaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reservation_id',
        'booking_information_id',
        'name',
        'email',
        'phone',
        'booking_date',
        'booking_ex',
        'booking_expiry_date',
        'coupon_type',
        'coupon_code',
        'coupon_discount',
        'tax_amount',
        'subtotal',
        'total_amount',
        'payment_gateway',
        'status',
        'payment_status',
        'transaction_id',
        'manual_payment_attachment',
    ];

    protected static function newFactory()
    {
        return \Modules\HotelBooking\Database\factories\HotelBookingPaymentLogFactory::new();
    }
}
