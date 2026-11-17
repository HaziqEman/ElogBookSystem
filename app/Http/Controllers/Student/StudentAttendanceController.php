<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ClockInRequest;
use App\Http\Requests\Student\StudentDayOffRequest;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\StudentAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentAttendanceController extends Controller
{
    protected function student()
    {
        return Auth::guard('student')->user();
    }

    /** JSON for the async forms, a normal redirect for everything else. */
    protected function reply(Request $request, bool $ok, string $message, int $failStatus = 422)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $ok ? 200 : $failStatus);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    public function index(StudentAttendanceService $attendanceService)
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE);
        $data = $attendanceService->indexData($student, $today);

        return view('student.attendance', array_merge(['student' => $student, 'today' => $today], $data));
    }

    public function clockIn(ClockInRequest $request, StudentAttendanceService $attendanceService)
    {
        $student = $this->student();
        $todayDate = Carbon::now(Attendance::TIMEZONE)->toDateString();
        $data = $request->validated();

        try {
            $result = $attendanceService->clockIn($student, $data, $todayDate);
        } catch (\Throwable $e) {
            Log::error('Clock in failed: '.$e->getMessage());

            return $this->reply($request, false, 'Could not clock you in. Please try again.', 500);
        }

        return $this->reply($request, $result['ok'], $result['message']);
    }

    public function clockOut(Request $request, StudentAttendanceService $attendanceService)
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();

        try {
            $result = $attendanceService->clockOut($student, $today);
        } catch (\Throwable $e) {
            Log::error('Clock out failed: '.$e->getMessage());

            return $this->reply($request, false, 'Could not clock you out. Please try again.', 500);
        }

        return $this->reply($request, $result['ok'], $result['message']);
    }

    public function dayOff(StudentDayOffRequest $request, StudentAttendanceService $attendanceService)
    {
        $student = $this->student();
        $data = $request->validated();

        $isMc = $data['day_off_type'] === 'MC';
        $file = $isMc ? $request->file('mc_file') : null;
        $mime = $file ? (string) $file->getMimeType() : null;

        try {
            $attendanceService->saveDayOff($student, $data, $file, $mime);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Save day off failed: '.$e->getMessage());

            return $this->reply($request, false, 'Could not save this day off. Please try again.', 500);
        }

        return $this->reply(
            $request,
            true,
            $data['day_off_type'].' saved for '.Carbon::parse($data['leave_date'])->format('d M Y').'.'
        );
    }

    public function cancelDayOff(Request $request, string $id, StudentAttendanceService $attendanceService)
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();

        $result = $attendanceService->cancelDayOff($student, $id, $today);

        return $this->reply($request, $result['ok'], $result['message']);
    }
}
