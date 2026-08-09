<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = trim($data['email']);
        $password = $data['password'];

        $student = Student::where('email', $email)->first();
        if ($student && Hash::check($password, $student->password)) {
            Auth::guard('student')->login($student);
            return redirect('/student/dashboard');
        }

        $lecturer = Lecturer::where('email', $email)->first();
        if ($lecturer && Hash::check($password, $lecturer->password)) {
            Auth::guard('lecturer')->login($lecturer);
            return redirect('/lecturer/dashboard');
        }

        $admin = Admin::where('email', $email)->first();
        if ($admin && Hash::check($password, $admin->password)) {
            Auth::guard('admin')->login($admin);
            return redirect('/admin/dashboard');
        }

        return back()->withInput()->with('error', 'Invalid email or password.');
    }

    public function logout()
    {
        Auth::guard('student')->logout();
        Auth::guard('lecturer')->logout();
        Auth::guard('admin')->logout();

        return redirect('/');
    }

    public function switchModule($role)
    {
        $allowedRoles = ['student', 'lecturer', 'admin'];

        if (! in_array($role, $allowedRoles, true)) {
            return redirect('/');
        }

        if (Auth::guard($role)->check()) {
            return match ($role) {
                'student' => redirect('/student/dashboard'),
                'lecturer' => redirect('/lecturer/dashboard'),
                'admin' => redirect('/admin/dashboard'),
            };
        }

        return redirect('/');
    }

    public function showRegister()
    {
        return view('register', [
            'lecturers' => Lecturer::all(),
        ]);
    }

    public function register(Request $request)
    {
        $role = $request->input('role', 'student');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,lecturer,student',
        ];

        if ($role === 'lecturer') {
            $rules['faculty'] = 'required|string|max:255';
        }

        if ($role === 'student') {
            $rules['matric_no'] = 'required|string|max:255';
            $rules['phone_no'] = 'nullable|string|max:50';
            $rules['course'] = 'nullable|string|max:255';
            $rules['lecturer_id'] = 'nullable|integer|exists:lecturers,lecturer_id';
        }

        $data = $request->validate($rules);
        $password = Hash::make($data['password']);

        try {
            if ($role === 'admin') {
                $model = Admin::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                ]);
                Auth::guard('admin')->login($model);
                return redirect('/admin/dashboard');
            }

            if ($role === 'lecturer') {
                $model = Lecturer::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                    'faculty' => $data['faculty'],
                ]);
                Auth::guard('lecturer')->login($model);
                return redirect('/lecturer/dashboard');
            }

            if ($role === 'student') {
                $lecturerId = $data['lecturer_id'] ?? Lecturer::first()?->lecturer_id;

                if (! $lecturerId) {
                    $lecturer = Lecturer::create([
                        'name' => 'Default Lecturer',
                        'email' => 'lecturer@default.local',
                        'password' => Hash::make('password123'),
                        'faculty' => 'Default Faculty',
                    ]);
                    $lecturerId = $lecturer->lecturer_id;
                }

                $model = Student::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                    'matric_no' => $data['matric_no'],
                    'phone_no' => $data['phone_no'] ?? 'N/A',
                    'course' => $data['course'] ?? 'Unknown',
                    'lecturer_id' => $lecturerId,
                ]);
                Auth::guard('student')->login($model);
                return redirect('/student/dashboard');
            }
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Unable to register: ' . $e->getMessage()]);
        }

        return back()->withInput()->with('error', 'Unable to register.');
    }
}