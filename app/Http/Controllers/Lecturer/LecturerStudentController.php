<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Conversation;
use App\Models\Logbook;
use App\Models\Student;
use App\Services\LecturerInsights;
use App\Services\SupervisorWeekService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LecturerStudentController extends Controller
{
    protected function ownStudent($id): Student
    {
        $lecturer = Auth::guard('lecturer')->user();

        return Student::with('supervisor')
            ->where('student_id', $id)
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->firstOrFail();
    }

    public function show(Request $request, $id, SupervisorWeekService $weekService)
    {
        $lecturer = Auth::guard('lecturer')->user();
        $student = $this->ownStudent($id);
        $today = Carbon::now(Attendance::TIMEZONE);

        $insights = (new LecturerInsights($lecturer, $today))->build();
        $row = $insights['students']->get($student->student_id);
        $planner = $row['planner'];

        $weekData = $weekService->pageData($student, $today, $request->query('week'));

        return view('lecturer.students.week', [
            'student' => $student,
            'row' => $row,
            'planner' => $planner,
            'today' => $today,
        ] + $weekData);
    }

    /** Open (or start) the chat with this student. */
    public function message($id)
    {
        $student = $this->ownStudent($id);
        $lecturer = Auth::guard('lecturer')->user();

        $conversation = Conversation::firstOrCreate([
            'student_id' => $student->student_id,
            'lecturer_id' => $lecturer->lecturer_id,
        ]);

        return redirect()->route('lecturer.messages.show', $conversation->conversation_id);
    }

    /** Old links to a single entry now open that student's week. */
    public function entry($id)
    {
        $lecturer = Auth::guard('lecturer')->user();

        $logbook = Logbook::with('student')->findOrFail($id);

        abort_unless($logbook->student && (int) $logbook->student->lecturer_id === (int) $lecturer->lecturer_id, 404);

        return redirect('/lecturer/students/'.$logbook->student_id.'?week='.$logbook->week_no.'#logbook');
    }
}
