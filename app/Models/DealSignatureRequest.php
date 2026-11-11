<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class DealSignatureRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'deal_id',
        'generated_document_id',
        'document_type',
        'title',
        'status',
        'opensign_object_id',
        'signers',
        'signing_urls',
        'source_pdf_path',
        'signed_pdf_path',
        'certificate_path',
        'archive_directory',
        'external_file_url',
        'external_certificate_url',
        'declined_reason',
        'last_event_payload',
        'last_event',
        'sent_at',
        'completed_at',
        'declined_at',
        'expires_at',
        'last_event_at',
    ];

    protected function casts(): array
    {
        return [
            'signers' => 'array',
            'signing_urls' => 'array',
            'last_event_payload' => 'array',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
            'declined_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_event_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function generatedDocument()
    {
        return $this->belongsTo(GeneratedDocument::class);
    }
}
