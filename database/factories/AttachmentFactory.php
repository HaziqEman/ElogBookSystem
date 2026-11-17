<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition()
    {
        return [
            'logbook_id' => null,
            'file_name' => $this->faker->word().'.pdf',
            'file_path' => 'uploads/'.$this->faker->word().'.pdf',
            'upload_date' => $this->faker->date(),
        ];
    }
}
