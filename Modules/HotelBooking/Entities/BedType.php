<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class BedType extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ["name"];
    protected $translatable = ['name'];

    public $timestamps = false;
}
