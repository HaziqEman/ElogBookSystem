<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Attachment;
use App\Models\CalendarDailyNote;
use App\Models\CalendarDailyNoteImage;
use App\Models\Logbook;
use App\Models\Todo;
use App\Services\AttachmentStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentCalendarController extends Controller
{
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();
        $tz = Attendance::TIMEZONE;
        $today = Carbon::now($tz)->startOfDay();

        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->query('month', $today->format('Y-m')), $tz)->startOfMonth();

            if ($month->year < 2000 || $month->year > 2100) {
                throw new \InvalidArgumentException('Month out of range.');
            }
        } catch (\Throwable $e) {
            $month = $today->copy()->startOfMonth();
        }

        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $from = $gridStart->toDateString();
        $to = $gridEnd->toDateString();

        $logsByDate = Logbook::where('student_id', $student->student_id)
            ->whereBetween('activity_date', [$from, $to])
            ->get()
            ->groupBy(fn ($log) => Carbon::parse($log->activity_date)->toDateString());

        $attendanceByDate = Attendance::where('student_id', $student->student_id)
            ->whereBetween('attendance_date', [$from, $to])
            ->get()
            ->keyBy(fn ($record) => $record->attendance_date->toDateString());

        $todosByDate = Todo::where('student_id', $student->student_id)
            ->whereBetween('due_date', [$from, $to])
            ->get()
            ->groupBy(fn ($todo) => $todo->due_date->toDateString());

        $notesByDate = CalendarDailyNote::where('student_id', $student->student_id)
            ->whereBetween('note_date', [$from, $to])
            ->with('images')
            ->get()
            ->keyBy(fn ($note) => $note->note_date->toDateString());

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $dayLogs = $logsByDate->get($key, collect());
                $dayTodos = $todosByDate->get($key, collect());
                $dailyNote = $notesByDate->get($key);
                $dayAttendance = $attendanceByDate->get($key);

                $week[] = [
                    'date' => $cursor->copy(),
                    'in_month' => $cursor->month === $month->month,
                    'is_today' => $key === $today->toDateString(),
                    'is_weekend' => $cursor->isWeekend(),
                    'logs' => $dayLogs->count(),
                    'attendance' => $dayAttendance,
                    'daily_note' => $dailyNote,
                    'tasks_open' => $dayTodos->where('is_done', false)->count(),
                    'tasks_done' => $dayTodos->where('is_done', true)->count(),
                    'log_items' => $dayLogs->map(fn ($log) => [
                        'title' => 'Week '.$log->week_no,
                        'summary' => Str::limit(trim((string) $log->description), 90),
                        'locked' => ! $log->isEditableByStudent(),
                    ])->values()->all(),
                    'task_items' => $dayTodos->map(fn ($todo) => [
                        'id' => $todo->todo_id,
                        'title' => $todo->title,
                        'done' => (bool) $todo->is_done,
                        'assigned' => ! empty($todo->supervisor_id),
                    ])->values()->all(),
                    'payload' => [
                        'date' => $key,
                        'label' => $cursor->format('l, j F Y'),
                        'logs' => $dayLogs->map(fn ($log) => [
                            'title' => 'Week '.$log->week_no,
                            'summary' => Str::limit(trim((string) $log->description), 90),
                            'locked' => ! $log->isEditableByStudent(),
                        ])->values()->all(),
                        'tasks' => $dayTodos->map(fn ($todo) => [
                            'id' => $todo->todo_id,
                            'title' => $todo->title,
                            'done' => (bool) $todo->is_done,
                            'assigned' => $todo->isAssigned(),
                        ])->values()->all(),
                        'attendance' => $dayAttendance ? [
                            'status' => $dayAttendance->status === Attendance::STATUS_LEAVE ? 'Leave' : 'Present',
                            'in' => $dayAttendance->checkInLocal(),
                            'note' => $dayAttendance->note,
                            'is_leave' => $dayAttendance->status === Attendance::STATUS_LEAVE,
                        ] : null,
                        'note' => $dailyNote?->body ?? '',
                        'images' => $dailyNote?->images->map(fn ($image) => [
                            'id' => $image->daily_note_image_id,
                            'url' => $image->url,
                            'name' => $image->file_name,
                        ])->values()->all() ?? [],
                    ],
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        $monthKey = $month->format('Y-m');
        $monthLogs = $logsByDate->filter(fn ($group, $key) => str_starts_with($key, $monthKey))->sum(fn ($group) => $group->count());
        $monthPresent = $attendanceByDate->filter(
            fn ($record, $key) => str_starts_with($key, $monthKey) && $record->status === Attendance::STATUS_PRESENT
        )->count();
        $monthTasks = $todosByDate->filter(fn ($group, $key) => str_starts_with($key, $monthKey))
            ->sum(fn ($group) => $group->where('is_done', false)->count());

        $prevMonth = $month->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $month->copy()->addMonthNoOverflow()->format('Y-m');
        $isCurrentMonth = $month->format('Y-m') === $today->format('Y-m');
        $selectedDate = $this->selectedDate($request, $month, $today);

        return view('student.calendar', compact(
            'student',
            'month',
            'weeks',
            'prevMonth',
            'nextMonth',
            'isCurrentMonth',
            'monthLogs',
            'monthPresent',
            'monthTasks',
            'selectedDate'
        ));
    }

    public function saveDailyNote(Request $request, AttachmentStorage $storage)
    {
        $data = $request->validate([
            'note_date' => 'required|date_format:Y-m-d',
            'body' => 'nullable|string|max:5000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,gif,webp,heic,heif|max:10240',
        ], [
            'images.max' => 'You can upload up to 5 images at a time.',
            'images.*.image' => 'Daily note attachments must be image files.',
            'images.*.max' => 'Each image must be 10 MB or smaller.',
        ]);

        $student = Auth::guard('student')->user();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $data, $student, $storage, &$storedPaths) {
                $note = CalendarDailyNote::firstOrNew([
                    'student_id' => $student->student_id,
                    'note_date' => $data['note_date'],
                ]);
                $note->body = trim((string) ($data['body'] ?? '')) ?: null;
                $note->save();

                foreach ($request->file('images', []) as $file) {
                    $stored = $storage->store($file);
                    $storedPaths[] = $stored['file_path'];
                    $note->images()->create($stored);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                try {
                    $storage->delete(new Attachment(['file_path' => $path]));
                } catch (\Throwable $cleanupError) {
                    Log::error('Calendar daily note image cleanup failed: '.$cleanupError->getMessage(), [
                        'student_id' => $student->student_id,
                        'file_path' => $path,
                        'exception' => $cleanupError,
                    ]);
                }
            }

            Log::error('Calendar daily note save failed: '.$e->getMessage(), [
                'student_id' => $student->student_id,
                'note_date' => $data['note_date'],
                'exception' => $e,
            ]);

            return back()->withInput()->with('error', 'Your daily note or images could not be saved. Please try again.');
        }

        return redirect($this->calendarUrl($data['note_date']))->with('success', 'Daily note saved.');
    }

    public function deleteDailyNoteImage(Request $request, int $imageId, AttachmentStorage $storage)
    {
        $student = Auth::guard('student')->user();
        $image = CalendarDailyNoteImage::whereHas('dailyNote', fn ($query) => $query
            ->where('student_id', $student->student_id))
            ->findOrFail($imageId);

        try {
            $storage->delete(new Attachment(['file_path' => $image->file_path]));
            $image->delete();
        } catch (\Throwable $e) {
            Log::error('Calendar daily note image deletion failed: '.$e->getMessage(), [
                'student_id' => $student->student_id,
                'daily_note_image_id' => $image->daily_note_image_id,
                'exception' => $e,
            ]);

            return back()->with('error', 'The image could not be removed. Please try again.');
        }

        return redirect($this->calendarUrl($image->dailyNote->note_date->toDateString()))
            ->with('success', 'Image removed.');
    }

    private function selectedDate(Request $request, Carbon $month, Carbon $today): string
    {
        $selected = $request->query('selected');
        if (is_string($selected) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected)) {
            try {
                $date = Carbon::createFromFormat('!Y-m-d', $selected, Attendance::TIMEZONE);
                if ($date && $date->format('Y-m-d') === $selected && $date->format('Y-m') === $month->format('Y-m')) {
                    return $selected;
                }
            } catch (\Throwable) {
                // Fall back to the current date or the first day of the requested month.
            }
        }

        return $month->format('Y-m') === $today->format('Y-m')
            ? $today->toDateString()
            : $month->toDateString();
    }

    private function calendarUrl(string $date): string
    {
        $month = substr($date, 0, 7);

        return '/student/calendar?month='.urlencode($month).'&selected='.urlencode($date);
    }
}
