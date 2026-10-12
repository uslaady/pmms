<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'contract_id', 'certificate_number', 'amount', 'retention_held',
        'payment_date', 'status', 'recorded_by', 'approved_by',
    ];
    protected $casts = ['payment_date' => 'date'];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
