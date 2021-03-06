<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventUser extends Pivot {
    protected $fillable = [
        'event_id',
        'user_id'
    ];

    protected $casts = [
        'event_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
