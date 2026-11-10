<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupervisorRubricImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private ?array $rubric = null;

    public function collection(Collection $rows): void
    {
        $rows = $rows->map(fn ($row) => $row->map(fn ($value) => is_string($value) ? trim($value) : $value)->all())
            ->filter(fn ($row) => collect($row)->contains(fn ($value) => $value !== null && $value !== ''))
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'The spreadsheet has no rubric rows.',
            ]);
        }

        $first = $rows->first();
        $settings = [
            'title' => trim((string) ($first['evaluation_title'] ?? '')),
            'weight' => $first['evaluation_weight'] ?? null,
            'allow_edit' => $this->booleanValue($first['allow_edit'] ?? null),
            'scale_hint' => trim((string) ($first['scale_hint'] ?? '')),
        ];
        $errors = [];

        $settingsValidator = Validator::make($settings, [
            'title' => 'required|string|max:255',
            'weight' => 'required|numeric|between:1,100',
            'allow_edit' => 'required|boolean',
            'scale_hint' => 'required|string|max:500',
        ]);

        if ($settingsValidator->fails()) {
            $errors = array_merge($errors, $settingsValidator->errors()->all());
        }

        $sections = [];
        $seenCriteria = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $rowSettings = [
                'title' => trim((string) ($row['evaluation_title'] ?? '')),
                'weight' => $row['evaluation_weight'] ?? null,
                'allow_edit' => $this->booleanValue($row['allow_edit'] ?? null),
                'scale_hint' => trim((string) ($row['scale_hint'] ?? '')),
            ];

            if ($rowSettings !== $settings) {
                $errors[] = "Row {$line}: evaluation settings must be the same on every row.";
            }

            $sectionKey = trim((string) ($row['section_key'] ?? ''));
            $sectionTitle = trim((string) ($row['section_title'] ?? ''));
            $criterionKey = trim((string) ($row['criterion_key'] ?? ''));
            $criterionLabel = trim((string) ($row['criterion_label'] ?? ''));
            $max = $row['max_marks'] ?? null;
            $help = trim((string) ($row['help_text'] ?? ''));

            $rowValidator = Validator::make([
                'section_key' => $sectionKey,
                'section_title' => $sectionTitle,
                'criterion_key' => $criterionKey,
                'criterion_label' => $criterionLabel,
                'max_marks' => $max,
                'help_text' => $help,
            ], [
                'section_key' => ['required', 'regex:/^[a-z][a-z0-9_]{0,49}$/'],
                'section_title' => 'required|string|max:255',
                'criterion_key' => ['required', 'regex:/^[a-z][a-z0-9_]{0,49}$/'],
                'criterion_label' => 'required|string|max:255',
                'max_marks' => 'required|integer|between:1,20',
                'help_text' => 'nullable|string|max:1000',
            ]);

            foreach ($rowValidator->errors()->all() as $error) {
                $errors[] = "Row {$line}: {$error}";
            }

            if ($rowValidator->fails()) {
                continue;
            }

            if (isset($seenCriteria[$criterionKey])) {
                $errors[] = "Row {$line}: criterion key \"{$criterionKey}\" is duplicated.";

                continue;
            }
            $seenCriteria[$criterionKey] = true;

            if (isset($sections[$sectionKey]) && $sections[$sectionKey]['title'] !== $sectionTitle) {
                $errors[] = "Row {$line}: section \"{$sectionKey}\" has inconsistent titles.";

                continue;
            }

            $sections[$sectionKey] ??= ['title' => $sectionTitle, 'criteria' => []];
            $sections[$sectionKey]['criteria'][$criterionKey] = [
                'label' => $criterionLabel,
                'max' => (int) $max,
                'help' => $help,
            ];
        }

        if (! $seenCriteria) {
            $errors[] = 'Include at least one valid criterion.';
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        $this->rubric = [
            'title' => $settings['title'],
            'weight' => (float) $settings['weight'],
            'allow_edit' => $settings['allow_edit'],
            'scale_hint' => $settings['scale_hint'],
            'sections' => $sections,
        ];
    }

    public function rubric(): ?array
    {
        return $this->rubric;
    }

    private function booleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'yes', 'true' => true,
            '0', 'no', 'false' => false,
            default => null,
        };
    }
}
