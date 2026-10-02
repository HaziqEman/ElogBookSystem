<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Logbook;
use App\Models\Feedback;

use Illuminate\Http\Request;

class LecturerLogbookController extends Controller
{
    public function dashboard()
    {
        $lecturer = Auth::guard('lecturer')->user();

        $logbooks = Logbook::whereHas('student', function ($query) use ($lecturer) {

    $query->where(
        'lecturer_id',
        $lecturer->lecturer_id
    );

        })
        ->with('student', 'attachments')
        ->latest()
        ->get();

        return view('lecturer.dashboard', compact('logbooks'));
    }

    public function show($id)
{
    $logbook = $this->assignedLogbooks()->with(
                    'student',
                    'attachments'
                )
                ->findOrFail($id);

    return view(
        'lecturer.review',
        compact('logbook')
    );
}
public function approve(
    Request $request,
    $id)
{
    $logbook = $this->assignedLogbooks()->findOrFail($id);

    $logbook->update([
        'status'=>'Approved'
    ]);

    Feedback::create([

        'logbook_id'=>$id,

        'lecturer_id'=> Auth::guard('lecturer')->user()->lecturer_id,

        'comment'=>$request->comment,

        'feedback_date'=>now()

    ]);

    return redirect(
        '/lecturer/dashboard'
    );
}

public function reject(Request $request,$id)
{
    $logbook = $this->assignedLogbooks()->findOrFail($id);

    $logbook->update([
        'status'=>'Rejected'
    ]);

    Feedback::create([

        'logbook_id'=>$id,

        'lecturer_id'=>Auth::guard('lecturer')->user()->lecturer_id,

        'comment'=>$request->comment,

        'feedback_date'=>now()

    ]);

    return redirect('/lecturer/dashboard'    );
}

private function assignedLogbooks()
{
    $lecturer = Auth::guard('lecturer')->user();

    return Logbook::whereHas('student', function ($query) use ($lecturer) {
        $query->where('lecturer_id', $lecturer->lecturer_id);
    });
}
}
