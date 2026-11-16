<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Conversation;
use App\Models\Logbook;
use App\Models\Student;
use App\Services\LecturerInsights;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

    public function show(Request $request, $id)
    {
        $lecturer = Auth::guard('lecturer')->user();
        $student = $this->ownStudent($id);
        $today = Carbon::now(Attendance::TIMEZONE);

        $insights = (new LecturerInsights($lecturer, $today))->build();
        $row = $insights['students']->get($student->student_id);
        $planner = $row['planner'];

        $weeks = [];
        $selected = null;

        if ($planner->hasStart()) {
            $from = $planner->firstMonday()->toDateString();
            $to = $planner->weekStart($planner->totalWeeks())->addDays(6)->toDateString();

            $logbooksByDate = Logbook::with('attachments')
                ->where('student_id', $student->student_id)
                ->whereBetween('activity_date', [$from, $to])
                ->orderBy('logbook_id')
                ->get()
                ->groupBy(fn ($log) => Carbon::parse($log->activity_date)->toDateString());

            $attendanceByDate = Schema::hasTable('attendances')
                ? Attendance::where('student_id', $student->student_id)
                    ->whereBetween('attendance_date', [$from, $to])
                    ->get()
                    ->keyBy(fn ($record) => $record->attendance_date->toDateString())
                : collect();

            $weeks = $planner->weeks($logbooksByDate, $attendanceByDate);

            $default = max(1, min($planner->totalWeeks(), $planner->currentWeek() ?? 1));
            $number = max(1, min($planner->totalWeeks(), (int) $request->query('week', $default)));
            $selected = $weeks[$number - 1] ?? null;
        }

        return view('lecturer.student', compact('student', 'row', 'planner', 'weeks', 'selected', 'today'));
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