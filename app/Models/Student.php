<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Authenticatable
{
    protected $primaryKey = 'student_id';

    protected $fillable = [
        'lecturer_id',
        'supervisor_id',
        'matric_no',
        'name',
        'email',
        'password',
        'phone_no',
        'course',
        'university_id',
        'organization_address',
        'department',
        'internship_start',
        'internship_weeks',
        'working_days',
        'working_schedule',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'internship_start' => 'date',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(
            Lecturer::class,
            'lecturer_id',
            'lecturer_id'
        );
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(
            University::class,
            'university_id',
            'university_id'
        );
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            Supervisor::class,
            'supervisor_id',
            'supervisor_id'
        );
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(
            Logbook::class,
            'student_id'
        );
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class, 'student_id', 'student_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id');
    }
}
