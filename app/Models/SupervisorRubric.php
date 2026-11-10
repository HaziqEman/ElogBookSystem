<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorRubric extends Model
{
    protected $fillable = [
        'rubric',
        'is_active',
    ];

    protected $casts = [
        'rubric' => 'array',
        'is_active' => 'boolean',
    ];
}
