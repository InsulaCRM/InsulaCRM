<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['tenant_id', 'name', 'color'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function leads()
    {
        return $this->morphedByMany(Lead::class, 'taggable');
    }

    public function deals()
    {
        return $this->morphedByMany(Deal::class, 'taggable');
    }
}
