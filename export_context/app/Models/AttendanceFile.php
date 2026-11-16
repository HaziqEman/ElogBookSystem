<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceFile extends Model
{
    protected $table = 'attendance_files';

    protected $primaryKey = 'attendance_id';

    public $incrementing = false;

    protected $fillable = [
        'attendance_id',
        'content_base64',
    ];
}