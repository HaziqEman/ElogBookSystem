<?php

namespace App\Actions\Admin;

use App\Services\AdminStudentDirectoryService;
use App\Services\WeekPlanner;
use Illuminate\Http\Request;

class ExportStudentCsv
{
    public function __construct(private AdminStudentDirectoryService $directory)
    {
    }

    public function handle(Request $request)
    {
        $query = $this->directory->filteredQuery($request)
            ->with(['lecturer', 'supervisor'])
            ->withCount('logbooks')
            ->orderBy('name');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Matric', 'Name', 'Email', 'Phone', 'Course', 'Lecturer', 'Company supervisor', 'Company', 'Internship start', 'Weeks', 'Working days', 'Entries']);

            $query->chunkById(200, function ($students) use ($out) {
                foreach ($students as $student) {
                    fputcsv($out, [
                        $student->matric_no,
                        $student->name,
                        $student->email,
                        $student->phone_no,
                        $student->course,
                        optional($student->lecturer)->name,
                        optional($student->supervisor)->name,
                        optional($student->supervisor)->company_name,
                        $student->internship_start ? $student->internship_start->format('Y-m-d') : '',
                        $student->internship_start ? ($student->internship_weeks ?: 14) : '',
                        $student->internship_start ? implode(' ', array_map(fn ($day) => WeekPlanner::DAY_NAMES[$day], WeekPlanner::parseDays($student->working_days))) : '',
                        $student->logbooks_count,
                    ]);
                }
            }, 'student_id');

            fclose($out);
        }, 'students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
