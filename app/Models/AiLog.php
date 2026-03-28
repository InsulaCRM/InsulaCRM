<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiLog extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'model_type',
        'model_id',
        'prompt_summary',
        'result',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    public function getSubjectUrlAttribute(): ?string
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }

        return match ($this->model_type) {
            Lead::class => route('leads.show', $this->model_id),
            Deal::class => route('deals.show', $this->model_id),
            Property::class => route('properties.show', $this->model_id),
            Buyer::class => route('buyers.show', $this->model_id),
            Campaign::class => route('campaigns.show', $this->model_id),
            default => null,
        };
    }

    public function getSubjectLabelAttribute(): ?string
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }

        $basename = class_basename($this->model_type);
        $name = $this->subject?->full_name ?? $this->subject?->title ?? $this->subject?->name ?? null;

        return $name ? "{$basename}: {$name}" : "{$basename} #{$this->model_id}";
    }

    public static function record(string $type, string $result, array $options = []): static
    {
        return static::create([
            'tenant_id' => $options['tenant_id'] ?? (auth()->check() ? auth()->user()->tenant_id : null),
            'user_id' => $options['user_id'] ?? (auth()->check() ? auth()->id() : null),
            'type' => $type,
            'model_type' => $options['model_type'] ?? null,
            'model_id' => $options['model_id'] ?? null,
            'prompt_summary' => $options['prompt_summary'] ?? null,
            'result' => $result,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }
}
