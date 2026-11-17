<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceFile;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentAttendanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_persists_work_mode_and_location_state(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs($student, 'student')->postJson('/student/attendance/clock-in', [
            'work_mode' => 'Home',
        ]);

        $response->assertOk()->assertJsonStructure(['message']);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->student_id,
            'status' => 'Present',
            'work_mode' => 'Home',
            'location_status' => 'unavailable',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_mc_day_off_persists_its_certificate(): void
    {
        $student = $this->createStudent();
        $leaveDate = now(Attendance::TIMEZONE)->toDateString();

        $this->actingAs($student, 'student')
            ->post('/student/attendance/day-off', [
                'day_off_type' => 'MC',
                'leave_date' => $leaveDate,
                'mc_file' => UploadedFile::fake()->image('medical-certificate.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance = Attendance::where('student_id', $student->student_id)->firstOrFail();

        $this->assertSame('Leave', $attendance->status);
        $this->assertSame('MC', $attendance->day_off_type);
        $this->assertSame('image/jpeg', $attendance->mc_mime);
        $this->assertDatabaseHas('attendance_files', [
            'attendance_id' => $attendance->attendance_id,
        ]);
        $this->assertNotEmpty(AttendanceFile::find($attendance->attendance_id)?->content_base64);
    }

    private function createStudent(): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Attendance Lecturer',
            'email' => 'attendance.lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
        ]);

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'ATTENDANCE001',
            'name' => 'Attendance Student',
            'email' => 'attendance.student@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
    }
}
