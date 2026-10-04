<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Logbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupervisorReviewController extends Controller
{
    protected function ownedQuery()
    {
        $supervisor = Auth::guard('supervisor')->user();

        return Logbook::whereHas('student', function ($query) use ($supervisor) {
            $query->where('supervisor_id', $supervisor->supervisor_id);
        });
    }

    public function next()
    {
        $logbook = $this->ownedQuery()
            ->where('supervisor_status', 'Pending')
            ->orderBy('created_at')
            ->first();

        if (! $logbook) {
            return redirect('/supervisor/dashboard')->with('success', 'No entries are waiting for your validation.');
        }

        return redirect('/supervisor/logbook/'.$logbook->logbook_id);
    }

    public function show($id)
    {
        $logbook = $this->ownedQuery()
            ->with(['student', 'attachments', 'feedbacks.lecturer', 'feedbacks.supervisor'])
            ->findOrFail($id);

        return view('supervisor.review', compact('logbook'));
    }

    public function store(Request $request, $id)
    {
        $logbook = $this->ownedQuery()->findOrFail($id);
        $supervisor = Auth::guard('supervisor')->user();

        $data = $request->validate([
            'decision' => 'required|in:Validated,Revision Requested',
            'comment' => 'required_if:decision,Revision Requested|nullable|string|max:2000',
        ], [
            'comment.required_if' => 'Please write what the student needs to change before requesting a revision.',
        ]);

        try {
            DB::transaction(function () use ($logbook, $supervisor, $data) {
                $logbook->update(['supervisor_status' => $data['decision']]);

                $comment = trim((string) ($data['comment'] ?? ''));

                if ($comment !== '') {
                    Feedback::create([
                        'logbook_id' => $logbook->logbook_id,
                        'supervisor_id' => $supervisor->supervisor_id,
                        'comment' => $comment,
                        'feedback_date' => now()->toDateString(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Supervisor review failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Could not save the review. Please try again.');
        }

        return redirect('/supervisor/dashboard')->with('success', 'Entry marked as '.$data['decision'].'.');
    }
}