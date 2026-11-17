<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\LecturersTemplateExport;
use App\Http\Requests\Admin\ImportLecturerFileRequest;
use App\Imports\LecturersImport;
use Maatwebsite\Excel\Facades\Excel;

class AdminLecturerImportController extends Controller
{
    public function show()
    {
        return view('admin.lecturers.import');
    }

    public function template()
    {
        return Excel::download(new LecturersTemplateExport(), 'lecturers-import-template.xlsx');
    }

    public function store(ImportLecturerFileRequest $request)
    {
        $request->validated();

        @set_time_limit(300);

        $import = new LecturersImport();

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
            $flash['success'] = 'Imported '.$created.' '.str('lecturer')->plural($created).'.'
                .($failed ? ' '.$failed.' '.str('row')->plural($failed).' skipped, see below.' : '');
        } else {
            $flash['error'] = 'No lecturers were imported. All '.$failed.' '.str('row')->plural($failed).' had problems, see below.';
        }

        return redirect('/admin/lecturers/import')->with($flash);
    }
}
