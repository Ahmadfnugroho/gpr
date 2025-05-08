<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'direction',      // from_sheet / to_sheet
        'model',          // e.g., "User"
        'model_id',       // ID dari model terkait, jika ada
        'payload',        // data JSON terkait perubahan
        'status',         // success / failed / skipped
        'message',        // pesan error atau log tambahan
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
