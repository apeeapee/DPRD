<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VillageVoteSummary extends Model
{
    protected $fillable = [
        'election_year_id',
        'village_id',
        'votes_cast',
    ];

    protected $casts = [
        'votes_cast' => 'integer',
    ];
}
