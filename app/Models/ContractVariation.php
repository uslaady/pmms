<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVariation extends Model
{
    protected $fillable = ['contract_id', 'type', 'amount', 'extra_days', 'reason', 'date', 'approved_by'];
    protected $casts = ['date' => 'date'];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
