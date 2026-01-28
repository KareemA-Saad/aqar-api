<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormBuilder extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'email',
        'button_text',
        'success_message',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];
}