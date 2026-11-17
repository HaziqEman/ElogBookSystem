<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentScheduleRequest;
use App\Models\Attendance;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentScheduleController extends Controller
{
    public function update(UpdateStudentScheduleRequest $request)
    {
        $data = $request->validated();

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
