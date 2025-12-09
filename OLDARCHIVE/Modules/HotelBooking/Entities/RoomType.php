<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\CountryManage\Entities\Country;
use Modules\CountryManage\Entities\State;
use Spatie\Translatable\HasTranslations;

class RoomType extends Model
{
    use HasFactory, HasTranslations;
    protected $fillable = [
        "name",
        "max_guest",
        "max_adult",
        "max_child",
        "no_bedroom",
        "no_living_room",
        "no_bathrooms",
        "base_charge",
        "extra_adult",
        "extra_child",
        "breakfast_price",
        "lunch_price",
        "dinner_price",
        "bed_type_id",
        "extra_bed_type_id",
        "description",
        "hotel_id"
    ];

    protected $with = ["room_type_amenities","bed_type","hotel"];
    protected $translatable = ['name','description'];

    public function room_type_amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class,RoomTypeAmenity::class,"room_type_id","amenity_id","id","id");
    }

    public function bed_type(): HasOne
    {
        return $this->hasOne(BedType::class,"id","bed_type_id");
    }

    public function hotel(): HasOne
    {
        return $this->hasOne(Hotel::class,"id","hotel_id");
    }

    // Access country through hotel
    public function country()
    {
        return $this->hasOneThrough(Country::class,Hotel::class,'id','id','hotel_id','country_id');
    }
    public function state()
    {
        return $this->hasOneThrough(State::class,Hotel::class,'id','id','hotel_id','state_id');
    }

    public function inventory(){
        return $this->hasMany(Inventory::class,"room_type_id");
    }

}
