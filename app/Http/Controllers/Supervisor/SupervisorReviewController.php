<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\SupervisorReviewRequest;
use App\Models\Logbook;
use App\Services\NotificationService;
use App\Services\SupervisorReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SupervisorReviewController extends Controller
{
    protected function ownedQuery(): Builder
    {
        $supervisor = Auth::guard('supervisor')->user();

        return Logbook::whereHas('student', function ($query) use ($supervisor) {
            $query->where('supervisor_id', $supervisor->supervisor_id);
        });
    }

    public function next(): RedirectResponse
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

    public function show(int $id): View
    {
        $logbook = $this->ownedQuery()
            ->with(['student', 'attachments', 'feedbacks.lecturer', 'feedbacks.supervisor'])
            ->findOrFail($id);

        return view('supervisor.review', compact('logbook'));
    }

    public function store(
        SupervisorReviewRequest $request,
        SupervisorReviewService $reviews,
        int $id
    ): JsonResponse|RedirectResponse
    {
        $logbook = $this->ownedQuery()->findOrFail($id);
        $supervisor = Auth::guard('supervisor')->user();
        $data = $request->validated();

        try {
            $reviews->save(
                $logbook,
                $supervisor,
                $data['decision'],
                $data['comment'] ?? null
            );
        } catch (\Throwable $e) {
            Log::error('Supervisor review failed: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Could not save the review. Please try again.'], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Could not save the review. Please try again.');
        }

        app(NotificationService::class)->reviewGiven(
            $logbook,
            'supervisor',
            $supervisor->name,
            $data['decision'],
            $data['comment'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Entry marked as '.$data['decision'].'.']);
        }

        return redirect('/supervisor/dashboard')->with('success', 'Entry marked as '.$data['decision'].'.');
    }
}
