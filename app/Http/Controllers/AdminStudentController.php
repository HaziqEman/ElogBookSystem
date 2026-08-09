<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminStudentController extends Controller
{
    // public function index()
    // {
    //     $students = Student::with('lecturer')
    //                 ->paginate(10);

    //     return view(
    //         'admin.students.index',
    //         compact('students')
    //     );
    // }

    

public function index()
{
    $students = Student::with('lecturer')
                ->paginate(10);

    return view(
        'admin.students.index',
        compact('students')
    );
}

    public function create()
    {
        $lecturers = Lecturer::all();

        return view(
            'admin.students.create',
            compact('lecturers')
        );
    }

    public function store(Request $request)
    {
        Student::create([

            'matric_no'=>$request->matric_no,

            'name'=>$request->name,

            'email'=>$request->email,

            'password'=>Hash::make(
                $request->password
            ),

            'phone_no'=>$request->phone_no,

            'course'=>$request->course,

            'lecturer_id'=>$request->lecturer_id

        ]);

        return redirect(
            '/admin/students'
        );
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);

        $lecturers = Lecturer::all();

        return view(
            'admin.students.edit',
            compact(
                'student',
                'lecturers'
            )
        );
    }

    public function update(
        Request $request,
        $id
    )
    {
        $student = Student::findOrFail($id);

        $student->update([

            'name'=>$request->name,

            'phone_no'=>$request->phone_no,

            'course'=>$request->course,

            'lecturer_id'=>$request->lecturer_id

        ]);

        return redirect(
            '/admin/students'
        );
    }

    public function destroy($id)
    {
        Student::findOrFail($id)
                ->delete();

        return back();
    }
}