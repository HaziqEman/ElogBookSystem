<?php

namespace App\Http\Controllers;

use App\Exports\SupervisorRubricTemplateExport;
use App\Imports\SupervisorRubricImport;
use App\Services\SupervisorRubricService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();
        $hasEvaluationsTable = $schema->hasTable('supervisor_evaluations');
        $missingSetup = [];

        if (! $hasEvaluationsTable) {
            $missingSetup[] = 'supervisor_evaluations table';
        } elseif (! $schema->hasColumn('supervisor_evaluations', 'rubric_snapshot')) {
            $missingSetup[] = 'supervisor_evaluations.rubric_snapshot column';
        }

        if (! $schema->hasTable('supervisor_rubrics')) {
            $missingSetup[] = 'supervisor_rubrics table';
        }

        if ($missingSetup) {
            $schemaName = $connection->getDriverName() === 'pgsql'
                ? $connection->selectOne('select current_schema() as schema_name')->schema_name
                : 'not applicable';

            return back()->withErrors([
                'file' => 'Rubric setup is missing from the database used by this app: '
                    .implode(', ', $missingSetup)
                    .'. App connection: '.$connection->getDriverName()
                    .' / '.$connection->getDatabaseName()
                    .' / schema '.$schemaName
                    .'. Run database/neon_supervisor_rubric.sql against this exact database and schema, then retry.',
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
