<?php

namespace App\Http\Controllers;

use App\Exports\StudentsTemplateExport;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminStudentImportController extends Controller
{
    public function show()
    {
        return view('admin.students.import');
    }

    public function template()
    {
        return Excel::download(new StudentsTemplateExport(), 'students-import-template.xlsx');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.required' => 'Choose an Excel or CSV file first.',
            'file.mimes' => 'The file must be .xlsx, .xls or .csv.',
            'file.max' => 'The file is too large (max 5 MB).',
        ]);

        @set_time_limit(300);

        $import = new StudentsImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not read that file. Please use the template and keep the heading row.');
        }

        $created = $import->created;
        $failed = count($import->failures);

        if ($created === 0 && $failed === 0) {
            return back()->with('error', 'No rows were found. Make sure the first row has the column headings from the template.');
        }

        $flash = [
            'import_failures' => $import->failures,
            'import_passwords' => $import->generated,
        ];

        if ($created > 0) {
            $flash['success'] = 'Imported '.$created.' '.str('student')->plural($created).'.'
                .($failed ? ' '.$failed.' '.str('row')->plural($failed).' skipped, see below.' : '');
        } else {
            $flash['error'] = 'No students were imported. All '.$failed.' '.str('row')->plural($failed).' had problems, see below.';
        }

        return redirect('/admin/students/import')->with($flash);
    }
}
