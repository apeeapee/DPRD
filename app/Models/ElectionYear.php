<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionYear extends Model
{
    protected $fillable = ['year'];

    public function summaries(): HasMany
    {
        return $this->hasMany(AreaElectionSummary::class);
    }

    public function partyResults(): HasMany
    {
        return $this->hasMany(AreaPartyResult::class);
    }

    public function candidateResults(): HasMany
    {
        return $this->hasMany(AreaCandidateResult::class);
    }
}
