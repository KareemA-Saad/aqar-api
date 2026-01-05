<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $fillable = [
        "room_type_id",
        "date",
        "available_room",
        "booked_room",
        "total_room",
        "extra_base_charge",
        "extra_adult",
        "extra_child",
        "status"
    ];

    public $timestamps = false;

    public function roomInventory(){
        return $this->hasMany(RoomInventory::class,"inventory_id","id");
    }

    public function room_type(){
        return $this->belongsTo(Room_type::class,"id","room_type_id");
    }
}
