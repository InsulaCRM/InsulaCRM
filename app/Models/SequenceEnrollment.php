<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class SequenceEnrollment extends Model
{
    protected $fillable = [
        'tenant_id',
        'sequence_id',
        'lead_id',
        'current_step',
        'last_step_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'last_step_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
