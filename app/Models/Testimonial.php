<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'designation',
        'company',
        'description',
        'image',
        'status',
    ];

    public $translatable = ['name', 'designation', 'company', 'description'];

    protected $casts = [
        'status' => 'boolean',
    ];
}