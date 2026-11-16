<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentScheduleController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['required', 'integer', 'between:1,7', 'distinct:strict'],
        ]);

        $student = Auth::guard('student')->user();
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();
        $days = array_values(array_unique(array_map('intval', $data['working_days'])));
        sort($days, SORT_NUMERIC);
        $daysCsv = implode(',', $days);

        $history = WeekPlanner::parseSchedule($student->working_schedule, $student->working_days);
        $history = array_values(array_filter(
            $history,
            fn (array $entry) => $entry['from'] < $today
        ));
        $history[] = ['from' => $today, 'days' => $daysCsv];

        $student->working_schedule = json_encode($history, JSON_THROW_ON_ERROR);
        $student->working_days = $daysCsv;
        $student->save();

        $message = 'Working days saved. Changes apply from today.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 200);
        }

        return back()->with('success', $message);
    }
}
