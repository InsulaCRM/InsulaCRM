<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $table = 'imports_log';

    protected $fillable = [
        'tenant_id',
        'list_id',
        'user_id',
        'filename',
        'status',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'duplicate_rows',
        'error_message',
        'dedupe_strategy',
        'updated_rows',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function list()
    {
        return $this->belongsTo(LeadList::class, 'list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
