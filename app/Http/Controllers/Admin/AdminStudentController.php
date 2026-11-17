<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BulkStudentAction;
use App\Actions\Admin\ExportStudentCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkStudentActionRequest;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Student;
use App\Services\AdminStudentDirectoryService;
use App\Services\AdminStudentService;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    public function index(Request $request, AdminStudentDirectoryService $directory)
    {
        return view('admin.students.index', $directory->indexData($request));
    }

    public function bulk(BulkStudentActionRequest $request, BulkStudentAction $action)
    {
        $result = $action->handle($request);
        $redirect = $result['redirect'] === 'students' ? redirect('/admin/students') : back();

        return $redirect->with($result['flash']);
    }

    // ------------------------------------------------------------------
    // Export + password reset
    // ------------------------------------------------------------------

    public function export(Request $request, ExportStudentCsv $export)
    {
        return $export->handle($request);
    }

    public function resetPassword($id, AdminStudentService $students)
    {
        $student = Student::findOrFail($id);
        $temporary = $students->resetPassword($student);

        return back()->with([
            'success' => 'Password reset for '.$student->name.'.',
            'temp_password' => $temporary,
            'temp_for' => $student->name,
        ]);
    }

    // ------------------------------------------------------------------
    // Create / edit / delete (same behaviour as before)
    // ------------------------------------------------------------------

    public function create(AdminStudentService $students)
    {
        return view('admin.students.create', $students->createFormData());
    }

    public function store(StoreStudentRequest $request, AdminStudentService $students)
    {
        $data = $request->validated();

        if ($students->missingInternshipColumns($data)) {
            return back()->withInput()->with('error', 'The internship date columns do not exist yet. Run the database update first.');
        }

        $student = $students->store($data);

        return redirect('/admin/students')->with('success', 'Student created.'.$students->datesMessage($student->getAttributes()));
    }

    public function edit($id, AdminStudentService $students)
    {
        $student = Student::findOrFail($id);

        return view('admin.students.edit', $students->editFormData($student));
    }

    public function update(Request $request, $id, AdminStudentService $students)
    {
        $student = Student::findOrFail($id);
        $data = app(UpdateStudentRequest::class)->validated();

        if ($students->missingInternshipColumns($data)) {
            return back()->withInput()->with('error', 'The internship date columns do not exist yet. Run the database update first.');
        }

        $payload = $students->update($student, $data);

        return redirect('/admin/students')->with('success', 'Student updated.'.$students->datesMessage($payload));
    }

    public function destroy($id, AdminStudentService $students)
    {
        $students->delete(Student::findOrFail($id));

        return back()->with('success', 'Student deleted.');
    }
}
