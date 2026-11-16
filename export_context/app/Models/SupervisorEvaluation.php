<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorEvaluation extends Model
{
    protected $fillable = [
        'student_id',
        'supervisor_id',
        'scores',
        'total_marks',
        'max_marks',
        'final_mark',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'scores' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
