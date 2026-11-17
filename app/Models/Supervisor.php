<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $supervisor_id
 * @property string $name
 * @property string $email
 * @property string|null $company_name
 * @property string|null $position
 * @property string|null $phone
 * @property int|null $waiting_count
 */
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

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'supervisor_id', 'supervisor_id');
    }
}
