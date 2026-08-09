<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Logbook;

class LogbookFactory extends Factory
{
    protected $model = Logbook::class;

    public function definition()
    {
        $week = $this->faker->numberBetween(1,12);
        return [
            'student_id' => 1,
            'week_no' => $week,
            'description' => $this->faker->paragraph(),
            'activity_date' => $this->faker->date(),
            'status' => 'Pending',
        ];
    }
}
