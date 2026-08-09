<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_new_portal_shell(): void
    {
        $response = $this->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Smart eLogBook');
        $response->assertSee('Student Portal');
        $response->assertSee('Logbook Entries');
    }

    public function test_guest_auth_pages_render_without_dashboard_shell(): void
    {
        $loginResponse = $this->get('/');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('UiTM E-Logbook Portal');
        $loginResponse->assertDontSee('Smart eLogBook');

        $registerResponse = $this->get('/register');
        $registerResponse->assertStatus(200);
        $registerResponse->assertSee('Portal Registration');
        $registerResponse->assertDontSee('Smart eLogBook');
    }

    public function test_lecturer_dashboard_shows_assigned_students_and_logbook_statuses(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lee',
            'email' => 'lee@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);

        $assignedStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240001',
            'name' => 'Aina Aziz',
            'email' => 'aina@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'ITT626',
        ]);

        $otherStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240002',
            'name' => 'Nadia Rahman',
            'email' => 'nadia@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456788',
            'course' => 'ITT626',
        ]);

        Logbook::create([
            'student_id' => $assignedStudent->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Submitted weekly report',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        Logbook::create([
            'student_id' => $otherStudent->student_id,
            'week_no' => 2,
            'title' => 'Week 2',
            'description' => 'Completed review',
            'activity_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertStatus(200);
        $response->assertSee('2 Students');
        $response->assertSee('1 Submissions');
        $response->assertSee('1 Reviewed');
        $response->assertSee('Aina Aziz');
        $response->assertSee('Nadia Rahman');
    }

    public function test_lecturer_dashboard_counts_latest_submission_status_per_student(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lim',
            'email' => 'lim@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240003',
            'name' => 'Sara Ali',
            'email' => 'sara@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456787',
            'course' => 'ITT626',
        ]);

        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 3,
            'title' => 'Week 3',
            'description' => 'Needs revision',
            'activity_date' => now()->toDateString(),
            'status' => 'Rejected',
        ]);

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertStatus(200);
        $response->assertSee('1 Reviewed');
        $response->assertSee('Rejected');
        $response->assertSee('Status');
    }

    public function test_lecturer_dashboard_uses_newest_created_logbook_for_each_student(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Noor',
            'email' => 'noor@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240004',
            'name' => 'Hafiz Hamid',
            'email' => 'hafiz@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456786',
            'course' => 'ITT626',
        ]);

        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 4,
            'title' => 'Week 4',
            'description' => 'Older submission',
            'activity_date' => now()->addDay()->toDateString(),
            'status' => 'Approved',
        ]);

        $latestLogbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 5,
            'title' => 'Week 5',
            'description' => 'Newest submission',
            'activity_date' => now()->subDay()->toDateString(),
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Week 5');
        $response->assertSee('Newest submission');
        $response->assertSee('Pending');
    }

    public function test_student_dashboard_shows_updated_status_after_lecturer_review(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Noor',
            'email' => 'noor@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240004',
            'name' => 'Hafiz Hamid',
            'email' => 'hafiz@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456786',
            'course' => 'ITT626',
        ]);

        $logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 4,
            'title' => 'Week 4',
            'description' => 'Needs approval',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        $this->actingAs($lecturer, 'lecturer')
            ->post('/lecturer/approve/'.$logbook->logbook_id, ['comment' => 'Looks good'])
            ->assertRedirect('/lecturer/dashboard');

        $this->assertDatabaseHas('logbooks', ['logbook_id' => $logbook->logbook_id, 'status' => 'Approved']);

        $response = $this->actingAs($student, 'student')->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Approved');
    }
}
