<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorDocument extends Model
{
    public const TYPES = [
        'cac_certificate' => 'CAC registration certificate',
        'tax_clearance' => 'Tax clearance certificate',
        'pencom' => 'PENCOM certificate',
        'itf' => 'ITF certificate',
        'nsitf' => 'NSITF certificate',
        'bpp' => 'BPP / IRR certificate',
        'bank_reference' => 'Bank reference',
        'company_profile' => 'Company profile',
        'other' => 'Other',
    ];

    protected $fillable = ['contractor_id', 'title', 'type', 'path', 'original_name', 'size', 'uploaded_by'];

    public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class)->withTrashed(); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Other';
    }
}
