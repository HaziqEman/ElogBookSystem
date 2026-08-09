<?php

namespace App\Http\Controllers;
use App\Models\Feedback;
use App\Models\Logbook;

use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function dashboard()
    {
        $student = Auth::guard('student')->user();

        return view('student.dashboard', compact('student'));
    }

    public function feedback()
    {
        $student = Auth::guard('student')->user();
        $feedbacks = Feedback::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })
        ->with('logbook', 'lecturer')
        ->latest()
        ->get();

        return view('student.feedback', compact('feedbacks'));
    }
}