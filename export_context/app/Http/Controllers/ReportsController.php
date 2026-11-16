<?php

namespace App\Http\Controllers;

use App\Exports\AcademicReportExport;
use App\Models\Logbook;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function exportExcel(Request $request)
    {
        try {
            return Excel::download(
                new AcademicReportExport,
                'academic-report-' . now()->format('Ymd-His') . '.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Excel export failed: ' . $e->getMessage());
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

            return $pdf->download('master-evaluation-audit-' . now()->format('Ymd-His') . '.pdf');
        } catch (\Throwable $e) {
            return redirect('/admin/reports')->with('error', 'PDF export failed: ' . $e->getMessage());
        }
    }
}
