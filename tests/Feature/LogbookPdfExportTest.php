<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogbookPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_logbook_page_searches_entries_without_internship_dates(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lee',
            'email' => 'lee@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Computer Science',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = $this->createStudent($lecturer, $supervisor, 'STU003');
        $entry = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'description' => 'Completed the assigned project work.',
            'activity_date' => '2026-10-30',
            'status' => 'Pending',
        ]);
        $this->actingAs($student, 'student')
            ->get('/student/logbooks')
            ->assertOk()
            ->assertSee('onclick="openUniversityModal()"', false)
            ->assertSee('id="universityModal"', false)
            ->assertSee('action="/student/university"', false)
            ->assertSee('Save &amp; Export PDF', false)
            ->assertSee('Your internship dates have not been set yet')
            ->assertSee('Completed the assigned project work.');

        $this->get('/student/logbooks?search=project')
            ->assertOk()
            ->assertSee('Search entries, files or feedback')
            ->assertSee('Matching entries')
            ->assertSee('Completed the assigned project work.');
    }

    public function test_student_lecturer_and_assigned_supervisor_can_export_logbooks(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lee',
            'email' => 'lee@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Computer Science',
        ]);
        $otherLecturer = Lecturer::create([
            'name' => 'Dr. Tan',
            'email' => 'tan@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Engineering',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $otherSupervisor = Supervisor::create([
            'name' => 'Other Supervisor',
            'email' => 'other-supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = $this->createStudent($lecturer, $supervisor, 'STU001');
        $otherStudent = $this->createStudent($otherLecturer, $otherSupervisor, 'STU002');

        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'description' => 'Completed the assigned project work.',
            'activity_date' => '2026-10-30',
            'status' => 'Pending',
        ]);

        $this->actingAs($student, 'student')
            ->get('/student/dashboard')
            ->assertOk()
            ->assertSee(route('student.logbooks.pdf'));
        $this->actingAs($student, 'student')
            ->get('/student/logbooks')
            ->assertOk()
            ->assertSee('Export PDF');
        $this->actingAs($lecturer, 'lecturer')
            ->get('/lecturer/students/'.$student->student_id)
            ->assertOk()
            ->assertSee('Export logbook PDF');
        $studentResponse = $this->actingAs($student, 'student')->get(route('student.logbooks.pdf'));
        $this->assertPdfDownload($studentResponse);

        $lecturerResponse = $this->actingAs($lecturer, 'lecturer')
            ->get(route('lecturer.student.logbooks.pdf', $student->student_id));
        $this->assertPdfDownload($lecturerResponse);

        $this->actingAs($lecturer, 'lecturer')
            ->get(route('lecturer.student.logbooks.pdf', $otherStudent->student_id))
            ->assertNotFound();

        $supervisorResponse = $this->actingAs($supervisor, 'supervisor')
            ->get(route('supervisor.student.logbooks.pdf', $student->student_id));
        $this->assertPdfDownload($supervisorResponse);

        $this->actingAs($supervisor, 'supervisor')
            ->get(route('supervisor.student.logbooks.pdf', $otherStudent->student_id))
            ->assertNotFound();
    }

    private function createStudent(Lecturer $lecturer, Supervisor $supervisor, string $matricNo): Student
    {
        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => $matricNo,
            'name' => 'Test Student '.$matricNo,
            'email' => strtolower($matricNo).'@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0123456789',
            'course' => 'Computer Science',
        ]);
    }

    private function assertPdfDownload($response): void
    {
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
