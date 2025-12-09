<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class HotelReview extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = ['description'];
    protected $fillable = [
        "hotel_id",
        "user_id",
        "room_id",
        "ratting",
        "cleanliness",
        "comfort",
        "staff",
        "facilities",
        "description",
        "created_at",
    ];

    public $timestamps = false;

//    public function User(){
//        return $this->belongsTo(U::class);
//    }

    public function hotel(){
        return $this->belongsTo(Hotel::class);
    }
}



