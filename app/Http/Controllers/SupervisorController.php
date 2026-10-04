<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SupervisorController extends Controller
{
    public function dashboard()
    {
        $supervisor = Auth::guard('supervisor')->user();

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->withCount('logbooks')
            ->orderBy('name')
            ->get();

        $base = Logbook::whereHas('student', function ($query) use ($supervisor) {
            $query->where('supervisor_id', $supervisor->supervisor_id);
        });

        $pending = (clone $base)->where('supervisor_status', 'Pending')->count();
        $validated = (clone $base)->where('supervisor_status', 'Validated')->count();
        $revision = (clone $base)->where('supervisor_status', 'Revision Requested')->count();

        $recent = (clone $base)
            ->with('student')
            ->orderByDesc('created_at')
            ->orderByDesc('logbook_id')
            ->take(15)
            ->get();

        $totalEntries = $students->sum('logbooks_count');

        return view('supervisor.dashboard', compact(
            'supervisor',
            'students',
            'recent',
            'totalEntries',
            'pending',
            'validated',
            'revision'
        ));
    }

    public function showPassword()
    {
        $supervisor = Auth::guard('supervisor')->user();

        return view('supervisor.password', compact('supervisor'));
    }

    public function updatePassword(Request $request)
    {
        $supervisor = Auth::guard('supervisor')->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'password.different' => 'The new password must be different from the current one.',
        ]);

        if (! Hash::check($data['current_password'], $supervisor->password)) {
            return back()->withErrors(['current_password' => 'The current password is not correct.']);
        }

        $supervisor->password = Hash::make($data['password']);
        $supervisor->must_change_password = false;
        $supervisor->save();

        return redirect('/supervisor/dashboard')->with('success', 'Password updated.');
    }
}