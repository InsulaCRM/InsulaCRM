<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealBuyerMatch extends Model
{
    protected $fillable = [
        'deal_id',
        'buyer_id',
        'match_score',
        'notified_at',
        'responded_at',
        'status',
        'outreach_status',
        'buyer_notes',
        'last_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
            'responded_at' => 'datetime',
            'last_contacted_at' => 'datetime',
        ];
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }
}
