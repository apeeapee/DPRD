<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaElectionSummary extends Model
{
    protected $fillable = [
        'election_year_id',
        'area_id',
        'registered_voters',
        'votes_cast',
        'valid_votes',
        'invalid_votes',
    ];

    protected $casts = [
        'registered_voters' => 'integer',
        'votes_cast'        => 'integer',
        'valid_votes'       => 'integer',
        'invalid_votes'     => 'integer',
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(ElectionYear::class, 'election_year_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
