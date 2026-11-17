<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecturer extends Authenticatable
{
    protected $primaryKey = 'lecturer_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'faculty',
    ];

    protected $hidden = [
        'password',
    ];

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'lecturer_id', 'lecturer_id');
    }
}
