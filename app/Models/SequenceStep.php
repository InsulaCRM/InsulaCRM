<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SequenceStep extends Model
{
    protected $fillable = [
        'sequence_id',
        'order',
        'delay_days',
        'action_type',
        'message_template',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
