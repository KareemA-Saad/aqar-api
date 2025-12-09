<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel_roomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["name"];
    public $timestamps = false;
    protected $dates = ["deleted_at"];
    protected $table = "hotel_room_type";
}
