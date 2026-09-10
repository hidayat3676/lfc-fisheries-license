<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViolationMedia extends Model
{
    protected $fillable = [
        'violation_report_id',
        'path',
        'media_type',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ViolationReport::class, 'violation_report_id');
    }
}
