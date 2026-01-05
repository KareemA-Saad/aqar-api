<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations;
    protected $table = 'faqs';
    protected $fillable = ['title','status','is_open','description'];
    protected $translatable = ['title','description'];
}

