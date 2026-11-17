<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('Student Management');
    }
}
