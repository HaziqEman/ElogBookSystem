<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\SupervisorWeekReviewRequest;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\SupervisorWeekService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorWeekController extends Controller
{
    protected function supervisor()
    {
        return Auth::guard('supervisor')->user();
    }

    protected function ownStudent($id): Student
    {
        return Student::where('supervisor_id', $this->supervisor()->supervisor_id)->findOrFail($id);
    }

    /** Oldest week that is waiting, across all of this supervisor's students. */
    public function next(SupervisorWeekService $weekService)
    {
        $next = $weekService->nextWaitingWeek($this->supervisor());

        if ($next) {
            return redirect('/supervisor/students/'.$next['student_id'].'?week='.$next['week_no']);
        }

        return redirect('/supervisor/dashboard')->with('success', 'No weeks are waiting for your review.');
    }

    public function show(Request $request, $id, SupervisorWeekService $weekService)
    {
        $student = $this->ownStudent($id);
        $now = Carbon::now(Attendance::TIMEZONE);
        $supervisor = $this->supervisor();
        $data = $weekService->pageData($student, $supervisor, $now, $request->query('week'));

        return view('supervisor.student-week', [
            'student' => $student,
            'supervisor' => $supervisor,
        ] + $data);
    }

    /** Builds the AI summary card (returns HTML so the page can swap it in). */
    public function summary(Request $request, $id, $week, SupervisorWeekService $weekService)
    {
        $student = $this->ownStudent($id);
        $now = Carbon::now(Attendance::TIMEZONE);
        $data = $weekService->summaryData($student, (int) $week, $request->boolean('force'), $now);
        abort_unless($data !== null, 404);

        if ($data['empty']) {
            return response()->json(['message' => 'This week has no entries to summarise.'], 422);
        }

        return response()->json([
            'html' => view('supervisor.partials.week-summary', [
                'summary' => $data['summary'],
                'missing' => $data['missing'],
                'generatedAt' => $data['generatedAt'],
            ])->render(),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * One decision per entry in a single submit. Entries left on "Validate" are validated;
     * entries flagged "Needs revision" need a comment.
     */
    public function review(Request $request, $id, $week, SupervisorWeekService $weekService)
    {
        $student = $this->ownStudent($id);
        $supervisor = $this->supervisor();
        $week = (int) $week;
        $back = '/supervisor/students/'.$student->student_id.'?week='.$week;
        $submission = $weekService->submission($student, $week);

        if (! $submission) {
            return redirect($back)->with('error', 'This week has not been sent to you yet.');
        }

        $reviewRequest = app(SupervisorWeekReviewRequest::class);
        $data = $reviewRequest->validated();
        $result = $weekService->review($student, $supervisor, $submission, $week, $data, $reviewRequest);

        if ($result['errors']) {
            return redirect($result['redirect'])->withInput()->withErrors($result['errors']);
        }

        $response = redirect($result['redirect']);

        if ($result['input']) {
            $response->withInput();
        }

        return $result['flash']
            ? $response->with($result['flash'], $result['message'])
            : $response;
    }
}
