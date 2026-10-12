<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    protected $fillable = [
        'project_id', 'milestone_id', 'inspection_id', 'path', 'stage',
        'gps_lat', 'gps_lng', 'caption', 'uploaded_by',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function milestone(): BelongsTo { return $this->belongsTo(ProjectMilestone::class, 'milestone_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
