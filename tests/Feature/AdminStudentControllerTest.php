<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminStudentControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $lecturerSequence = 0;

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

    public function test_update_preserves_optional_values_when_they_are_omitted(): void
    {
        $this->authenticateAdmin();
        $lecturer = $this->createLecturer();
        $student = $this->createStudent($lecturer, [
            'phone_no' => '0123456789',
            'course' => 'Existing Course',
        ]);

        $this->put('/admin/students/'.$student->student_id, [
            'name' => 'Updated Student',
            'lecturer_id' => $lecturer->lecturer_id,
        ])->assertRedirect('/admin/students');

        $student->refresh();
        $this->assertSame('Updated Student', $student->name);
        $this->assertSame('0123456789', $student->phone_no);
        $this->assertSame('Existing Course', $student->course);
    }

    public function test_bulk_can_assign_a_different_lecturer(): void
    {
        $this->authenticateAdmin();
        $student = $this->createStudent();
        $newLecturer = $this->createLecturer();

        $this->post('/admin/students/bulk', [
            'action' => 'assign_lecturer',
            'ids' => [$student->student_id],
            'lecturer_id' => $newLecturer->lecturer_id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($newLecturer->lecturer_id, $student->refresh()->lecturer_id);
    }

    public function test_bulk_delete_removes_the_selected_student(): void
    {
        $this->authenticateAdmin();
        $student = $this->createStudent();

        $this->post('/admin/students/bulk', [
            'action' => 'delete',
            'ids' => [$student->student_id],
        ])->assertRedirect('/admin/students')->assertSessionHas('success');

        $this->assertDatabaseMissing('students', ['student_id' => $student->student_id]);
    }

    public function test_admin_can_export_student_csv(): void
    {
        $this->authenticateAdmin();
        $student = $this->createStudent();

        $response = $this->get('/admin/students/export')->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Matric,Name,Email,Phone,Course', $content);
        $this->assertStringContainsString($student->matric_no, $content);
        $this->assertStringContainsString($student->name, $content);
    }

    public function test_password_reset_hashes_the_temporary_password_and_flashes_it(): void
    {
        $this->authenticateAdmin();
        $student = $this->createStudent();

        $this->post('/admin/students/'.$student->student_id.'/reset-password')
            ->assertRedirect()
            ->assertSessionHas('temp_for', $student->name)
            ->assertSessionHas('temp_password', function ($temporary) use ($student): bool {
                return is_string($temporary)
                    && strlen($temporary) === 10
                    && Hash::check($temporary, $student->fresh()->password);
            });
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
        $this->lecturerSequence++;

        return Lecturer::create([
            'name' => 'Test Lecturer '.$this->lecturerSequence,
            'email' => 'lecturer'.$this->lecturerSequence.'@example.test',
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
