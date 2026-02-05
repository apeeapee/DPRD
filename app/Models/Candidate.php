<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'party_id',
        'name',
        'number',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function areaResults(): HasMany
    {
        return $this->hasMany(AreaCandidateResult::class);
    }
}
