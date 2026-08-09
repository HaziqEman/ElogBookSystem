<?php

namespace App\Models;

use App\Models\Logbook;
use App\Models\Lecturer;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    protected $primaryKey = 'student_id';

    protected $fillable = [
        'lecturer_id',
        'matric_no',
        'name',
        'email',
        'password',
        'phone_no',
        'course'
    ];

    protected $hidden = [
        'password'
    ];

    public function lecturer()
    {
        return $this->belongsTo(
            Lecturer::class,
            'lecturer_id',
            'lecturer_id'
        );
    }

    public function logbooks()
    {
        return $this->hasMany(
            Logbook::class,
            'student_id'
        );
    }
}