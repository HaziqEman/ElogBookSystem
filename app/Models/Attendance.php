<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /** Dates and clock times are shown in Malaysia time. */
    public const TIMEZONE = 'Asia/Kuala_Lumpur';

    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'student_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function checkInLocal(): ?string
    {
        return $this->check_in ? $this->check_in->copy()->timezone(self::TIMEZONE)->format('H:i') : null;
    }

    public function checkOutLocal(): ?string
    {
        return $this->check_out ? $this->check_out->copy()->timezone(self::TIMEZONE)->format('H:i') : null;
    }

    public function workedMinutes(): ?int
    {
        if (! $this->check_in || ! $this->check_out) {
            return null;
        }

        return (int) floor(abs($this->check_in->diffInMinutes($this->check_out)));
    }

    public function workedLabel(): ?string
    {
        $minutes = $this->workedMinutes();

        if ($minutes === null) {
            return null;
        }

        return intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
    }
}