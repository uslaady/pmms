<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    protected $fillable = [
        'project_id', 'officer_id', 'inspection_date', 'gps_lat', 'gps_lng',
        'weather', 'observations', 'recommendations', 'risk_level', 'score', 'next_inspection_date',
    ];
    protected $casts = ['inspection_date' => 'date', 'next_inspection_date' => 'date'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function officer(): BelongsTo { return $this->belongsTo(User::class, 'officer_id'); }
    public function images(): HasMany { return $this->hasMany(ProjectImage::class); }
}
