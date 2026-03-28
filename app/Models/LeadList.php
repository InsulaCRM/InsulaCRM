<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class LeadList extends Model
{
    protected $table = 'lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'record_count',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'list_leads', 'list_id', 'lead_id');
    }

    public function importLog()
    {
        return $this->hasOne(ImportLog::class, 'list_id');
    }
}
