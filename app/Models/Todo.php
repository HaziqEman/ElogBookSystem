<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $primaryKey = 'todo_id';

    protected $fillable = [
        'student_id',
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
}