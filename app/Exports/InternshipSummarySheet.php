<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * First sheet of the dashboard workbook.
 *
 *   A:B  key figures            D:E  students by status
 *   G:H  pending reviews (top supervisors)      J:K  weeks sent per week number
 * Charts sit underneath and read straight from those tables, so they stay editable in Excel.
 */
class InternshipSummarySheet implements FromArray, WithTitle, WithCharts, WithStyles, WithColumnWidths
{
    protected const SHEET = 'Summary';

    protected array $grid = [];

    protected array $percentCells = [];

    protected array $blocks = [];

    public function __construct(protected array $report)
    {
        $this->build();
    }

    protected function put(int $row, int $col, mixed $value): void
    {
        $this->grid[$row][$col] = $value;
    }

    protected function build(): void
    {
        $this->put(1, 1, 'Internship Programme Dashboard');
        $this->put(2, 1, 'Generated '.$this->report['generated_at']->format('d M Y, H:i').'  |  '.$this->report['filters_label']);

        // A:B key figures
        $this->put(4, 1, 'Key figure');
        $this->put(4, 2, 'Value');
        $row = 5;

        foreach ($this->report['kpis'] as $kpi) {
            $this->put($row, 1, $kpi['label']);
            $this->put($row, 2, $kpi['value']);

            if ($kpi['percent'] && $kpi['value'] !== null) {
                $this->percentCells[] = 'B'.$row;
            }

            $row++;
        }

        // D:E students by status
        $this->put(4, 4, 'Status');
        $this->put(4, 5, 'Students');
        $row = 5;

        foreach ($this->report['status_counts'] as $status => $count) {
            $this->put($row, 4, $status);
            $this->put($row, 5, $count);
            $row++;
        }

        $this->blocks['status'] = [5, $row - 1];

        // G:H top supervisors by entries waiting for review
        $this->put(4, 7, 'Supervisor');
        $this->put(4, 8, 'Entries waiting review');
        $row = 5;

        foreach ($this->report['top_pending'] as $item) {
            $this->put($row, 7, $item['name']);
            $this->put($row, 8, $item['pending']);
            $row++;
        }

        $this->blocks['pending'] = [5, $row - 1];

        if ($row === 5) {
            $this->put(5, 7, 'No entries waiting');
        }

        // J:K weeks sent per week number
        $this->put(4, 10, 'Week');
        $this->put(4, 11, 'Students who sent it');
        $row = 5;

        foreach ($this->report['weekly_sent'] as $week => $count) {
            $this->put($row, 10, 'Week '.$week);
            $this->put($row, 11, $count);
            $row++;
        }

        $this->blocks['weeks'] = [5, $row - 1];
    }

    public function title(): string
    {
        return self::SHEET;
    }

    public function array(): array
    {
        $maxRow = $this->grid ? max(array_keys($this->grid)) : 1;
        $rows = [];

        for ($r = 1; $r <= $maxRow; $r++) {
            $line = [];

            for ($c = 1; $c <= 11; $c++) {
                $line[] = $this->grid[$r][$c] ?? null;
            }

            $rows[] = $line;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 42, 'B' => 14, 'C' => 3, 'D' => 18, 'E' => 11, 'F' => 3, 'G' => 28, 'H' => 22, 'I' => 3, 'J' => 12, 'K' => 20];
    }

    public function styles(Worksheet $sheet): array
    {
        foreach ($this->percentCells as $cell) {
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0.0%');
        }

        $sheet->getStyle('B5:B30')->getAlignment()->setHorizontal('right');

        $header = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
        ];

        foreach (['A4:B4', 'D4:E4', 'G4:H4', 'J4:K4'] as $range) {
            $sheet->getStyle($range)->applyFromArray($header);
        }

        return [
            1 => ['font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1E3A8A']]],
            2 => ['font' => ['italic' => true, 'color' => ['argb' => 'FF595959']]],
        ];
    }

    protected function ref(string $col, int $first, int $last): string
    {
        return "'".self::SHEET."'!\$".$col.'$'.$first.':$'.$col.'$'.$last;
    }

    protected function series(string $type, ?string $grouping, string $labelCol, string $valueCol, array $block, ?string $nameCell): DataSeries
    {
        [$first, $last] = $block;
        $count = $last - $first + 1;

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $this->ref($labelCol, $first, $last), null, $count)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $this->ref($valueCol, $first, $last), null, $count)];
        $names = $nameCell
            ? [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'".self::SHEET."'!".$nameCell, null, 1)]
            : [];

        return new DataSeries($type, $grouping, range(0, count($values) - 1), $names, $categories, $values);
    }

    public function charts(): array
    {
        $charts = [];

        // 1. Students by status (pie)
        $pie = $this->series(DataSeries::TYPE_PIECHART, null, 'D', 'E', $this->blocks['status'], null);
        $statusChart = new Chart(
            'statusChart',
            new Title('Students by status'),
            new Legend(Legend::POSITION_RIGHT, null, false),
            new PlotArea(null, [$pie])
        );
        $statusChart->setTopLeftPosition('A21');
        $statusChart->setBottomRightPosition('E38');
        $charts[] = $statusChart;

        // 2. Entries waiting for review per supervisor (horizontal bars)
        if (! empty($this->report['top_pending'])) {
            $bar = $this->series(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, 'G', 'H', $this->blocks['pending'], 'H4');
            $bar->setPlotDirection(DataSeries::DIRECTION_BAR);
            $pendingChart = new Chart(
                'pendingChart',
                new Title('Entries waiting for review (top supervisors)'),
                null,
                new PlotArea(null, [$bar])
            );
            $pendingChart->setTopLeftPosition('G21');
            $pendingChart->setBottomRightPosition('K38');
            $charts[] = $pendingChart;
        }

        // 3. Weeks sent per week number (columns)
        $column = $this->series(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, 'J', 'K', $this->blocks['weeks'], 'K4');
        $column->setPlotDirection(DataSeries::DIRECTION_COL);
        $weeksChart = new Chart(
            'weeksChart',
            new Title('Logbook weeks sent, by week number'),
            null,
            new PlotArea(null, [$column])
        );
        $weeksChart->setTopLeftPosition('A40');
        $weeksChart->setBottomRightPosition('K58');
        $charts[] = $weeksChart;

        return $charts;
    }
}
