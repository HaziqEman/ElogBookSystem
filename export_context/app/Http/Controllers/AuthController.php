<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
            $request->session()->regenerate();
            return redirect('/student/dashboard');
        }

        $lecturer = Lecturer::where('email', $email)->first();
        if ($lecturer && Hash::check($password, $lecturer->password)) {
            Auth::guard('lecturer')->login($lecturer);
            $request->session()->regenerate();
            return redirect('/lecturer/dashboard');
        }

        $supervisor = Supervisor::where('email', $email)->first();
        if ($supervisor && Hash::check($password, $supervisor->password)) {
            Auth::guard('supervisor')->login($supervisor);
            $request->session()->regenerate();
            return redirect($supervisor->must_change_password ? '/supervisor/password' : '/supervisor/dashboard');
        }

        $admin = Admin::where('email', $email)->first();
        if ($admin && Hash::check($password, $admin->password)) {
            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();
            return redirect('/admin/dashboard');
        }

        return back()->withInput()->with('error', 'Invalid email or password.');
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        Auth::guard('lecturer')->logout();
        Auth::guard('supervisor')->logout();
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function switchModule($role)
    {
        $allowedRoles = ['student', 'lecturer', 'supervisor', 'admin'];

        if (! in_array($role, $allowedRoles, true)) {
            return redirect('/');
        }

        if (Auth::guard($role)->check()) {
            return match ($role) {
                'student' => redirect('/student/dashboard'),
                'lecturer' => redirect('/lecturer/dashboard'),
                'supervisor' => redirect('/supervisor/dashboard'),
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
            'role' => 'required|in:lecturer,student',
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

        if ($this->emailInUse($data['email'])) {
            return back()->withInput()->withErrors([
                'email' => 'This email is already used by another account.',
            ]);
        }

        $password = Hash::make($data['password']);

        try {
            if ($role === 'lecturer') {
                $model = Lecturer::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                    'faculty' => $data['faculty'],
                ]);
                Auth::guard('lecturer')->login($model);
                $request->session()->regenerate();
                return redirect('/lecturer/dashboard');
            }

            if ($role === 'student') {
                $lecturerId = $data['lecturer_id'] ?? Lecturer::first()?->lecturer_id;

                if (! $lecturerId) {
                    return back()->withInput()->withErrors([
                        'lecturer_id' => 'A lecturer must be available or selected before registering a student.',
                    ]);
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
                $request->session()->regenerate();
                return redirect('/student/dashboard');
            }
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Unable to register: ' . $e->getMessage()]);
        }

        return back()->withInput()->with('error', 'Unable to register.');
    }

    /**
     * Login stops at the first matching table, so one email must never exist in two role tables.
     */
    protected function emailInUse(string $email): bool
    {
        $email = strtolower(trim($email));

        foreach (['students', 'lecturers', 'admins', 'supervisors'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->whereRaw('lower(email) = ?', [$email])->exists()) {
                return true;
            }
        }

        return false;
    }
}