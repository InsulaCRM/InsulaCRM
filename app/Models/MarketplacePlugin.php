<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplacePlugin extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'author',
        'description',
        'category',
        'version',
        'download_url',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }
}
