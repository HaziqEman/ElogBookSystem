<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LecturerController extends Controller
{
    public function dashboard()
    {
        $hasStudentsTable = Schema::hasTable('students');
        $hasLogbooksTable = Schema::hasTable('logbooks');

        $lecturer = Auth::guard('lecturer')->user();

        $assignedStudents = collect();
        $studentSummaries = collect();
        $pending = 0;
        $reviewed = 0;

        if ($hasStudentsTable && $hasLogbooksTable && $lecturer) {
            $assignedStudents = Student::where('lecturer_id', $lecturer->lecturer_id)
                ->orderBy('name')
                ->get();

            $assignedStudents = $assignedStudents->values();

            $logbooks = Logbook::whereHas('student', function ($query) use ($lecturer) {
                $query->where('lecturer_id', $lecturer->lecturer_id);
            })
                ->with('student')
                ->orderByDesc('created_at')
                ->orderByDesc('logbook_id')
                ->get();

            $pending = $logbooks->where('status', 'Pending')->count();
            $reviewed = $logbooks->whereIn('status', ['Approved', 'Rejected'])->count();

            $studentSummaries = $logbooks->map(function ($logbook) {
                return [
                    'student' => $logbook->student,
                    'latest_logbook' => $logbook,
                    'submission_date' => $logbook->activity_date ?? $logbook->created_at?->toDateString(),
                ];
            });
        }

        return view('lecturer.dashboard', [
            'lecturer' => $lecturer,
            'students' => $assignedStudents->count(),
            'pending' => $pending,
            'reviewed' => $reviewed,
            'studentSummaries' => $studentSummaries,
        ]);
    }
}