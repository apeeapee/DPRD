<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaPartyResult extends Model
{
    protected $fillable = [
        'election_year_id',
        'area_id',
        'party_id',
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

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
