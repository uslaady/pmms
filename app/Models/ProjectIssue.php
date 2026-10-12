<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectIssue extends Model
{
    protected $fillable = ['project_id', 'title', 'description', 'raised_by', 'status'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function raiser(): BelongsTo { return $this->belongsTo(User::class, 'raised_by'); }
}
