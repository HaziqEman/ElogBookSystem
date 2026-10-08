<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Auth::guard('student')->user();
        $today = Carbon::now(Attendance::TIMEZONE);

        $logbooks = Logbook::with('attachments')
            ->where('student_id', $student->student_id)
            ->latest()
            ->orderByDesc('logbook_id')
            ->take(10)
            ->get();

        $totalLogs = Logbook::where('student_id', $student->student_id)->count();

        $totalAttachments = Attachment::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })->count();

        $totalReviews = Feedback::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })->count();

        // The attendance and to-do tables come from SQL. Until they exist the dashboard still loads.
        $extrasReady = Schema::hasTable('attendances') && Schema::hasTable('todos');

        $attendance = null;
        $monthPresent = 0;
        $monthLeave = 0;
        $openTodos = collect();
        $doneTodos = collect();
        $openCount = 0;
        $doneCount = 0;

        if ($extrasReady) {
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

            $openCount = Todo::where('student_id', $student->student_id)->where('is_done', false)->count();
            $doneCount = Todo::where('student_id', $student->student_id)->where('is_done', true)->count();

            $openTodos = Todo::where('student_id', $student->student_id)
                ->where('is_done', false)
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderBy('todo_id')
                ->take(8)
                ->get();

            $doneTodos = Todo::where('student_id', $student->student_id)
                ->where('is_done', true)
                ->orderByDesc('updated_at')
                ->take(5)
                ->get();
        }

        return view('student.dashboard', compact(
            'student',
            'today',
            'logbooks',
            'totalLogs',
            'totalAttachments',
            'totalReviews',
            'extrasReady',
            'attendance',
            'monthPresent',
            'monthLeave',
            'openTodos',
            'doneTodos',
            'openCount',
            'doneCount'
        ));
    }
}