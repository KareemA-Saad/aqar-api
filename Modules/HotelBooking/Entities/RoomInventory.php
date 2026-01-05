<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        "inventory_id",
        "room_type_id",
        "room_id",
        "user_id",
        "booked_date",
        "status",
    ];

    public $timestamps = false;

    public function inventories(){
        return $this->belongsTo(Inventory::class,"inventory_id","id");
    }
}
