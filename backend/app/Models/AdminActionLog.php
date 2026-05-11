<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'target_type',
        'target_id',
        'before_json',
        'after_json',
        'metadata_json',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
        'metadata_json' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
