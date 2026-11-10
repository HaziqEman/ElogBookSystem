<?php

namespace App\Http\Controllers;

use App\Exports\SupervisorRubricTemplateExport;
use App\Imports\SupervisorRubricImport;
use App\Services\SupervisorRubricService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AdminSupervisorRubricController extends Controller
{
    public function index(SupervisorRubricService $rubrics)
    {
        return view('admin.rubric.index', [
            'rubric' => $rubrics->current(),
        ]);
    }

    public function template(SupervisorRubricService $rubrics)
    {
        return Excel::download(
            new SupervisorRubricTemplateExport($rubrics->current()),
            'supervisor-rubric-template.xlsx'
        );
    }

    public function import(Request $request, SupervisorRubricService $rubrics)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Choose an Excel or CSV file first.',
            'file.mimes' => 'Upload an .xlsx, .xls, or .csv file.',
            'file.max' => 'The file must be 5 MB or smaller.',
        ]);

        if (
            ! Schema::hasTable('supervisor_evaluations')
            || ! Schema::hasColumn('supervisor_evaluations', 'rubric_snapshot')
            || ! Schema::hasTable('supervisor_rubrics')
        ) {
            return back()->withErrors([
                'file' => 'Rubric database migrations are missing. Run php artisan migrate on this application, then try importing again.',
            ])->withInput();
        }

        $import = new SupervisorRubricImport();

        try {
            Excel::import($import, $request->file('file'));

            if (! $import->rubric()) {
                return back()->withErrors(['file' => 'No rubric was found in the spreadsheet.']);
            }

            $rubrics->activate($import->rubric());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Supervisor rubric import failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->withErrors([
                'file' => 'Could not import that rubric. Check the template headings and spreadsheet format.',
            ])->withInput();
        }

        return redirect('/admin/supervisor-rubric')
            ->with('success', 'Rubric imported. New evaluations will use it; submitted evaluations keep their original rubric and marks.');
    }
}
