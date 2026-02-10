<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingStation extends Model
{
    protected $fillable = [
        'village_id',
        'code',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
