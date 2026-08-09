<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        $hasStudentsTable = Schema::hasTable('students');
        $hasLecturersTable = Schema::hasTable('lecturers');
        $hasLogbooksTable = Schema::hasTable('logbooks');
        $hasAttachmentsTable = Schema::hasTable('attachments');

        $students = $hasStudentsTable ? Student::count() : 0;
        $lecturers = $hasLecturersTable ? Lecturer::count() : 0;
        $logbooks = $hasLogbooksTable ? Logbook::count() : 0;
        $pending = $hasLogbooksTable ? Logbook::where('status', 'Pending')->count() : 0;
        $approved = $hasLogbooksTable ? Logbook::where('status', 'Approved')->count() : 0;
        $rejected = $hasLogbooksTable ? Logbook::where('status', 'Rejected')->count() : 0;
        $revision = $hasLogbooksTable ? Logbook::where('status', 'Revision Required')->count() : 0;
        $submittedToday = $hasLogbooksTable ? Logbook::whereDate('created_at', today())->count() : 0;
        $submittedThisWeek = $hasLogbooksTable ? Logbook::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count() : 0;
        $attachments = $hasAttachmentsTable ? Attachment::count() : 0;
        $activeStudents = $hasStudentsTable ? Student::whereHas('logbooks', function ($query) {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        })->count() : 0;

        $recentLogbooks = $hasLogbooksTable ? Logbook::with(['student', 'student.lecturer'])
            ->latest('created_at')
            ->take(10)
            ->get() : collect();

        $recentActivities = collect();
        if ($hasLogbooksTable) {
            $recentActivities = Logbook::with('student')
                ->latest('created_at')
                ->take(10)
                ->get()
                ->map(function ($logbook) {
                    $status = $logbook->status ?? 'Pending';
                    $message = match ($status) {
                        'Approved' => 'Lecturer approved a logbook',
                        'Revision Required' => 'Lecturer requested revision',
                        default => 'Student submitted a logbook',
                    };

                    return [
                        'title' => $message,
                        'detail' => $logbook->student->name ?? 'Unknown student',
                        'time' => $logbook->created_at?->diffForHumans() ?? 'Recently',
                    ];
                });
        }

        $studentWithMostSubmissions = $hasLogbooksTable ? Student::withCount('logbooks')->orderByDesc('logbooks_count')->first() : null;
        $mostActiveStudent = $studentWithMostSubmissions?->name ?? 'No data';
        $mostActiveStudentCount = $studentWithMostSubmissions?->logbooks_count ?? 0;

        $studentsWithoutSubmissionThisWeek = $hasStudentsTable ? Student::whereDoesntHave('logbooks', function ($query) {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        })->count() : 0;

        $lecturerWithMostReviews = $hasLogbooksTable ? Lecturer::withCount(['students' => function ($query) {
            $query->whereHas('logbooks', function ($subQuery) {
                $subQuery->whereIn('status', ['Approved', 'Rejected', 'Revision Required']);
            });
        }])->orderByDesc('students_count')->first() : null;

        $reviewedLogbooks = $hasLogbooksTable ? Logbook::whereIn('status', ['Approved', 'Rejected', 'Revision Required'])->count() : 0;
        $pendingReviews = $hasLogbooksTable ? Logbook::where('status', 'Pending')->count() : 0;
        $supervisedStudents = $hasStudentsTable ? Student::count() : 0;
        $topLecturerName = $lecturerWithMostReviews?->name ?? 'No data';

        $monthlyTrend = $hasLogbooksTable ? Logbook::query()
            ->whereBetween('created_at', [now()->subMonths(6), now()])
            ->get()
            ->groupBy(function ($logbook) {
                return $logbook->created_at->format('m');
            })
            ->map(function ($group, $month) {
                return [
                    'month' => (int) $month,
                    'count' => $group->count(),
                ];
            })
            ->values() : collect();

        $statusBreakdown = $hasLogbooksTable ? Logbook::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get() : collect();

        $userDistribution = [
            'students' => $students,
            'lecturers' => $lecturers,
        ];

        $averageLogbooksPerStudent = $students > 0 ? round($logbooks / $students, 1) : 0;
        $storageUsage = round((is_dir(storage_path('app')) ? folderSize(storage_path('app')) : 0) / 1024 / 1024, 2);
        $databaseStatus = Schema::hasTable('users') ? 'Connected' : 'Unavailable';
        $lastBackupTime = file_exists(storage_path('logs')) ? now()->toDateTimeString() : 'Not available';
        $appVersion = 'v1.0.0';

        return view(
            'admin.dashboard',
            compact(
                'students',
                'lecturers',
                'logbooks',
                'pending',
                'approved',
                'rejected',
                'revision',
                'submittedToday',
                'submittedThisWeek',
                'averageLogbooksPerStudent',
                'recentLogbooks',
                'recentActivities',
                'activeStudents',
                'studentsWithoutSubmissionThisWeek',
                'mostActiveStudent',
                'mostActiveStudentCount',
                'pendingReviews',
                'reviewedLogbooks',
                'supervisedStudents',
                'topLecturerName',
                'monthlyTrend',
                'statusBreakdown',
                'userDistribution',
                'attachments',
                'storageUsage',
                'databaseStatus',
                'lastBackupTime',
                'appVersion'
            )
        );
    }
}

if (! function_exists('folderSize')) {
    function folderSize($dir)
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
