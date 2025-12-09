<?php

namespace Modules\HotelBooking\Entities;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\CountryManage\Entities\Country;
use Modules\CountryManage\Entities\State;
use Spatie\Translatable\HasTranslations;


class Hotel extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ["name","distance","restaurant_inside","location","about","slug"];
    protected $with = ["hotel_amenities","hotel_images"];
//    protected $table = ["hotels"];
    protected $translatable = ['name','location','about','distance'];


    public function hotel_amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class,HotelAmenity::class,'hotel_id','amenities_id','id','id');
    }

    public function hotel_images(){
        return $this->hasMany(HotelImage::class,"hotel_id","id");
    }

    public function room_type(){
        return $this->hasMany(RoomType::class,"hotel_id","id");
    }

    public function review(){
        return $this->hasMany(HotelReview::class);
    }

    public function state(){
        return $this->belongsTo(State::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

}
