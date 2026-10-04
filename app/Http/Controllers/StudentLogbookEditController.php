<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentLogbookEditController extends Controller
{
    public function edit($id)
    {
        $student = Auth::guard('student')->user();

        $logbook = $student->logbooks()
            ->with(['attachments', 'feedbacks.lecturer', 'feedbacks.supervisor'])
            ->findOrFail($id);

        if (! $logbook->isEditableByStudent()) {
            return redirect('/student/dashboard')->with('error', 'This entry has been approved and can no longer be edited.');
        }

        return view('student.logbook_edit', compact('logbook'));
    }

    public function update(Request $request, $id)
    {
        $student = Auth::guard('student')->user();
        $logbook = $student->logbooks()->findOrFail($id);

        if (! $logbook->isEditableByStudent()) {
            return redirect('/student/dashboard')->with('error', 'This entry has been approved and can no longer be edited.');
        }

        $data = $request->validate([
            'week_no' => 'required|integer',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:5120',
        ]);

        try {
            DB::transaction(function () use ($request, $logbook, $data) {
                $logbook->update([
                    'week_no' => $data['week_no'],
                    'title' => 'Week '.$data['week_no'],
                    'description' => $data['description'],
                    'activity_date' => $data['activity_date'],
                    'status' => 'Pending',
                    'supervisor_status' => 'Pending',
                ]);

                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $filename = time().'_'.$file->getClientOriginalName();
                    $file->move(public_path('uploads'), $filename);

                    Attachment::create([
                        'logbook_id' => $logbook->logbook_id,
                        'file_name' => $filename,
                        'file_path' => 'uploads/'.$filename,
                        'upload_date' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Logbook update failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Could not save your changes. Please try again.');
        }

        return redirect('/student/dashboard')->with('success', 'Entry updated and sent back for review.');
    }
}