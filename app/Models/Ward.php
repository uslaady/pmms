<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    protected $fillable = ['lga_id', 'name', 'latitude', 'longitude'];

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }
}
