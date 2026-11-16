<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    public function headings(): array
    {
        return ['matric_no', 'name', 'email', 'password', 'phone_no', 'course', 'lecturer_email', 'supervisor_email', 'internship_start', 'internship_weeks', 'working_days'];
    }

    public function array(): array
    {
        return [
            ['2025110501', 'Ali Bin Abu', 'ali@student.edu.my', '', '0123456789', 'Computer Science', 'lecturer@uni.edu.my', 'supervisor@company.com', '2026-11-02', 14, 'Mon,Tue,Wed,Thu,Fri'],
        ];
    }

    /** Matric and phone are text so Excel does not strip leading zeros. */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'E' => NumberFormat::FORMAT_TEXT, 'I' => NumberFormat::FORMAT_TEXT];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
