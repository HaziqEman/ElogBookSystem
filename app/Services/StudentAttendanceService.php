<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceFile;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAttendanceService
{
    /**
     * @return array{
     *     attendance: Attendance|null,
     *     monthPresent: int,
     *     monthLeave: int,
     *     monthMinutes: int,
     *     records: Collection<int, Attendance>,
     *     presentCount: int,
     *     offCount: int
     * }
     */
    public function indexData(Student $student, Carbon $today): array
    {
        $monthRecords = Attendance::where('student_id', $student->student_id)
            ->whereDate('attendance_date', '>=', $today->copy()->startOfMonth()->toDateString())
            ->whereDate('attendance_date', '<=', $today->copy()->endOfMonth()->toDateString())
            ->get();

        $todayKey = $today->toDateString();
        $attendance = $monthRecords->first(fn ($record) => $record->attendance_date->toDateString() === $todayKey);
        $monthPresent = $monthRecords->where('status', Attendance::STATUS_PRESENT)->count();
        $monthLeave = $monthRecords->where('status', Attendance::STATUS_LEAVE)->count();
        $monthMinutes = (int) $monthRecords->sum(fn ($record) => $record->workedMinutes() ?? 0);

        $records = Attendance::where('student_id', $student->student_id)
            ->orderByDesc('attendance_date')
            ->take(60)
            ->get();
        $offCount = $records->where('status', Attendance::STATUS_LEAVE)->count();

        return [
            'attendance' => $attendance,
            'monthPresent' => $monthPresent,
            'monthLeave' => $monthLeave,
            'monthMinutes' => $monthMinutes,
            'records' => $records,
            'presentCount' => $records->count() - $offCount,
            'offCount' => $offCount,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string}
     */
    public function clockIn(Student $student, array $data, string $todayDate): array
    {
        if ($data['work_mode'] === 'Home') {
            $data['location_status'] = 'unavailable';
            $data['latitude'] = null;
            $data['longitude'] = null;
            $data['accuracy'] = null;
        }

        $attendance = Attendance::where('student_id', $student->student_id)
            ->whereDate('attendance_date', $todayDate)
            ->first();

        if (! $attendance) {
            $attendance = new Attendance([
                'student_id' => $student->student_id,
                'attendance_date' => $todayDate,
            ]);
        }

        if ($attendance->exists && $attendance->isDayOff()) {
            return [
                'ok' => false,
                'message' => 'Today is marked as '.$attendance->dayOffLabel().'. Press Change first if you want to clock in.',
            ];
        }

        if ($attendance->check_in) {
            return [
                'ok' => false,
                'message' => 'You already clocked in today at '.$attendance->checkInLocal().'.',
            ];
        }

        $shared = ($data['location_status'] ?? null) === 'shared'
            && isset($data['latitude'], $data['longitude']);

        $latitude = $shared ? round((float) $data['latitude'], 4) : null;
        $longitude = $shared ? round((float) $data['longitude'], 4) : null;

        $attendance->status = Attendance::STATUS_PRESENT;
        $attendance->day_off_type = null;
        $attendance->note = null;
        $attendance->work_mode = $data['work_mode'];
        $attendance->latitude = $latitude;
        $attendance->longitude = $longitude;
        $attendance->location_accuracy = $shared && isset($data['accuracy']) ? (int) round((float) $data['accuracy']) : null;
        $attendance->location_name = $shared ? $this->placeName($latitude, $longitude) : null;
        $attendance->location_status = $shared ? 'shared' : ($data['location_status'] ?? 'unavailable');
        $attendance->check_in = now();
        $attendance->check_out = null;
        $attendance->save();

        return ['ok' => true, 'message' => 'Clocked in at '.$attendance->checkInLocal().'.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function clockOut(Student $student, string $today): array
    {
        $attendance = Attendance::where('student_id', $student->student_id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in) {
            return ['ok' => false, 'message' => 'Please clock in first.'];
        }

        if ($attendance->check_out) {
            return [
                'ok' => false,
                'message' => 'You already clocked out today at '.$attendance->checkOutLocal().'.',
            ];
        }

        $attendance->check_out = now();
        $attendance->save();

        return [
            'ok' => true,
            'message' => 'Clocked out at '.$attendance->checkOutLocal().'. Worked '.$attendance->workedLabel().'.',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveDayOff(Student $student, array $data, ?UploadedFile $file, ?string $mime): void
    {
        DB::transaction(function () use ($student, $data, $file, $mime): void {
            $leaveDate = Carbon::parse($data['leave_date'])->toDateString();
            $attendance = Attendance::where('student_id', $student->student_id)
                ->whereDate('attendance_date', $leaveDate)
                ->first();

            if (! $attendance) {
                $attendance = new Attendance([
                    'student_id' => $student->student_id,
                    'attendance_date' => $leaveDate,
                ]);
            }

            if ($attendance->check_in) {
                throw ValidationException::withMessages([
                    'leave_date' => 'You already clocked in on that day, so it cannot be marked as a day off.',
                ]);
            }

            $attendance->status = Attendance::STATUS_LEAVE;
            $attendance->day_off_type = $data['day_off_type'];
            $attendance->note = isset($data['note']) ? trim($data['note']) : null;
            $attendance->check_in = null;
            $attendance->check_out = null;
            $attendance->work_mode = null;
            $attendance->latitude = null;
            $attendance->longitude = null;
            $attendance->location_accuracy = null;
            $attendance->location_name = null;
            $attendance->location_status = null;
            $attendance->mc_file_name = $file ? Str::limit(basename($file->getClientOriginalName()), 200, '') : null;
            $attendance->mc_mime = $file ? $mime : null;
            $attendance->save();

            if ($file) {
                AttendanceFile::updateOrCreate(
                    ['attendance_id' => $attendance->attendance_id],
                    ['content_base64' => base64_encode((string) file_get_contents($file->getRealPath()))]
                );
            } else {
                AttendanceFile::where('attendance_id', $attendance->attendance_id)->delete();
            }
        });
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function cancelDayOff(Student $student, string $id, string $today): array
    {
        $attendance = Attendance::where('student_id', $student->student_id)
            ->where('attendance_id', $id)
            ->where('status', Attendance::STATUS_LEAVE)
            ->firstOrFail();

        if ($attendance->attendance_date->toDateString() < $today) {
            return ['ok' => false, 'message' => 'Days that have already passed cannot be changed.'];
        }

        $attendance->delete();

        return ['ok' => true, 'message' => 'The day off was removed.'];
    }

    private function placeName(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(4)
                ->connectTimeout(3)
                ->withHeaders([
                    'User-Agent' => 'SmartELogBook/1.0 ('.config('app.url').')',
                    'Accept-Language' => 'en',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                ]);

            if ($response->successful()) {
                $name = data_get($response->json(), 'display_name');

                return $name ? Str::limit((string) $name, 250, '') : null;
            }
        } catch (\Throwable $e) {
            Log::notice('Reverse geocoding failed: '.$e->getMessage());
        }

        return null;
    }
}
