<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class GeneratedDocument extends Model
{
    protected $fillable = [
        'tenant_id',
        'deal_id',
        'template_id',
        'user_id',
        'name',
        'content',
        'pdf_path',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
