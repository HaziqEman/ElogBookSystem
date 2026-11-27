<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    protected $primaryKey = 'todo_id';

    protected $fillable = [
        'student_id',
        'supervisor_id',
        'title',
        'due_date',
        'is_done',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_done' => 'boolean',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** The company supervisor who assigned this task (null for the student's own tasks). */
    /** @return BelongsTo<Supervisor, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id', 'supervisor_id');
    }

    public function scopeCurrentOpen(Builder $query, string $today): Builder
    {
        return $query->where('is_done', false)
            ->where(fn (Builder $query) => $query->whereNull('due_date')->orWhereDate('due_date', '>=', $today));
    }

    public function scopeOverdueAsOf(Builder $query, string $today): Builder
    {
        return $query->where('is_done', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_done', true);
    }

    public function isAssigned(): bool
    {
        return ! empty($this->supervisor_id);
    }
}
