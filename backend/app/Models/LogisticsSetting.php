<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'validation_rules',
        'sort_order',
        'is_admin_editable',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'is_admin_editable' => 'boolean',
    ];
}
