<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class DealDocument extends Model
{
    protected $fillable = [
        'tenant_id',
        'deal_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the deal this document belongs to.
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }
}
