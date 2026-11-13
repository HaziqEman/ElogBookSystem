<?php

namespace App\Exports;

use DateTimeInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One table sheet: header row, real Excel dates and percentages, filters,
 * a frozen header and colour rules. In CSV mode dates become Y-m-d text and
 * percentages become plain numbers (85.5), so the file opens cleanly anywhere.
 *
 * Options (all optional):
 *   formats => ['Heading name' => 'date' | 'percent']
 *   rules   => [['col' => 'Heading name', 'op' => 'equal|lt|gt|gte', 'value' => ..., 'fill' => 'FFC7CE', 'font' => '9C0006']]
 *   freeze  => 'C2'
 */
class ReportArraySheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, WithColumnWidths, WithStyles, WithEvents
{
    public function __construct(
        protected string $title,
        protected array $headings,
        protected array $rows,
        protected array $options = [],
        protected bool $csv = false
    ) {
        $this->rows = array_map('array_values', $this->rows);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        $types = $this->typesByIndex();

        return array_map(function (array $row) use ($types) {
            foreach ($row as $i => $value) {
                $row[$i] = $this->cast($value, $types[$i] ?? null);
            }

            return $row;
        }, $this->rows);
    }

    protected function cast(mixed $value, ?string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $this->csv ? $value->format('Y-m-d') : Date::dateTimeToExcel($value);
        }

        if ($type === 'percent' && is_numeric($value)) {
            return $this->csv ? round($value * 100, 1) : (float) $value;
        }

        return $value;
    }

    /** @return array<int, string> column index => 'date' | 'percent' */
    protected function typesByIndex(): array
    {
        $types = [];

        foreach ($this->options['formats'] ?? [] as $heading => $type) {
            $index = array_search($heading, $this->headings, true);

            if ($index !== false) {
                $types[$index] = $type;
            }
        }

        return $types;
    }

    protected function letter(string $heading): ?string
    {
        $index = array_search($heading, $this->headings, true);

        return $index === false ? null : Coordinate::stringFromColumnIndex($index + 1);
    }

    public function columnFormats(): array
    {
        if ($this->csv) {
            return [];
        }

        $formats = [];

        foreach ($this->typesByIndex() as $index => $type) {
            $formats[Coordinate::stringFromColumnIndex($index + 1)] = $type === 'date' ? 'dd/mm/yyyy' : '0%';
        }

        return $formats;
    }

    /** Width from the heading and the first rows, so long text does not stretch the sheet. */
    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->headings as $i => $heading) {
            $longest = mb_strlen((string) $heading);

            foreach (array_slice($this->rows, 0, 200) as $row) {
                $value = $row[$i] ?? null;

                if (is_scalar($value)) {
                    $longest = max($longest, mb_strlen((string) $value));
                }
            }

            $widths[Coordinate::stringFromColumnIndex($i + 1)] = max(10, min($longest + 3, 46));
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        if ($this->csv) {
            return [];
        }

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        if ($this->csv) {
            return [];
        }

        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, count($this->rows) + 1);
                $lastCol = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));

                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->setAutoFilter('A1:'.$lastCol.$lastRow);
                $sheet->freezePane($this->options['freeze'] ?? 'A2');

                foreach ($this->options['rules'] ?? [] as $rule) {
                    $this->addRule($sheet, $rule, $lastRow);
                }
            },
        ];
    }

    protected function addRule(Worksheet $sheet, array $rule, int $lastRow): void
    {
        $letter = $this->letter($rule['col']);

        if (! $letter) {
            return;
        }

        $conditional = new Conditional();
        $op = $rule['op'];

        if ($op === 'equal') {
            $conditional->setConditionType(Conditional::CONDITION_CELLIS);
            $conditional->setOperatorType(Conditional::OPERATOR_EQUAL);
            $conditional->addCondition('"'.$rule['value'].'"');
        } else {
            // Expression rules ignore empty cells, which a plain "less than" rule would colour.
            $symbol = ['lt' => '<', 'gt' => '>', 'gte' => '>='][$op] ?? '>';
            $conditional->setConditionType(Conditional::CONDITION_EXPRESSION);
            $conditional->addCondition('AND(ISNUMBER('.$letter.'2),'.$letter.'2'.$symbol.$rule['value'].')');
        }

        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $conditional->getStyle()->getFill()->getStartColor()->setARGB('FF'.$rule['fill']);
        $conditional->getStyle()->getFill()->getEndColor()->setARGB('FF'.$rule['fill']);
        $conditional->getStyle()->getFont()->getColor()->setARGB('FF'.$rule['font']);

        $range = $letter.'2:'.$letter.$lastRow;
        $existing = $sheet->getStyle($range)->getConditionalStyles();
        $existing[] = $conditional;
        $sheet->getStyle($range)->setConditionalStyles($existing);
    }
}
