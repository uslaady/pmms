<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['name'];

    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
