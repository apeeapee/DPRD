<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'geojson',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'geojson' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function electionSummaries(): HasMany
    {
        return $this->hasMany(AreaElectionSummary::class, 'area_id');
    }

    public function partyResults(): HasMany
    {
        return $this->hasMany(AreaPartyResult::class, 'area_id');
    }

    public function candidateResults(): HasMany
    {
        return $this->hasMany(AreaCandidateResult::class, 'area_id');
    }
}
