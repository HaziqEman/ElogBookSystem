<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeekSubmission extends Model
{
    protected $table = 'week_submissions';

    protected $primaryKey = 'submission_id';

    protected $fillable = [
        'student_id',
        'week_no',
        'sent_at',
        'reminded_at',
        'reviewed_at',
        'ai_summary',
        'ai_hash',
        'ai_generated_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'reminded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'ai_generated_at' => 'datetime',
        'ai_summary' => 'array',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
