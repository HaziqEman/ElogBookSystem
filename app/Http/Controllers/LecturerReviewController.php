<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Logbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LecturerReviewController extends Controller
{
    protected function ownedQuery()
    {
        $lecturer = Auth::guard('lecturer')->user();

        return Logbook::whereHas('student', function ($query) use ($lecturer) {
            $query->where('lecturer_id', $lecturer->lecturer_id);
        });
    }

    public function next()
    {
        $logbook = $this->ownedQuery()
            ->where('status', 'Pending')
            ->orderBy('created_at')
            ->first();

        if (! $logbook) {
            return redirect('/lecturer/dashboard')->with('success', 'No pending entries to review.');
        }

        return redirect('/lecturer/logbook/'.$logbook->logbook_id);
    }

    public function show($id)
    {
        $logbook = $this->ownedQuery()
            ->with(['student', 'attachments', 'feedbacks.lecturer'])
            ->findOrFail($id);

        return view('lecturer.review', compact('logbook'));
    }

    public function store(Request $request, $id)
    {
        $logbook = $this->ownedQuery()->findOrFail($id);
        $lecturer = Auth::guard('lecturer')->user();

        $data = $request->validate([
            'decision' => 'required|in:Approved,Rejected',
            'comment' => 'required_if:decision,Rejected|nullable|string|max:2000',
        ], [
            'comment.required_if' => 'Please write what the student needs to fix before rejecting.',
        ]);

        try {
            DB::transaction(function () use ($logbook, $lecturer, $data) {
                $logbook->update(['status' => $data['decision']]);

                $comment = trim((string) ($data['comment'] ?? ''));

                if ($comment !== '') {
                    Feedback::create([
                        'logbook_id' => $logbook->logbook_id,
                        'lecturer_id' => $lecturer->lecturer_id,
                        'comment' => $comment,
                        'feedback_date' => now()->toDateString(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Lecturer review failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Could not save the review. Please try again.');
        }

        return redirect('/lecturer/dashboard')->with('success', 'Entry marked as '.$data['decision'].'.');
    }
}
