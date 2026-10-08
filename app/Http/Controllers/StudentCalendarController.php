<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCalendarController extends Controller
{
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();
        $tz = Attendance::TIMEZONE;
        $today = Carbon::now($tz)->startOfDay();

        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->query('month', $today->format('Y-m')), $tz)->startOfMonth();

            if ($month->year < 2000 || $month->year > 2100) {
                throw new \InvalidArgumentException('Month out of range.');
            }
        } catch (\Throwable $e) {
            $month = $today->copy()->startOfMonth();
        }

        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $from = $gridStart->toDateString();
        $to = $gridEnd->toDateString();

        $logsByDate = Logbook::where('student_id', $student->student_id)
            ->whereBetween('activity_date', [$from, $to])
            ->get()
            ->groupBy(fn ($log) => Carbon::parse($log->activity_date)->toDateString());

        $attendanceByDate = Attendance::where('student_id', $student->student_id)
            ->whereBetween('attendance_date', [$from, $to])
            ->get()
            ->keyBy(fn ($record) => $record->attendance_date->toDateString());

        $todosByDate = Todo::where('student_id', $student->student_id)
            ->whereBetween('due_date', [$from, $to])
            ->get()
            ->groupBy(fn ($todo) => $todo->due_date->toDateString());

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $dayTodos = $todosByDate->get($key, collect());

                $week[] = [
                    'date' => $cursor->copy(),
                    'in_month' => $cursor->month === $month->month,
                    'is_today' => $key === $today->toDateString(),
                    'is_weekend' => $cursor->isWeekend(),
                    'logs' => $logsByDate->get($key, collect())->count(),
                    'attendance' => $attendanceByDate->get($key),
                    'tasks_open' => $dayTodos->where('is_done', false)->count(),
                    'tasks_done' => $dayTodos->where('is_done', true)->count(),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        $monthKey = $month->format('Y-m');
        $monthLogs = $logsByDate->filter(fn ($group, $key) => str_starts_with($key, $monthKey))->sum(fn ($group) => $group->count());
        $monthPresent = $attendanceByDate->filter(fn ($record, $key) => str_starts_with($key, $monthKey) && $record->status === 'Present')->count();
        $monthTasks = $todosByDate->filter(fn ($group, $key) => str_starts_with($key, $monthKey))
            ->sum(fn ($group) => $group->where('is_done', false)->count());

        $prevMonth = $month->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $month->copy()->addMonthNoOverflow()->format('Y-m');
        $isCurrentMonth = $month->format('Y-m') === $today->format('Y-m');

        return view('student.calendar', compact(
            'student',
            'month',
            'weeks',
            'prevMonth',
            'nextMonth',
            'isCurrentMonth',
            'monthLogs',
            'monthPresent',
            'monthTasks'
        ));
    }
}