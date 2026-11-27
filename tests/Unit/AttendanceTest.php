<?php

namespace Tests\Unit;

use App\Models\Attendance;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    public function test_it_formats_local_clock_times_and_work_duration(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 01:45:00', 'UTC'));

        $attendance = new Attendance([
            'check_in' => Carbon::parse('2026-11-17 01:15:00', 'UTC'),
            'check_out' => Carbon::parse('2026-11-17 03:00:00', 'UTC'),
        ]);

        $this->assertSame('09:15', $attendance->checkInLocal());
        $this->assertSame('11:00', $attendance->checkOutLocal());
        $this->assertSame(105, $attendance->workedMinutes());
        $this->assertSame('1h 45m', $attendance->workedLabel());

        $incomplete = new Attendance(['check_in' => Carbon::parse('2026-11-17 01:15:00', 'UTC')]);
        $this->assertNull($incomplete->workedMinutes());
        $this->assertNull($incomplete->workedLabel());
        $until = Carbon::parse('2026-11-17 01:45:00', 'UTC');
        $this->assertSame(30, $incomplete->workedMinutes($until));
        $this->assertSame('0h 30m', $incomplete->workedLabel($until));
    }

    public function test_it_formats_day_off_work_mode_and_mc_attachment_details(): void
    {
        $attendance = new Attendance([
            'status' => Attendance::STATUS_LEAVE,
            'day_off_type' => 'MC',
            'work_mode' => 'Home',
            'mc_file_name' => 'medical-note.pdf',
        ]);
        $attendance->attendance_id = 42;

        $this->assertTrue($attendance->isDayOff());
        $this->assertSame('MC', $attendance->dayOffLabel());
        $this->assertSame('On MC', $attendance->dayOffTitle());
        $this->assertSame('Work from home', $attendance->workModeLabel());
        $this->assertTrue($attendance->hasMc());
        $this->assertSame('medical-note.pdf', $attendance->mcAttachment()->file_name);
        $this->assertSame('attendance/42/mc', $attendance->mcAttachment()->file_path);

        $leave = new Attendance(['status' => Attendance::STATUS_LEAVE]);
        $this->assertSame('Leave', $leave->dayOffLabel());
        $this->assertSame('On leave', $leave->dayOffTitle());
        $this->assertFalse($leave->hasMc());
        $this->assertNull($leave->mcAttachment());
    }

    public function test_it_builds_map_urls_only_when_both_coordinates_are_present(): void
    {
        $attendance = new Attendance([
            'latitude' => 3.139,
            'longitude' => 101.6869,
        ]);

        $this->assertTrue($attendance->hasLocation());
        $this->assertSame(
            'https://www.openstreetmap.org/export/embed.html?bbox=101.6839,3.136,101.6899,3.142&layer=mapnik&marker=3.139,101.6869',
            $attendance->mapEmbedUrl()
        );
        $this->assertSame(
            'https://www.openstreetmap.org/?mlat=3.139&mlon=101.6869#map=17/3.139/101.6869',
            $attendance->mapLink()
        );

        $withoutLongitude = new Attendance(['latitude' => 3.139]);
        $this->assertFalse($withoutLongitude->hasLocation());
        $this->assertNull($withoutLongitude->mapEmbedUrl());
        $this->assertNull($withoutLongitude->mapLink());
    }
}
