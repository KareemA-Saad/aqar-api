<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelRoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["name"];
    public $timestamps = false;
    protected $dates = ["deleted_at"];
    protected $table = "hotel_room_type";
}
