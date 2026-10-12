<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'auditable_type', 'auditable_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Human-readable summary for the audit table's Details column.
     * Pulls the most identifying values out of what was submitted, and
     * resolves the project code for project-scoped routes.
     */
    public function getDetailsAttribute(): string
    {
        $v = $this->new_values ?? [];
        $parts = [];

        if ($this->auditable_id && (str_starts_with((string) $this->auditable_type, 'projects.')
            || str_contains((string) $this->auditable_type, 'project'))) {
            $project = Project::withTrashed()->find($this->auditable_id);
            if ($project) {
                $parts[] = 'Project '.$project->project_code;
            }
        }

        foreach ([
            'project_code' => 'Code', 'name' => 'Name', 'title' => 'Title',
            'contract_number' => 'Contract', 'certificate_number' => 'Certificate',
            'amount' => 'Amount ₦', 'decision' => 'Decision', 'status' => 'Status',
            'type' => 'Type', 'physical_progress_percent' => 'Progress %',
            'inspection_date' => 'Inspection date', 'risk_level' => 'Risk',
        ] as $key => $label) {
            if (isset($v[$key]) && $v[$key] !== '' && ! is_array($v[$key])) {
                $val = $key === 'amount' ? number_format((float) $v[$key], 2) : $v[$key];
                $parts[] = "{$label}: {$val}";
                if (count($parts) >= 4) break; // keep the column compact
            }
        }

        return $parts ? implode(' · ', $parts) : '—';
    }
}
