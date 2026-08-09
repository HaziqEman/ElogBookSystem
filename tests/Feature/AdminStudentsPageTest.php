<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Student;
use App\Models\Lecturer;

class AdminStudentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_students_page_renders()
    {
        $lecturer = Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
        ]);

        Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);

        $response = $this->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('Student Management');
    }
}
