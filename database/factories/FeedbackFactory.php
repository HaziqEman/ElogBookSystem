<?php

namespace Database\Factories;

use App\Models\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition()
    {
        return [
            'logbook_id' => null,
            'lecturer_id' => 1,
            'comment' => $this->faker->sentence(),
            'feedback_date' => $this->faker->date(),
        ];
    }
}
