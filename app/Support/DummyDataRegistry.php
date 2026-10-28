<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DummyDataRegistry
{
    public const TABLE = 'dummy_data_records';

    private array $created = [];

    private array $existing = [];

    public function counts(): array
    {
        return ['created' => $this->created, 'existing' => $this->existing];
    }

    /**
     * @template TModel of Model
     * @param class-string<TModel> $modelClass
     * @return TModel
     *
     * Create a fixture row once and record its exact primary key for safe cleanup.
     * Existing unregistered rows at a fixture identity are treated as collisions,
     * never silently adopted or overwritten.
     */
    public function create(string $key, string $modelClass, array $identity, array $attributes): Model
    {
        $record = DB::table(self::TABLE)->where('fixture_key', $key)->first();

        if ($record) {
            $existing = $modelClass::query()->find($record->record_id);

            if ($existing) {
                $this->existing[$modelClass] = ($this->existing[$modelClass] ?? 0) + 1;

                return $existing;
            }
        } elseif ($modelClass::query()->where($identity)->exists()) {
            throw new RuntimeException("Refusing to claim an unregistered existing row for fixture [{$key}].");
        }

        $model = new $modelClass;
        $model->fill($attributes);
        $model->save();

        $now = now();
        DB::table(self::TABLE)->updateOrInsert(
            ['fixture_key' => $key],
            [
                'record_type' => $modelClass,
                'record_id' => (string) $model->getKey(),
                'created_at' => $record?->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        $this->created[$modelClass] = ($this->created[$modelClass] ?? 0) + 1;

        return $model;
    }
}