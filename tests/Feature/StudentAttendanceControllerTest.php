<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_summarizes_the_current_month_and_lists_recent_records(): void
    {
        $today = Carbon::parse('2026-11-17 12:00:00', Attendance::TIMEZONE);
        $this->travelTo($today);
        $student = $this->authenticateStudent();
        $present = $this->createAttendance($student, $today->toDateString(), [
            'status' => 'Present',
            'check_in' => $today->copy()->subHours(2),
            'check_out' => $today->copy()->subMinutes(30),
        ]);
        $this->createAttendance($student, $today->copy()->subDay()->toDateString(), [
            'status' => 'Leave',
            'day_off_type' => 'Leave',
        ]);

        $this->get('/student/attendance')
            ->assertOk()
            ->assertViewHas('attendance', fn ($attendance) => $attendance->attendance_id === $present->attendance_id)
            ->assertViewHas('monthPresent', 1)
            ->assertViewHas('monthLeave', 1)
            ->assertViewHas('monthMinutes', 90)
            ->assertViewHas('records', fn ($records) => $records->count() === 2);
    }

    public function test_clock_in_rounds_shared_coordinates_and_rejects_a_duplicate(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 09:00:00', Attendance::TIMEZONE));
        $student = $this->authenticateStudent();
        Http::fake(['*' => Http::response(['display_name' => 'Test location'], 200)]);

        $this->postJson('/student/attendance/clock-in', [
            'work_mode' => 'Workplace',
            'location_status' => 'shared',
            'latitude' => 3.141592,
            'longitude' => 101.686912,
            'accuracy' => 12.6,
        ])->assertOk();

        $attendance = Attendance::where('student_id', $student->student_id)->firstOrFail();
        $this->assertSame(3.1416, $attendance->latitude);
        $this->assertSame(101.6869, $attendance->longitude);
        $this->assertSame(13, $attendance->location_accuracy);
        $this->assertSame('Test location', $attendance->location_name);
        Http::assertSentCount(1);

        $duplicate = $this->postJson('/student/attendance/clock-in', ['work_mode' => 'Home']);
        $duplicate
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You already clocked in today at '.$attendance->checkInLocal().'.',
            ]);
    }

    public function test_clock_out_requires_clock_in_and_records_worked_time(): void
    {
        $today = Carbon::parse('2026-11-17 09:00:00', Attendance::TIMEZONE);
        $this->travelTo($today);
        $this->authenticateStudent();

        $this->postJson('/student/attendance/clock-out')
            ->assertStatus(422)
            ->assertExactJson(['message' => 'Please clock in first.']);

        $clockIn = $this->postJson('/student/attendance/clock-in', ['work_mode' => 'Home']);
        $clockIn->assertOk();
        $this->travelTo($today->copy()->addMinutes(30));

        $this->postJson('/student/attendance/clock-out')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Clocked out at 09:30. Worked 0h 30m.']);
    }

    public function test_leave_note_and_mc_certificate_are_required_with_current_messages(): void
    {
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();
        $this->authenticateStudent();
        $this->post('/student/attendance/day-off', [
            'day_off_type' => 'Leave',
            'leave_date' => $today,
        ])->assertRedirect()->assertSessionHasErrors(['note']);

        $this->post('/student/attendance/day-off', [
            'day_off_type' => 'MC',
            'leave_date' => $today,
        ])->assertRedirect()->assertSessionHasErrors(['mc_file']);
    }

    public function test_day_off_cannot_replace_a_day_with_a_clock_in(): void
    {
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();
        $student = $this->authenticateStudent();
        $this->createAttendance($student, $today, [
            'status' => 'Present',
            'check_in' => now(),
        ]);

        $this->post('/student/attendance/day-off', [
            'day_off_type' => 'Leave',
            'leave_date' => $today,
            'note' => 'Personal leave',
        ])->assertRedirect()->assertSessionHasErrors(['leave_date']);
    }

    public function test_day_off_note_is_trimmed_and_today_can_be_cancelled(): void
    {
        $today = Carbon::now(Attendance::TIMEZONE)->toDateString();
        $student = $this->authenticateStudent();

        $this->post('/student/attendance/day-off', [
            'day_off_type' => 'Leave',
            'leave_date' => $today,
            'note' => '  Personal leave  ',
        ])->assertRedirect()->assertSessionHas('success');

        $attendance = Attendance::where('student_id', $student->student_id)->firstOrFail();
        $this->assertSame('Personal leave', $attendance->note);

        $this->postJson('/student/attendance/day-off/'.$attendance->attendance_id.'/cancel')
            ->assertOk()
            ->assertJsonFragment(['message' => 'The day off was removed.']);

        $this->assertDatabaseMissing('attendances', ['attendance_id' => $attendance->attendance_id]);
    }

    public function test_past_day_off_cannot_be_cancelled(): void
    {
        $student = $this->authenticateStudent();
        $past = $this->createAttendance($student, Carbon::now(Attendance::TIMEZONE)->subDay()->toDateString(), [
            'status' => 'Leave',
            'day_off_type' => 'Leave',
        ]);

        $this->postJson('/student/attendance/day-off/'.$past->attendance_id.'/cancel')
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Days that have already passed cannot be changed.']);

        $this->assertDatabaseHas('attendances', ['attendance_id' => $past->attendance_id]);
    }

    private function authenticateStudent(): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Attendance Lecturer',
            'email' => 'attendance.lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'ATTENDANCE001',
            'name' => 'Attendance Student',
            'email' => 'attendance.student@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);

        $this->actingAs($student, 'student');

        return $student;
    }

    private function createAttendance(Student $student, string $date, array $attributes = []): Attendance
    {
        return Attendance::create(array_merge([
            'student_id' => $student->student_id,
            'attendance_date' => $date,
            'status' => 'Present',
        ], $attributes));
    }
}
