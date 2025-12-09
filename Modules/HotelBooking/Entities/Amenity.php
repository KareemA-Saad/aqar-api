<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Amenity extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ["name","icon"];
    protected $translatable = ['name'];

    public $timestamps = false;
}
