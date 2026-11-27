<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CalendarDailyNoteImage extends Model
{
    protected $primaryKey = 'daily_note_image_id';

    protected $fillable = [
        'daily_note_id',
        'file_name',
        'file_path',
    ];

    /** @return BelongsTo<CalendarDailyNote, $this> */
    public function dailyNote(): BelongsTo
    {
        return $this->belongsTo(CalendarDailyNote::class, 'daily_note_id', 'daily_note_id');
    }

    public function getUrlAttribute(): string
    {
        $path = (string) $this->file_path;

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : '/'.ltrim($path, '/');
    }
}
