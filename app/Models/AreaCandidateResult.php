<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaCandidateResult extends Model
{
    protected $fillable = [
        'election_year_id',
        'area_id',
        'candidate_id',
        'votes',
    ];

    protected $casts = [
        'votes' => 'integer',
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(ElectionYear::class, 'election_year_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
