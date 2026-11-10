<?php

namespace Tests\Unit;

use App\Exports\SupervisorRubricTemplateExport;
use App\Imports\SupervisorRubricImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SupervisorRubricImportTest extends TestCase
{
    public function test_it_builds_a_rubric_from_rows(): void
    {
        $import = new SupervisorRubricImport();
        $import->collection(new Collection([
            $this->row('work', 'Work performance', 'quality', 'Quality of work', 5),
            $this->row('skills', 'Skills', 'learning', 'Ability to learn', 4),
        ]));

        $this->assertSame('Supervisor Evaluation', $import->rubric()['title']);
        $this->assertSame(40.0, $import->rubric()['weight']);
        $this->assertSame(5, $import->rubric()['sections']['work']['criteria']['quality']['max']);
        $this->assertSame('Ability to learn', $import->rubric()['sections']['skills']['criteria']['learning']['label']);
    }

    public function test_it_rejects_duplicate_criterion_keys(): void
    {
        $import = new SupervisorRubricImport();

        try {
            $import->collection(new Collection([
                $this->row('work', 'Work performance', 'quality', 'Quality of work', 5),
                $this->row('skills', 'Skills', 'quality', 'Different criterion', 4),
            ]));
            $this->fail('Expected duplicate criterion validation to fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('duplicated', implode(' ', $e->errors()['file']));
        }
    }

    public function test_download_template_can_be_imported_without_changing_rubric_values(): void
    {
        $rubric = [
            'title' => 'Supervisor Evaluation',
            'weight' => 40.0,
            'allow_edit' => true,
            'scale_hint' => '1 = Poor, 5 = Excellent',
            'sections' => [
                'work' => [
                    'title' => 'Work performance',
                    'criteria' => [
                        'quality' => ['label' => 'Quality of work', 'max' => 5, 'help' => 'Accurate work.'],
                    ],
                ],
            ],
        ];
        $contents = Excel::raw(
            new SupervisorRubricTemplateExport($rubric),
            \Maatwebsite\Excel\Excel::XLSX
        );
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('rubric.xlsx', $contents);
        $import = new SupervisorRubricImport();

        Excel::import($import, $file);

        $this->assertSame($rubric, $import->rubric());
    }

    private function row(string $sectionKey, string $sectionTitle, string $criterionKey, string $label, int $max): Collection
    {
        return new Collection([
            'evaluation_title' => 'Supervisor Evaluation',
            'evaluation_weight' => 40,
            'allow_edit' => 'yes',
            'scale_hint' => '1 = Poor, 5 = Excellent',
            'section_key' => $sectionKey,
            'section_title' => $sectionTitle,
            'criterion_key' => $criterionKey,
            'criterion_label' => $label,
            'max_marks' => $max,
            'help_text' => 'Sample criterion help.',
        ]);
    }
}
