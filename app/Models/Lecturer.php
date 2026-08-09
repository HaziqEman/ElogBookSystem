<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Lecturer extends Authenticatable
{
    protected $primaryKey = 'lecturer_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'faculty'
    ];

    protected $hidden = [
        'password'
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'lecturer_id', 'lecturer_id');
    }
}