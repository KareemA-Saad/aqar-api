<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    protected $table = 'newsletters';
    protected $fillable = ['email','token','verified'];
}
