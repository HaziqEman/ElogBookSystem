<?php

namespace App\Services;

use App\Models\SupervisorEvaluation;
use App\Models\SupervisorRubric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupervisorRubricService
{
    public function current(): array
    {
        if (Schema::hasTable('supervisor_rubrics')) {
            $record = SupervisorRubric::query()->where('is_active', true)->latest('id')->first();

            if ($record && is_array($record->rubric)) {
                return $record->rubric;
            }
        }

        return config('supervisor_rubric');
    }

    public function activate(array $rubric): void
    {
        DB::transaction(function () use ($rubric) {
            $previous = $this->current();

            SupervisorEvaluation::query()
                ->whereNull('rubric_snapshot')
                ->update(['rubric_snapshot' => json_encode($previous, JSON_THROW_ON_ERROR)]);

            SupervisorRubric::query()->where('is_active', true)->update(['is_active' => false]);
            SupervisorRubric::create([
                'rubric' => $rubric,
                'is_active' => true,
            ]);
        });
    }
}
