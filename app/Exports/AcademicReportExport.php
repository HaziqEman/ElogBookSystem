<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AcademicReportExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        $students = Student::with(['lecturer', 'logbooks'])->get();

        $rows = collect();

        foreach ($students as $student) {
            foreach ($student->logbooks as $logbook) {
                $rows->push([
                    'student_name' => $student->name,
                    'student_email' => $student->email,
                    'matric_no' => $student->matric_no,
                    'course' => $student->course,
                    'lecturer_name' => optional($student->lecturer)->name,
                    'lecturer_email' => optional($student->lecturer)->email,
                    'internship_company' => $this->resolveCompany($student, $logbook),
                    'week_number' => $logbook->week_no,
                    'approval_status' => $logbook->status,
                    'submission_date' => $logbook->activity_date,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Student Email',
            'Matric No',
            'Course',
            'Lecturer Name',
            'Lecturer Email',
            'Internship Company',
            'Week Number',
            'Approval Status',
            'Submission Date',
        ];
    }

    protected function resolveCompany(Student $student, $logbook): string
    {
        if (! empty($student->company_name)) {
            return $student->company_name;
        }

        if (! empty($student->company)) {
            return $student->company;
        }

        return 'Not provided';
    }
}
