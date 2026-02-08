<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'subtitle',
        'image',
        'status',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Get the category that owns the gallery
     */
    public function category()
    {
        return $this->belongsTo(ImageGalleryCategory::class, 'category_id');
    }
}
