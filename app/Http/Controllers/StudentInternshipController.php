<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StudentInternshipController extends Controller
{
    public function update(Request $request)
    {
        $student = Auth::guard('student')->user();

        if (! Schema::hasColumn('students', 'internship_start')) {
            return back()->with('error', 'The internship settings are not set up yet. Ask the administrator to run the database update.');
        }

        $data = $request->validate([
            'internship_start' => 'required|date|after_or_equal:2020-01-01|before_or_equal:2100-12-31',
            'internship_weeks' => 'required|integer|between:1,52',
            'working_days' => 'required|array|min:1',
            'working_days.*' => 'integer|between:1,7',
        ], [
            'working_days.required' => 'Choose at least one working day.',
        ]);

        $days = collect($data['working_days'])->map(fn ($day) => (int) $day)->unique()->sort()->values()->all();

        $student->update([
            'internship_start' => $data['internship_start'],
            'internship_weeks' => (int) $data['internship_weeks'],
            'working_days' => implode(',', $days),
        ]);

        return redirect('/student/dashboard#logbook-week')->with('success', 'Internship settings saved.');
    }
}