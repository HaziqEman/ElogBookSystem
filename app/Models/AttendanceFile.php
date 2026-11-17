<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $attendance_id
 * @property string $content_base64
 */
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
