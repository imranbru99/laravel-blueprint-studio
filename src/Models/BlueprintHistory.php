<?php

namespace Imran\BlueprintStudio\Models;

use Illuminate\Database\Eloquent\Model;

class BlueprintHistory extends Model
{
    protected $table = 'blueprint_studio_history';

    protected $fillable = [
        'action',
        'resource',
        'payload',
        'files',
        'status',
        'message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'files' => 'array',
    ];
}
