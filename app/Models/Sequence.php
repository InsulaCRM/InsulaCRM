<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function steps()
    {
        return $this->hasMany(SequenceStep::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(SequenceEnrollment::class);
    }
}
