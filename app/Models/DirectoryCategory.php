<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectoryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
    ];

    /**
     * Get all directory business listings within this category.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(DirectoryListing::class, 'category_id');
    }
}
