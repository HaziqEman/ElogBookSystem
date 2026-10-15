<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Message;
use App\Models\Student;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();
        $today = Carbon::now(Attendance::TIMEZONE);
        $todayKey = $today->toDateString();

        $logbooks = Logbook::with('attachments')
            ->where('student_id', $student->student_id)
            ->latest()
            ->orderByDesc('logbook_id')
            ->take($request->boolean('all') ? 100 : 5)
            ->get();

        $totalLogs = Logbook::where('student_id', $student->student_id)->count();

        $totalAttachments = Attachment::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })->count();

        $totalReviews = Feedback::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })->count();

        $unreadMessages = Message::whereNull('read_at')
            ->where('sender_type', '!=', Student::class)
            ->where('deleted_for_student', false)
            ->whereHas('conversation', function ($query) use ($student) {
                $query->where('student_id', $student->student_id);
            })
            ->count();

        // The attendance and to-do tables come from SQL. Until then the dashboard still loads.
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

        // Small calendar for the current month (Monday first).
        $monthStart = $today->copy()->startOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $logDays = Logbook::where('student_id', $student->student_id)
            ->whereBetween('activity_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->flip();

        $dueDays = collect();

        if ($extrasReady) {
            $dueDays = Todo::where('student_id', $student->student_id)
                ->where('is_done', false)
                ->whereBetween('due_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
                ->get()
                ->map(fn ($todo) => $todo->due_date->toDateString())
                ->unique()
                ->flip();
        }

        $cells = [];

        for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
            $key = $cursor->toDateString();

            $cells[] = [
                'label' => $cursor->day,
                'in_month' => $cursor->month === $monthStart->month,
                'is_today' => $key === $todayKey,
                'has_log' => $logDays->has($key),
                'has_due' => $dueDays->has($key),
            ];
        }

        $calendar = [
            'title' => $monthStart->format('F Y'),
            'prev' => $monthStart->copy()->subMonthNoOverflow()->format('Y-m'),
            'next' => $monthStart->copy()->addMonthNoOverflow()->format('Y-m'),
            'cells' => $cells,
        ];

        // Daily AI draft allowance.
        $aiLimit = max(1, (int) config('aihelp.daily_limit', 10));
        $aiReady = Schema::hasTable('ai_help_usages');
        $aiUsed = $aiReady
            ? (int) DB::table('ai_help_usages')
                ->where('student_id', $student->student_id)
                ->where('used_on', $todayKey)
                ->value('used_count')
            : 0;
        $aiLeft = max($aiLimit - $aiUsed, 0);

        return view('student.dashboard', compact(
            'student',
            'today',
            'logbooks',
            'totalLogs',
            'totalAttachments',
            'totalReviews',
            'unreadMessages',
            'extrasReady',
            'attendance',
            'monthPresent',
            'monthLeave',
            'openTodos',
            'doneTodos',
            'openCount',
            'doneCount',
            'calendar',
            'aiReady',
            'aiLimit',
            'aiLeft'
        ));
    }
}