<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $table = 'app_versions'; // Optional, if table name matches

    protected $fillable = [
        'version',
        'message',
        'is_force_update', // optional: if you want to force users to update
        'platform',        // optional: e.g. android, ios, web
        'released_by',     // optional: who created it
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
    ];
}
