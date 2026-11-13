<?php

namespace App\Http\Controllers;

use App\Exports\AcademicReportExport;
use App\Exports\InternshipDashboardExport;
use App\Exports\InternshipSheets;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\University;
use App\Services\InternshipReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index()
    {
        return view('admin.reports.index', [
            'lecturers' => Lecturer::orderBy('name')->get(['lecturer_id', 'name']),
            'supervisors' => Supervisor::orderBy('name')->get(['supervisor_id', 'name', 'company_name']),
            'companies' => Supervisor::whereNotNull('company_name')
                ->where('company_name', '!=', '')
                ->distinct()
                ->orderBy('company_name')
                ->pluck('company_name'),
            'courses' => Student::whereNotNull('course')
                ->where('course', '!=', '')
                ->distinct()
                ->orderBy('course')
                ->pluck('course'),
            'universities' => University::orderBy('name')->get(['university_id', 'name']),
        ]);
    }

    /** Filters from the query string; empty values are dropped. */
    protected function filters(Request $request): array
    {
        return array_filter(
            $request->only(['lecturer_id', 'supervisor_id', 'company', 'course', 'university_id']),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    /** One Excel workbook: Summary (with charts) plus the detail sheets. */
    public function exportDashboard(Request $request)
    {
        try {
            $report = (new InternshipReportService())->build($this->filters($request));

            return Excel::download(
                new InternshipDashboardExport($report),
                'internship-dashboard-'.now()->format('Ymd-His').'.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('Dashboard export failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect('/admin/reports')->with('error', 'Dashboard export failed: '.$e->getMessage());
        }
    }

    /** One table as plain CSV (progress, weekly, attention, lecturers, supervisors, companies). */
    public function exportCsv(Request $request, string $type)
    {
        if (! in_array($type, InternshipSheets::TYPES, true)) {
            abort(404);
        }

        try {
            $report = (new InternshipReportService())->build($this->filters($request));

            return Excel::download(
                InternshipSheets::make($type, $report, true),
                'internship-'.$type.'-'.now()->format('Ymd-His').'.csv',
                ExcelType::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        } catch (\Throwable $e) {
            Log::error('CSV export failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect('/admin/reports')->with('error', 'CSV export failed: '.$e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            return Excel::download(
                new AcademicReportExport,
                'academic-report-'.now()->format('Ymd-His').'.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Excel export failed: '.$e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $students = Student::with(['lecturer', 'logbooks'])->get();
            $logbooks = Logbook::with('student')->get();

            $pdf = Pdf::loadView('admin.reports.pdf', [
                'students' => $students,
                'logbooks' => $logbooks,
            ])->setPaper('a4', 'portrait');

            return $pdf->download('master-evaluation-audit-'.now()->format('Ymd-His').'.pdf');
        } catch (\Throwable $e) {
            return redirect('/admin/reports')->with('error', 'PDF export failed: '.$e->getMessage());
        }
    }
}
