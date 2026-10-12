<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id', 'name', 'sequence', 'planned_date', 'actual_date', 'status', 'comments',
    ];
    protected $casts = ['planned_date' => 'date', 'actual_date' => 'date'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function images(): HasMany { return $this->hasMany(ProjectImage::class, 'milestone_id'); }
}
