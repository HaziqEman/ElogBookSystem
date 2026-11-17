<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $student_id
 * @property int $lecturer_id
 * @property int|null $supervisor_id
 * @property int|null $university_id
 * @property string $matric_no
 * @property string $name
 * @property string $email
 * @property string $phone_no
 * @property string $course
 * @property string|null $internship_role
 * @property \Illuminate\Support\Carbon|null $internship_start
 * @property int|null $internship_weeks
 * @property string|null $working_days
 * @property string|null $working_schedule
 * @property-read int|null $logbooks_count
 * @property-read \Illuminate\Support\Carbon|null $logbooks_max_created_at
 * @property-read array{key: string, label: string, detail: string|null}|null $internship_state
 */
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

    /** @return BelongsTo<Lecturer, $this> */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(
            Lecturer::class,
            'lecturer_id',
            'lecturer_id'
        );
    }

    /** @return BelongsTo<University, $this> */
    public function university(): BelongsTo
    {
        return $this->belongsTo(
            University::class,
            'university_id',
            'university_id'
        );
    }

    /** @return BelongsTo<Supervisor, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            Supervisor::class,
            'supervisor_id',
            'supervisor_id'
        );
    }

    /** @return HasMany<Logbook, $this> */
    public function logbooks(): HasMany
    {
        return $this->hasMany(
            Logbook::class,
            'student_id'
        );
    }

    /** @return HasMany<Todo, $this> */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class, 'student_id', 'student_id');
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id');
    }
}
