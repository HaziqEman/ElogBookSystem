<?php

namespace Tests\Unit;

use App\Exports\InternshipSheets;
use Tests\TestCase;

class InternshipMarksReportTest extends TestCase
{
    public function test_student_marks_sheet_includes_submitted_and_pending_students(): void
    {
        $sheet = InternshipSheets::marks([
            'students' => [
                [
                    'matric' => 'A001',
                    'name' => 'Submitted Student',
                    'course' => 'Computer Science',
                    'university' => 'Example University',
                    'lecturer' => 'Lecturer',
                    'company' => 'Example Company',
                    'supervisor' => 'Supervisor',
                    'eval_status' => 'Submitted',
                    'eval_mark' => 32.0,
                    'eval_weight' => 40.0,
                    'eval_percentage' => 0.8,
                    'eval_submitted' => new \DateTimeImmutable('2026-11-10'),
                ],
                [
                    'matric' => 'A002',
                    'name' => 'Pending Student',
                    'course' => 'Computer Science',
                    'university' => 'Example University',
                    'lecturer' => 'Lecturer',
                    'company' => 'Example Company',
                    'supervisor' => 'Supervisor',
                    'eval_status' => 'Not submitted',
                    'eval_mark' => null,
                    'eval_weight' => null,
                    'eval_percentage' => null,
                    'eval_submitted' => null,
                ],
            ],
        ]);

        $this->assertSame('Student Marks', $sheet->title());
        $this->assertSame(
            ['Matric No', 'Student Name', 'Course', 'University', 'Lecturer', 'Company', 'Company Supervisor', 'Evaluation Status', 'Mark', 'Out Of', 'Mark %', 'Submitted On'],
            $sheet->headings()
        );
        $rows = $sheet->array();
        $this->assertSame(32.0, $rows[0][8]);
        $this->assertSame(40.0, $rows[0][9]);
        $this->assertSame(0.8, $rows[0][10]);
        $this->assertSame('Not submitted', $rows[1][7]);
        $this->assertNull($rows[1][8]);
        $this->assertNull($rows[1][11]);
    }
}
