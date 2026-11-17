<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $primaryKey = 'feedback_id';

    protected $table = 'feedback';

    protected $fillable = [
        'logbook_id',
        'lecturer_id',
        'supervisor_id',
        'comment',
        'feedback_date',
    ];

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class, 'logbook_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }
}
