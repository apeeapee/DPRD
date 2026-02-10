<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TpsCandidateVote extends Model
{
    protected $fillable = [
        'election_year_id',
        'polling_station_id',
        'candidate_id',
        'votes',
    ];

    protected $casts = [
        'votes' => 'integer',
    ];
}
