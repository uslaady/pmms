<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'project_id', 'contract_number', 'award_date', 'contract_value',
        'revised_value', 'funding_source_id', 'retention_percent',
    ];

    protected $casts = ['award_date' => 'date'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function fundingSource(): BelongsTo { return $this->belongsTo(FundingSource::class); }
    public function variations(): HasMany { return $this->hasMany(ContractVariation::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $value = $this->revised_value ?? $this->contract_value;
        return max(0, $value - $this->total_paid);
    }
}
