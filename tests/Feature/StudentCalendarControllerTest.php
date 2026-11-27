<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CalendarDailyNote;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_renders_daily_drawer_and_date_payloads(): void
    {
        $day = Carbon::parse('2026-11-25', Attendance::TIMEZONE);
        $this->travelTo($day->copy()->setTime(12, 0));
        $student = $this->createStudent();

        $log = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 3,
            'description' => str_repeat('Logbook description ', 8),
            'activity_date' => $day->toDateString(),
        ]);
        $log->forceFill(['supervisor_status' => Logbook::SUPERVISOR_STATUS_VALIDATED])->save();
        $todo = Todo::create([
            'student_id' => $student->student_id,
            'title' => 'Finish project report',
            'due_date' => $day->toDateString(),
        ]);
        Attendance::create([
            'student_id' => $student->student_id,
            'attendance_date' => $day->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
            'check_in' => $day->copy()->setTime(9, 0),
        ]);

        $response = $this->actingAs($student, 'student')->get('/student/calendar?month=2026-11');

        $response->assertOk()
            ->assertSee('id="calendar-daily-panel"', false)
            ->assertSee('data-day=', false)
            ->assertSee('Your Notes')
            ->assertSee('Day Summary')
            ->assertSee('Filter View')
            ->assertSee('data-calendar-search', false)
            ->assertViewHas('weeks', function ($weeks) use ($day, $log, $todo) {
                foreach ($weeks as $week) {
                    foreach ($week as $calendarDay) {
                        if ($calendarDay['date']->toDateString() !== $day->toDateString()) {
                            continue;
                        }

                        return $calendarDay['log_items'] === [[
                            'title' => 'Week '.$log->week_no,
                            'summary' => \Illuminate\Support\Str::limit(trim((string) $log->description), 90),
                            'locked' => true,
                        ]]
                            && $calendarDay['task_items'] === [[
                                'id' => $todo->todo_id,
                                'title' => $todo->title,
                                'done' => false,
                                'assigned' => false,
                            ]]
                            && $calendarDay['tasks_open'] === 1;
                    }
                }

                return false;
            });
    }

    public function test_student_can_save_daily_notes_and_multiple_images_and_remove_an_image(): void
    {
        $date = '2026-11-25';
        $student = $this->createStudent();

        $response = $this->actingAs($student, 'student')
            ->from('/student/calendar?month=2026-11&selected='.$date)
            ->post('/student/calendar/daily-notes', [
                'note_date' => $date,
                'body' => 'Discuss progress with supervisor.',
                'images' => [
                    UploadedFile::fake()->image('meeting.jpg'),
                    UploadedFile::fake()->image('whiteboard.png'),
                ],
            ]);

        $response->assertRedirect('/student/calendar?month=2026-11&selected='.$date);
        $note = CalendarDailyNote::with('images')->where('student_id', $student->student_id)->firstOrFail();
        $this->assertSame('Discuss progress with supervisor.', $note->body);
        $this->assertCount(2, $note->images);

        foreach ($note->images as $image) {
            $this->delete('/student/calendar/daily-note-images/'.$image->daily_note_image_id)
                ->assertRedirect('/student/calendar?month=2026-11&selected='.$date);
            $this->assertDatabaseMissing('calendar_daily_note_images', [
                'daily_note_image_id' => $image->daily_note_image_id,
            ]);
        }
    }

    public function test_student_can_only_view_their_own_daily_notes_and_tasks_keep_the_selected_date(): void
    {
        $date = '2026-11-25';
        $student = $this->createStudent();
        $otherStudent = $this->createStudent('OTHER001');
        CalendarDailyNote::create([
            'student_id' => $otherStudent->student_id,
            'note_date' => $date,
            'body' => 'Private note from another student',
        ]);

        $this->actingAs($student, 'student')
            ->from('/student/calendar?month=2026-11&selected='.$date)
            ->post('/student/todos', [
                'title' => 'Submit weekly logbook',
                'due_date' => $date,
            ])->assertRedirect('/student/calendar?month=2026-11&selected='.$date);

        $this->assertTrue(Todo::where('student_id', $student->student_id)
            ->where('title', 'Submit weekly logbook')
            ->whereDate('due_date', $date)
            ->exists());

        $response = $this->get('/student/calendar?month=2026-11&selected='.$date);
        $response->assertOk()
            ->assertDontSee('Private note from another student')
            ->assertSee('Submit weekly logbook');
    }

    private function createStudent(string $matricNo = 'CALENDAR001'): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Calendar Lecturer',
            'email' => strtolower($matricNo).'.lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
        ]);

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => $matricNo,
            'name' => 'Calendar Student',
            'email' => strtolower($matricNo).'@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
    }
}
