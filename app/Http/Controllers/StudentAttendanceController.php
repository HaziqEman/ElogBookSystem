<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentAttendanceController extends Controller
{
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

    public function clockIn()
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();

        try {
            $attendance = Attendance::firstOrNew([
                'student_id' => $student->student_id,
                'attendance_date' => $today,
            ]);

            if ($attendance->check_in) {
                return back()->with('error', 'You already clocked in today at '.$attendance->checkInLocal().'.');
            }

            // Clocking in also turns a day marked as leave back into a work day.
            $attendance->status = 'Present';
            $attendance->note = null;
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

    public function leave(Request $request)
    {
        $student = $this->student();
        $today = Carbon::now(Attendance::TIMEZONE);

        $data = $request->validate([
            'leave_date' => [
                'required',
                'date',
                'after_or_equal:'.$today->copy()->subDays(30)->toDateString(),
                'before_or_equal:'.$today->copy()->addDays(60)->toDateString(),
            ],
            'note' => 'required|string|max:255',
        ]);

        try {
            $attendance = Attendance::firstOrNew([
                'student_id' => $student->student_id,
                'attendance_date' => Carbon::parse($data['leave_date'])->toDateString(),
            ]);

            if ($attendance->check_in) {
                return back()->with('error', 'You already clocked in on that day, so it cannot be marked as leave.');
            }

            $attendance->status = 'Leave';
            $attendance->note = trim($data['note']);
            $attendance->check_in = null;
            $attendance->check_out = null;
            $attendance->save();
        } catch (\Throwable $e) {
            Log::error('Mark leave failed: '.$e->getMessage());

            return back()->with('error', 'Could not save the leave. Please try again.');
        }

        return back()->with('success', 'Leave saved for '.Carbon::parse($data['leave_date'])->format('d M Y').'.');
    }
}