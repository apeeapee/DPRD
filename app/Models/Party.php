<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    protected $fillable = [
        'code',
        'name',
        'color', // optional
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function areaResults(): HasMany
    {
        return $this->hasMany(AreaPartyResult::class);
    }
}
