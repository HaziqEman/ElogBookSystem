<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** The company supervisor who assigned this task (null for the student's own tasks). */
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id', 'supervisor_id');
    }

    public function isAssigned(): bool
    {
        return ! empty($this->supervisor_id);
    }
}