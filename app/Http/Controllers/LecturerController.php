<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\LecturerInsights;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class LecturerController extends Controller
{
    public function dashboard(Request $request)
    {
        $lecturer = Auth::guard('lecturer')->user();
        $today = Carbon::now(Attendance::TIMEZONE);

        $insights = (new LecturerInsights($lecturer, $today))->build();

        $all = $insights['students']->values();
        $needsAttention = $insights['attention']->take(6);

        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $rows = $all;

        if ($q !== '') {
            $needle = mb_strtolower($q);

            $rows = $rows->filter(function ($row) use ($needle) {
                return str_contains(mb_strtolower((string) $row['student']->name), $needle)
                    || str_contains(mb_strtolower((string) $row['student']->matric_no), $needle);
            });
        }

        $rows = match ($filter) {
            'attention' => $rows->filter(fn ($row) => in_array($row['priority'], ['High', 'Medium'], true)),
            'review' => $rows->filter(fn ($row) => $row['review_label'] === 'Pending review'),
            'revision' => $rows->filter(fn ($row) => $row['review_label'] === 'Revision requested'),
            'tasks' => $rows->filter(fn ($row) => $row['tasks_overdue'] > 0),
            'nodates' => $rows->filter(fn ($row) => ! $row['has_dates']),
            default => $rows,
        };

        $order = ['High' => 3, 'Medium' => 2, 'Low' => 1];

        $rows = $rows->sort(function ($a, $b) use ($order) {
            return [$order[$b['priority'] ?? ''] ?? 0, mb_strtolower((string) $a['student']->name)]
                <=> [$order[$a['priority'] ?? ''] ?? 0, mb_strtolower((string) $b['student']->name)];
        })->values();

        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $students = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'fragment' => 'students']
        );

        $greeting = $today->hour < 12 ? 'Good morning' : ($today->hour < 17 ? 'Good afternoon' : 'Good evening');

        return view('lecturer.dashboard', [
            'lecturer' => $lecturer,
            'today' => $today,
            'greeting' => $greeting,
            'kpis' => $insights['kpis'],
            'needsAttention' => $needsAttention,
            'weekChart' => $insights['week_chart'],
            'trend' => $insights['trend'],
            'students' => $students,
            'totalStudents' => $all->count(),
            'q' => $q,
            'filter' => $filter,
        ]);
    }
}