<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentLogbookEditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_an_editable_logbook_entry(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => Hash::make('password123'),
            'faculty' => 'Test Faculty',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'Test Course',
        ]);
        $logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Previous project work and results.',
            'activity_date' => '2026-11-10',
            'status' => Logbook::STATUS_APPROVED,
            'supervisor_status' => Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
        ]);

        $this->actingAs($student, 'student')
            ->post('/student/logbook/update/'.$logbook->logbook_id, [
                'week_no' => 2,
                'description' => 'Completed the next project milestone and documented results.',
                'activity_date' => '2026-11-17',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['message' => 'Entry updated and sent back for review.']);

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $logbook->logbook_id,
            'week_no' => 2,
            'title' => 'Week 2',
            'status' => Logbook::STATUS_PENDING,
            'supervisor_status' => Logbook::SUPERVISOR_STATUS_PENDING,
        ]);
    }
}
