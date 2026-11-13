<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The coordinator's Excel dashboard: one Summary sheet with charts,
 * followed by the detail sheets.
 */
class InternshipDashboardExport implements WithMultipleSheets
{
    public function __construct(protected array $report)
    {
    }

    public function sheets(): array
    {
        return [
            new InternshipSummarySheet($this->report),
            InternshipSheets::progress($this->report),
            InternshipSheets::marks($this->report),
            InternshipSheets::weekly($this->report),
            InternshipSheets::attention($this->report),
            InternshipSheets::lecturers($this->report),
            InternshipSheets::supervisors($this->report),
            InternshipSheets::companies($this->report),
        ];
    }
}
