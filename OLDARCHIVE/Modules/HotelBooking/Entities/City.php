<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ["name", "status", "title", "description"];
    public $timestamps = false;
    protected $translatable = ['name','title','description'];

    public function hotels(){
        return $this->hasMany(Hotel::class,"city_id","id");
    }

    protected static function newFactory()
    {
        return \Modules\HotelBooking\Database\factories\CityFactory::new();
    }
}
