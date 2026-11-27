<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $attendance_id
 * @property int $student_id
 * @property \Illuminate\Support\Carbon $attendance_date
 * @property \Illuminate\Support\Carbon|null $check_in
 * @property \Illuminate\Support\Carbon|null $check_out
 * @property string|null $status
 * @property string|null $note
 * @property string|null $day_off_type
 * @property string|null $work_mode
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $location_accuracy
 * @property string|null $location_name
 * @property string|null $location_status
 * @property string|null $mc_file_name
 * @property string|null $mc_mime
 */
class Attendance extends Model
{
    /** Dates and clock times are shown in Malaysia time. */
    public const TIMEZONE = 'Asia/Kuala_Lumpur';

    public const STATUS_PRESENT = 'Present';

    public const STATUS_LEAVE = 'Leave';

    public const STATUS_MISSING = 'Missing';

    public const DAY_OFF_TYPES = ['MC', 'Leave', 'Public holiday'];

    public const WORK_MODES = [
        'Workplace' => 'At workplace',
        'Home' => 'Work from home',
    ];

    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'student_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'note',
        'day_off_type',
        'work_mode',
        'latitude',
        'longitude',
        'location_accuracy',
        'location_name',
        'location_status',
        'mc_file_name',
        'mc_mime',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'location_accuracy' => 'integer',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** @return HasOne<AttendanceFile, $this> */
    public function file(): HasOne
    {
        return $this->hasOne(AttendanceFile::class, 'attendance_id', 'attendance_id');
    }

    public function checkInLocal(): ?string
    {
        return $this->check_in ? $this->check_in->copy()->timezone(self::TIMEZONE)->format('H:i') : null;
    }

    public function checkOutLocal(): ?string
    {
        return $this->check_out ? $this->check_out->copy()->timezone(self::TIMEZONE)->format('H:i') : null;
    }

    public function workedMinutes(?Carbon $until = null): ?int
    {
        if (! $this->check_in) {
            return null;
        }

        $end = $this->check_out ?? $until;

        return $end ? (int) floor(abs($this->check_in->diffInMinutes($end))) : null;
    }

    public function workedLabel(?Carbon $until = null): ?string
    {
        $minutes = $this->workedMinutes($until);

        if ($minutes === null) {
            return null;
        }

        return intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
    }

    /** MC, leave and public holiday are all stored with status "Leave"; day_off_type tells them apart. */
    public function isDayOff(): bool
    {
        return $this->status === self::STATUS_LEAVE;
    }

    public function dayOffLabel(): string
    {
        return $this->day_off_type ?: self::STATUS_LEAVE;
    }

    public function dayOffTitle(): string
    {
        return match ($this->dayOffLabel()) {
            'MC' => 'On MC',
            'Public holiday' => 'Public holiday',
            default => 'On leave',
        };
    }

    public function hasMc(): bool
    {
        return filled($this->mc_file_name);
    }

    /** An unsaved Attachment pointing at the private MC route, so the file preview can show it. */
    public function mcAttachment(): ?Attachment
    {
        if (! $this->hasMc()) {
            return null;
        }

        return new Attachment([
            'file_name' => $this->mc_file_name,
            'file_path' => 'attendance/'.$this->attendance_id.'/mc',
        ]);
    }

    public function workModeLabel(): ?string
    {
        return $this->work_mode ? (self::WORK_MODES[$this->work_mode] ?? $this->work_mode) : null;
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function mapEmbedUrl(): ?string
    {
        if (! $this->hasLocation()) {
            return null;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        $d = 0.003;

        $bbox = implode(',', [round($lng - $d, 5), round($lat - $d, 5), round($lng + $d, 5), round($lat + $d, 5)]);

        return 'https://www.openstreetmap.org/export/embed.html?bbox='.$bbox.'&layer=mapnik&marker='.$lat.','.$lng;
    }

    public function mapLink(): ?string
    {
        if (! $this->hasLocation()) {
            return null;
        }

        return 'https://www.openstreetmap.org/?mlat='.$this->latitude.'&mlon='.$this->longitude.'#map=17/'.$this->latitude.'/'.$this->longitude;
    }

}
