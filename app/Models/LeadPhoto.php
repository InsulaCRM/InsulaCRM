<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LeadPhoto extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_id',
        'uploaded_by',
        'filename',
        'original_name',
        'path',
        'thumbnail_path',
        'mime_type',
        'size',
        'caption',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($photo) {
            if (empty($photo->tenant_id) && auth()->check()) {
                $photo->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }
        return $this->url;
    }
}
