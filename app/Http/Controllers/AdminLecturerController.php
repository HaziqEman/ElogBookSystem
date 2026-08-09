<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminLecturerController extends Controller
{
    public function index()
    {
        $lecturers = Lecturer::paginate(10);

        return view('admin.lecturers.index', compact('lecturers'));
    }

    public function create()
    {
        return view('admin.lecturers.create');
    }

    public function store(Request $request)
    {
        Lecturer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'faculty' => $request->faculty,
        ]);

        return redirect('/admin/lecturers');
    }

    public function edit($id)
    {
        $lecturer = Lecturer::findOrFail($id);

        return view('admin.lecturers.edit', compact('lecturer'));
    }

    public function update(Request $request, $id)
    {
        $lecturer = Lecturer::findOrFail($id);

        $lecturer->update([
            'name' => $request->name,
            'email' => $request->email,
            'faculty' => $request->faculty,
        ]);

        if ($request->filled('password')) {
            $lecturer->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect('/admin/lecturers');
    }

    public function destroy($id)
    {
        Lecturer::findOrFail($id)->delete();

        return back();
    }
}
