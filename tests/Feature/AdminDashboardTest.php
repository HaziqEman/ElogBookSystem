<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attachment;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_enhanced_analytics_sections(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Ali',
            'email' => 'ali@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'CS',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240001',
            'name' => 'Aiman',
            'email' => 'aiman@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'IT',
        ]);

        $logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Test entry',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        Attachment::create([
            'logbook_id' => $logbook->logbook_id,
            'file_name' => 'sample.pdf',
            'file_path' => 'uploads/sample.pdf',
            'upload_date' => now()->toDateString(),
        ]);

        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Logbook Analytics');
        $response->assertSee('Recent Logbook Activity');
        $response->assertSee('Student Insights');
        $response->assertSee('Lecturer Insights');
        $response->assertSee('Quick Actions');
        $response->assertSee('System Information');
    }
}
