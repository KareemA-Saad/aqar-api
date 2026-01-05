<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_information_id',
        'room_type_id',
        'quantity',
        'unit_price',
        'subtotal',
        'adults',
        'children',
        'meal_options',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'adults' => 'integer',
        'children' => 'integer',
        'meal_options' => 'array',
    ];

    public function bookingInformation(): BelongsTo
    {
        return $this->belongsTo(BookingInformation::class, 'booking_information_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}
