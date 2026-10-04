<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Supervisor extends Authenticatable
{
    protected $table = 'supervisors';

    protected $primaryKey = 'supervisor_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'position',
        'phone',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'must_change_password' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'supervisor_id', 'supervisor_id');
    }
}
