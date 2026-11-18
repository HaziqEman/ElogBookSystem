<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDirectoryPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_students_directory_renders_filters_bulk_controls_and_row_actions(): void
    {
        $admin = $this->createAdmin();
        $lecturer = $this->createLecturer('Directory Lecturer', 'Directory Faculty');
        $supervisor = $this->createSupervisor('Directory Supervisor', 'Directory Company');
        $student = $this->createStudent(
            $lecturer,
            $supervisor,
            'Directory Student',
            'DIR001',
            'Directory Course'
        );
        $this->createStudent(
            $lecturer,
            $supervisor,
            'Directory Student Other Course',
            'DIR002',
            'Other Course'
        );

        $response = $this->actingAs($admin, 'admin')->get('/admin/students?'.http_build_query([
            'q' => 'Directory Student',
            'course' => 'Directory Course',
        ]));

        $response->assertOk()
            ->assertSee('Student Management')
            ->assertSee('Directory Student')
            ->assertDontSee('Directory Student Other Course')
            ->assertSee('name="q" value="Directory Student"', false)
            ->assertSee('name="course"', false)
            ->assertSee('name="lecturer"', false)
            ->assertSee('name="supervisor"', false)
            ->assertSee('name="status"', false)
            ->assertSee('Set internship dates / duration / days')
            ->assertSee('id="bulk-form"', false)
            ->assertSee('action="/admin/students/bulk?q=Directory+Student&amp;course=Directory+Course"', false)
            ->assertSee('data-total="1"', false)
            ->assertSee('id="select-all"', false)
            ->assertSee('name="ids[]"', false)
            ->assertSee('id="selected-count"', false)
            ->assertSee('id="apply-all"', false)
            ->assertSee('id="bulk-action"', false)
            ->assertSee('id="bulk-submit"', false)
            ->assertSee('data-noun="students"', false)
            ->assertSee('action="/admin/students/'.$student->student_id.'/reset-password"', false)
            ->assertSee('action="/admin/students/'.$student->student_id.'"', false)
            ->assertSee('href="/admin/students/'.$student->student_id.'/edit"', false)
            ->assertSee('Reset the password for Directory Student? A new temporary password will be shown once.')
            ->assertSee('Delete this student and all of their logbook entries?');
    }

    public function test_admin_lecturers_directory_renders_filters_bulk_controls_and_row_actions(): void
    {
        $admin = $this->createAdmin();
        $lecturer = $this->createLecturer('Directory Lecturer', 'Directory Faculty');
        $this->createStudent(
            $lecturer,
            null,
            'Directory Student',
            'DIR003',
            'Directory Course'
        );
        $this->createLecturer('Directory Lecturer Without Students', 'Directory Faculty');

        $response = $this->actingAs($admin, 'admin')->get('/admin/lecturers?'.http_build_query([
            'q' => 'Directory Lecturer',
            'faculty' => 'Directory Faculty',
            'state' => 'has_students',
        ]));

        $response->assertOk()
            ->assertSee('Lecturer Management')
            ->assertSee('Directory Lecturer')
            ->assertDontSee('<p class="font-semibold text-slate-800">Directory Lecturer Without Students</p>', false)
            ->assertSee('name="q" value="Directory Lecturer"', false)
            ->assertSee('name="faculty"', false)
            ->assertSee('name="state"', false)
            ->assertSee('Move their students to another lecturer')
            ->assertSee('id="bulk-form"', false)
            ->assertSee('action="/admin/lecturers/bulk?q=Directory+Lecturer&amp;faculty=Directory+Faculty&amp;state=has_students"', false)
            ->assertSee('data-total="1"', false)
            ->assertSee('id="select-all"', false)
            ->assertSee('name="ids[]"', false)
            ->assertSee('id="selected-count"', false)
            ->assertSee('id="apply-all"', false)
            ->assertSee('id="bulk-action"', false)
            ->assertSee('id="bulk-submit"', false)
            ->assertSee('data-noun="lecturers"', false)
            ->assertSee('action="/admin/lecturers/'.$lecturer->lecturer_id.'/reset-password"', false)
            ->assertSee('action="/admin/lecturers/'.$lecturer->lecturer_id.'"', false)
            ->assertSee('href="/admin/lecturers/'.$lecturer->lecturer_id.'/edit"', false)
            ->assertSee('Reset the password for Directory Lecturer? A new temporary password will be shown once.')
            ->assertSee('Move their 1 students to another lecturer first')
            ->assertSee('Delete this lecturer?');
    }

    public function test_admin_supervisors_directory_renders_filters_bulk_controls_and_row_actions(): void
    {
        $admin = $this->createAdmin();
        $supervisor = $this->createSupervisor('Directory Supervisor', 'Directory Company');
        $this->createSupervisor('Directory Supervisor Other Company', 'Other Company');

        $response = $this->actingAs($admin, 'admin')->get('/admin/supervisors?'.http_build_query([
            'q' => 'Directory Supervisor',
            'company' => 'Directory Company',
        ]));

        $response->assertOk()
            ->assertSee('Company Supervisor Management')
            ->assertSee('Directory Supervisor')
            ->assertDontSee('<p class="font-semibold text-slate-800">Directory Supervisor Other Company</p>', false)
            ->assertSee('name="q" value="Directory Supervisor"', false)
            ->assertSee('name="company"', false)
            ->assertSee('name="state"', false)
            ->assertSee('Require password change at next login')
            ->assertSee('id="bulk-form"', false)
            ->assertSee('action="/admin/supervisors/bulk?q=Directory+Supervisor&amp;company=Directory+Company"', false)
            ->assertSee('data-total="1"', false)
            ->assertSee('id="select-all"', false)
            ->assertSee('name="ids[]"', false)
            ->assertSee('id="selected-count"', false)
            ->assertSee('id="apply-all"', false)
            ->assertSee('id="bulk-action"', false)
            ->assertSee('id="bulk-submit"', false)
            ->assertSee('data-noun="supervisors"', false)
            ->assertSee('action="/admin/supervisors/'.$supervisor->supervisor_id.'/reset-password"', false)
            ->assertSee('action="/admin/supervisors/'.$supervisor->supervisor_id.'"', false)
            ->assertSee('href="/admin/supervisors/'.$supervisor->supervisor_id.'/edit"', false)
            ->assertSee('Reset the password for Directory Supervisor? A new temporary password will be shown once.')
            ->assertSee("Remove this supervisor? Their students will become unassigned.");
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Directory Admin',
            'email' => 'directory-admin@example.test',
            'password' => bcrypt('secret'),
        ]);
    }

    private function createLecturer(string $name, string $faculty): Lecturer
    {
        return Lecturer::create([
            'name' => $name,
            'email' => str($name)->slug('-').'@example.test',
            'password' => bcrypt('secret'),
            'faculty' => $faculty,
        ]);
    }

    private function createSupervisor(string $name, string $company): Supervisor
    {
        return Supervisor::create([
            'name' => $name,
            'email' => str($name)->slug('-').'@example.test',
            'password' => bcrypt('secret'),
            'company_name' => $company,
            'position' => 'Manager',
        ]);
    }

    private function createStudent(
        Lecturer $lecturer,
        ?Supervisor $supervisor,
        string $name,
        string $matricNo,
        string $course
    ): Student {
        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor?->supervisor_id,
            'matric_no' => $matricNo,
            'name' => $name,
            'email' => str($matricNo)->lower().'@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => $course,
        ]);
    }
}
