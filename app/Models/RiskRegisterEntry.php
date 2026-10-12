<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskRegisterEntry extends Model
{
    protected $table = 'risk_register';
    protected $fillable = [
        'project_id', 'title', 'description', 'severity', 'mitigation',
        'responsible_user_id', 'due_date', 'status',
    ];
    protected $casts = ['due_date' => 'date'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function responsible(): BelongsTo { return $this->belongsTo(User::class, 'responsible_user_id'); }
}
