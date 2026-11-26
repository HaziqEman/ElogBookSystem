<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_includes_day_details_for_the_modal(): void
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
            ->assertSee('id="dayModal"', false)
            ->assertSee('data-day=', false)
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

    private function createStudent(): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Calendar Lecturer',
            'email' => 'calendar.lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
        ]);

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'CALENDAR001',
            'name' => 'Calendar Student',
            'email' => 'calendar.student@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
    }
}
