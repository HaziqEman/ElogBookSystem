<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupervisorRubricTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private array $rubric)
    {
    }

    public function headings(): array
    {
        return [
            'evaluation_title',
            'evaluation_weight',
            'allow_edit',
            'scale_hint',
            'section_key',
            'section_title',
            'criterion_key',
            'criterion_label',
            'max_marks',
            'help_text',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->rubric['sections'] as $sectionKey => $section) {
            foreach ($section['criteria'] as $criterionKey => $criterion) {
                $rows[] = [
                    $this->rubric['title'],
                    $this->rubric['weight'],
                    $this->rubric['allow_edit'] ? 'yes' : 'no',
                    $this->rubric['scale_hint'],
                    $sectionKey,
                    $section['title'],
                    $criterionKey,
                    $criterion['label'],
                    $criterion['max'] ?? 5,
                    $criterion['help'] ?? '',
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
