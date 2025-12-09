<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomTypeInventory extends Model
{
    use HasFactory;

    protected $fillable = ["room_type_id","date","quantity","booked_quantity","status"];
    public $timestamps = false;

    public function rooms(){
        return $this->hasMany(Room::class,'room_type_id','room_type_id');
    }

    public function room_booking_information(){
        return $this->hasMany(RoomBookingInformation::class,"room_type_id","room_type_id");
    }

    protected static function newFactory()
    {
        return \Modules\HotelBooking\Database\factories\RoomTypeInventoryFactory::new();
    }
}
