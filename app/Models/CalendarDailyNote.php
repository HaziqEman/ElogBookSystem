<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarDailyNote extends Model
{
    protected $primaryKey = 'daily_note_id';

    protected $fillable = [
        'student_id',
        'note_date',
        'body',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** @return HasMany<CalendarDailyNoteImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(CalendarDailyNoteImage::class, 'daily_note_id', 'daily_note_id');
    }
}
