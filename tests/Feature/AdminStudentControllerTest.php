<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminStudentControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $studentSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('students', function (Blueprint $table): void {
            $table->date('internship_start')->nullable();
            $table->unsignedInteger('internship_weeks')->nullable();
            $table->string('working_days')->nullable();
            $table->json('working_schedule')->nullable();
        });
    }

    public function test_admin_can_filter_students_and_choose_a_supported_page_size(): void
    {
        $this->authenticateAdmin();
        $lecturer = $this->createLecturer();
        $matching = $this->createStudent($lecturer, ['course' => 'Computing']);
        $this->createStudent($lecturer, ['course' => 'Design']);

        $this->get('/admin/students?course=Computing&per_page=25&sort=matric')
            ->assertOk()
            ->assertViewHas('students', fn ($students) => $students->perPage() === 25
                && $students->total() === 1
                && $students->first()->student_id === $matching->student_id);
    }

    public function test_admin_can_bulk_shift_start_dates_and_skips_students_without_dates(): void
    {
        $this->authenticateAdmin();
        $lecturer = $this->createLecturer();
        $withStartDate = $this->createStudent($lecturer, ['internship_start' => '2026-01-05']);
        $withoutStartDate = $this->createStudent($lecturer);

        $this->post('/admin/students/bulk', [
            'action' => 'shift_start',
            'ids' => [$withStartDate->student_id, $withoutStartDate->student_id],
            'shift_amount' => 7,
            'shift_unit' => 'days',
        ])
            ->assertRedirect()
            ->assertSessionHas(
                'success',
                'Moved the start date of 1 student 7 days later. 1 skipped because no start date was set.'
            );

        $this->assertSame('2026-01-12', $withStartDate->refresh()->internship_start->toDateString());
        $this->assertDatabaseHas('students', [
            'student_id' => $withoutStartDate->student_id,
            'internship_start' => null,
        ]);
    }

    public function test_admin_can_set_internship_details_and_schedule_in_bulk(): void
    {
        $this->authenticateAdmin();
        $student = $this->createStudent();

        $this->post('/admin/students/bulk', [
            'action' => 'set_internship',
            'ids' => [$student->student_id],
            'start_date' => '2026-03-02',
            'weeks' => 10,
            'working_days' => [1, 3, 5],
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('2026-03-02', $student->internship_start->toDateString());
        $this->assertSame(10, $student->internship_weeks);
        $this->assertSame('1,3,5', $student->working_days);
        $this->assertSame(
            [['from' => '1970-01-01', 'days' => '1,3,5']],
            json_decode($student->working_schedule, true)
        );
    }

    public function test_bulk_returns_empty_selection_before_validating_action_details(): void
    {
        $this->authenticateAdmin();

        $this->post('/admin/students/bulk', ['action' => 'shift_start'])
            ->assertRedirect()
            ->assertSessionHas('error', 'No students were selected.')
            ->assertSessionMissing('errors');
    }

    public function test_update_returns_not_found_before_validating_request_data(): void
    {
        $this->authenticateAdmin();

        $this->put('/admin/students/999999', [])
            ->assertNotFound();
    }

    public function test_store_trims_student_names_and_applies_current_defaults(): void
    {
        $this->authenticateAdmin();
        $lecturer = $this->createLecturer();

        $this->post('/admin/students', [
            'matric_no' => ' MAT001 ',
            'name' => ' New Student ',
            'email' => 'new.student@example.test',
            'password' => 'secret123',
            'phone_no' => null,
            'course' => null,
            'lecturer_id' => $lecturer->lecturer_id,
            'internship_start' => '2026-03-02',
        ])
            ->assertRedirect('/admin/students')
            ->assertSessionHas('success');

        $student = Student::where('email', 'new.student@example.test')->firstOrFail();
        $this->assertSame('MAT001', $student->matric_no);
        $this->assertSame('New Student', $student->name);
        $this->assertSame('N/A', $student->phone_no);
        $this->assertSame('Unknown', $student->course);
        $this->assertSame(14, $student->internship_weeks);
        $this->assertSame('1,2,3,4,5', $student->working_days);
        $this->assertSame(
            [['from' => '1970-01-01', 'days' => '1,2,3,4,5']],
            json_decode($student->working_schedule, true)
        );
    }

    private function authenticateAdmin(): void
    {
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($admin, 'admin');
    }

    private function createLecturer(): Lecturer
    {
        return Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
        ]);
    }

    private function createStudent(?Lecturer $lecturer = null, array $attributes = []): Student
    {
        $this->studentSequence++;
        $lecturer ??= $this->createLecturer();

        return Student::create(array_merge([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => sprintf('STU%03d', $this->studentSequence),
            'name' => 'Test Student '.$this->studentSequence,
            'email' => 'student'.$this->studentSequence.'@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ], $attributes));
    }
}
