<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class TransactionChecklist extends Model
{
    protected $table = 'transaction_checklists';

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'waived' => 'Waived',
        'failed' => 'Failed',
    ];

    public const DEFAULT_ITEMS = [
        ['item_key' => 'earnest_money_deposit', 'label' => 'Earnest Money Deposit', 'sort_order' => 1],
        ['item_key' => 'inspection', 'label' => 'Home Inspection', 'sort_order' => 2],
        ['item_key' => 'appraisal', 'label' => 'Appraisal', 'sort_order' => 3],
        ['item_key' => 'financing', 'label' => 'Financing Contingency', 'sort_order' => 4],
        ['item_key' => 'title_search', 'label' => 'Title Search & Insurance', 'sort_order' => 5],
        ['item_key' => 'survey', 'label' => 'Property Survey', 'sort_order' => 6],
        ['item_key' => 'hoa_docs', 'label' => 'HOA Documents Review', 'sort_order' => 7],
        ['item_key' => 'home_warranty', 'label' => 'Home Warranty', 'sort_order' => 8],
        ['item_key' => 'final_walkthrough', 'label' => 'Final Walkthrough', 'sort_order' => 9],
    ];

    protected $fillable = [
        'tenant_id',
        'deal_id',
        'item_key',
        'label',
        'status',
        'deadline',
        'completed_at',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'completed_at' => 'datetime',
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

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->deadline) {
            return false;
        }

        return $this->deadline->isPast() && !in_array($this->status, ['completed', 'waived']);
    }
}
