<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUpdate extends Model
{
    protected $fillable = [
        'project_id', 'submitted_by', 'physical_progress_percent',
        'financial_progress_percent', 'narrative', 'approval_status',
        'approved_by', 'approved_at',
    ];
    protected $casts = ['approved_at' => 'datetime'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
