<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAttendanceController extends Controller
{
    private const MC_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    protected function student()
    {
        return Auth::guard('student')->user();
    }

    public function index()
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE);

        $monthRecords = Attendance::where('student_id', $student->student_id)
            ->whereBetween('attendance_date', [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ])
            ->get();

        $todayKey = $today->toDateString();
        $attendance = $monthRecords->first(fn ($record) => $record->attendance_date->toDateString() === $todayKey);
        $monthPresent = $monthRecords->where('status', 'Present')->count();
        $monthLeave = $monthRecords->where('status', 'Leave')->count();
        $monthMinutes = (int) $monthRecords->sum(fn ($record) => $record->workedMinutes() ?? 0);

        $records = Attendance::where('student_id', $student->student_id)
            ->orderByDesc('attendance_date')
            ->take(60)
            ->get();

        return view('student.attendance', compact(
            'student',
            'today',
            'attendance',
            'monthPresent',
            'monthLeave',
            'monthMinutes',
            'records'
        ));
    }

    public function clockIn(Request $request)
    {
        $student = $this->student();
        $todayDate = Carbon::now(Attendance::TIMEZONE)->toDateString();

        $data = $request->validate([
            'work_mode' => ['required', Rule::in(array_keys(Attendance::WORK_MODES))],
            'location_status' => 'nullable|in:shared,denied,unavailable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000000',
        ]);

        try {
            $attendance = Attendance::firstOrNew([
                'student_id' => $student->student_id,
                'attendance_date' => $todayDate,
            ]);

            if ($attendance->exists && $attendance->isDayOff()) {
                return back()->with('error', 'Today is marked as '.$attendance->dayOffLabel().'. Press Change first if you want to clock in.');
            }

            if ($attendance->check_in) {
                return back()->with('error', 'You already clocked in today at '.$attendance->checkInLocal().'.');
            }

            $shared = ($data['location_status'] ?? null) === 'shared'
                && isset($data['latitude'], $data['longitude']);

            // Only a rounded position (about 11 m) is saved and sent to the place-name service.
            $latitude = $shared ? round((float) $data['latitude'], 4) : null;
            $longitude = $shared ? round((float) $data['longitude'], 4) : null;

            $attendance->status = 'Present';
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
        } catch (\Throwable $e) {
            Log::error('Clock in failed: '.$e->getMessage());

            return back()->with('error', 'Could not clock you in. Please try again.');
        }

        return back()->with('success', 'Clocked in at '.$attendance->checkInLocal().'.');
    }

    public function clockOut()
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();

        $attendance = Attendance::where('student_id', $student->student_id)
            ->where('attendance_date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in) {
            return back()->with('error', 'Please clock in first.');
        }

        if ($attendance->check_out) {
            return back()->with('error', 'You already clocked out today at '.$attendance->checkOutLocal().'.');
        }

        try {
            $attendance->check_out = now();
            $attendance->save();
        } catch (\Throwable $e) {
            Log::error('Clock out failed: '.$e->getMessage());

            return back()->with('error', 'Could not clock you out. Please try again.');
        }

        return back()->with('success', 'Clocked out at '.$attendance->checkOutLocal().'. Worked '.$attendance->workedLabel().'.');
    }

    public function dayOff(Request $request)
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE);

        $data = $request->validate([
            'day_off_type' => ['required', Rule::in(Attendance::DAY_OFF_TYPES)],
            'leave_date' => [
                'required',
                'date',
                'after_or_equal:'.$today->copy()->subDays(30)->toDateString(),
                'before_or_equal:'.$today->copy()->addDays(60)->toDateString(),
            ],
            'note' => [
                Rule::requiredIf(fn () => $request->input('day_off_type') !== 'MC'),
                'nullable',
                'string',
                'max:255',
            ],
            'mc_file' => [
                Rule::requiredIf(fn () => $request->input('day_off_type') === 'MC'),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:'.(int) config('attendance.mc_max_kb', 2048),
            ],
        ], [
            'note.required' => 'Please write a short reason.',
            'mc_file.required' => 'Please upload your MC (photo or PDF).',
        ]);

        $isMc = $data['day_off_type'] === 'MC';
        $file = $isMc ? $request->file('mc_file') : null;
        $mime = $file ? (string) $file->getMimeType() : null;

        if ($file && ! in_array($mime, self::MC_MIMES, true)) {
            throw ValidationException::withMessages(['mc_file' => 'The MC must be a JPG, PNG, WEBP or PDF file.']);
        }

        try {
            DB::transaction(function () use ($student, $data, $file, $mime) {
                $attendance = Attendance::firstOrNew([
                    'student_id' => $student->student_id,
                    'attendance_date' => Carbon::parse($data['leave_date'])->toDateString(),
                ]);

                if ($attendance->check_in) {
                    throw ValidationException::withMessages([
                        'leave_date' => 'You already clocked in on that day, so it cannot be marked as a day off.',
                    ]);
                }

                $attendance->status = 'Leave';
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Save day off failed: '.$e->getMessage());

            return back()->withInput()->with('error', 'Could not save this day off. Please try again.');
        }

        return back()->with('success', $data['day_off_type'].' saved for '.Carbon::parse($data['leave_date'])->format('d M Y').'.');
    }

    public function cancelDayOff($id)
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();

        $attendance = Attendance::where('student_id', $student->student_id)
            ->where('attendance_id', $id)
            ->where('status', 'Leave')
            ->firstOrFail();

        if ($attendance->attendance_date->toDateString() < $today) {
            return back()->with('error', 'Days that have already passed cannot be changed.');
        }

        // The certificate row is removed together with the record (ON DELETE CASCADE).
        $attendance->delete();

        return back()->with('success', 'The day off was removed.');
    }

    /** Turn a position into a place name using OpenStreetMap. Failure just means no name. */
    protected function placeName(float $latitude, float $longitude): ?string
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