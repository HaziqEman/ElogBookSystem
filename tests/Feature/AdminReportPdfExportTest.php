<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pdf_export_renders_all_students_and_logbooks_without_per_student_queries(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'PDF Export Lecturer',
            'email' => 'pdf-export-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $admin = Admin::create([
            'name' => 'PDF Export Admin',
            'email' => 'pdf-export-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($admin, 'admin');

        $this->createStudentWithLogbooks($lecturer, 1);
        $singleCount = $this->countDatabaseQueries(function (): void {
            $response = $this->get('/admin/reports/export/pdf');
            $response->assertOk();
        });

        for ($sequence = 2; $sequence <= 25; $sequence++) {
            $this->createStudentWithLogbooks($lecturer, $sequence);
        }

        $response = null;
        $multipleCount = $this->countDatabaseQueries(function () use (&$response): void {
            $response = $this->get('/admin/reports/export/pdf');
            $response->assertOk();
        });

        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $students = Student::with(['lecturer', 'logbooks'])
            ->orderBy('student_id')
            ->get();
        $html = view('admin.reports.pdf', ['students' => $students])->render();

        $this->assertCount(25, $students);
        $this->assertStringContainsString('PDF Export Student 01', $html);
        $this->assertStringContainsString('PDF Export Student 25', $html);
        $this->assertStringContainsString('Student 25 logbook 3', $html);
        $this->assertLessThanOrEqual(
            $singleCount + 4,
            $multipleCount,
            "PDF export queries grew from {$singleCount} to {$multipleCount} for 25 students."
        );
    }

    private function createStudentWithLogbooks(Lecturer $lecturer, int $sequence): void
    {
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => sprintf('PDFEXPORT%03d', $sequence),
            'name' => sprintf('PDF Export Student %02d', $sequence),
            'email' => sprintf('pdf-export-student-%02d@example.test', $sequence),
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);

        for ($logbookSequence = 1; $logbookSequence <= 3; $logbookSequence++) {
            Logbook::create([
                'student_id' => $student->student_id,
                'week_no' => $logbookSequence,
                'description' => sprintf(
                    'Student %d logbook %d',
                    $sequence,
                    $logbookSequence
                ),
                'activity_date' => now()->subDays($logbookSequence)->toDateString(),
                'status' => Logbook::STATUS_PENDING,
            ]);
        }
    }
}
