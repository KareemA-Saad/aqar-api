<?php

namespace Modules\Service\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ServiceCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['title','status'];
    private $translatable = ['title'];

    protected static function newFactory()
    {
        return \Modules\Service\Database\factories\ServiceCategoryFactory::new();
    }

    /**
     * Get all services for this category.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
