<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class DoNotContact extends Model
{
    protected $table = 'do_not_contact_list';

    protected $fillable = [
        'tenant_id',
        'phone',
        'email',
        'reason',
        'added_by',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function addedByUser()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
