# Using `HasFactory`, Model Factories, and Basic Testing

Short guide to help you learn and use model factories (`HasFactory`) for seeding and testing.

1) What `HasFactory` does
- The `HasFactory` trait adds a `factory()` helper to Eloquent models so you can call `Model::factory()` to create fake model instances for tests and seeders.
- Add it to a model:

  ```php
  use Illuminate\Database\Eloquent\Factories\HasFactory;

  class Logbook extends Model
  {
      use HasFactory;
      // ...
  }
  ```

2) Create a factory
- Generate a factory for `Logbook`:

  ```bash
  php artisan make:factory LogbookFactory --model=Logbook
  ```

- The generated file will be in `database/factories/LogbookFactory.php`.

3) Example factory definitions
- `database/factories/LogbookFactory.php` (example):

  ```php
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
              'title' => 'Week '.$week,
              'description' => $this->faker->paragraph(),
              'activity_date' => $this->faker->date(),
              'status' => 'Pending',
          ];
      }
  }
  ```

- `database/factories/AttachmentFactory.php` (example):

  ```php
  <?php

  namespace Database\Factories;

  use Illuminate\Database\Eloquent\Factories\Factory;
  use App\Models\Attachment;

  class AttachmentFactory extends Factory
  {
      protected $model = Attachment::class;

      public function definition()
      {
          return [
              'logbook_id' => null, // set when creating related records
              'file_name' => $this->faker->word().'.pdf',
              'file_path' => 'uploads/'.$this->faker->word().'.pdf',
              'upload_date' => $this->faker->date(),
          ];
      }
  }
  ```

4) Using factories in seeders
- Example inside a seeder or tinker:

  ```php
  // create one logbook
 php

  // create 5 logbooks
  Logbook::factory()->count(5)->create();

  // create a logbook with attachments
  $log = Logbook::factory()->create();
  Attachment::factory()->create(['logbook_id' => $log->logbook_id]);
  ```

5) Example test
- A minimal feature test using the factory: `tests/Feature/LogbookFactoryTest.php`.

  ```php
  <?php

  namespace Tests\Feature;

  use Tests\TestCase;
  use App\Models\Logbook;

  class LogbookFactoryTest extends TestCase
  {
      public function test_logbook_factory_creates_record()
      {
          $log = Logbook::factory()->create();
          $this->assertDatabaseHas('logbooks', ['logbook_id' => $log->logbook_id]);
      }
  }
  ```

6) Running tests and seeds
- Run tests:

  ```bash
  php artisan test
  ```

- Seed database manually in tinker or seeder:

  ```bash
  php artisan tinker
  // then in tinker
  \App\Models\Logbook::factory()->count(10)->create();
  ```

7) Learning suggestions
- Start by writing factories for models you use frequently.
- Use factories in seeders to create a reproducible dev dataset.
- Write small tests that use factories to verify database interactions and controller behavior.
- Inspect generated records with `php artisan tinker`.

8) Notes
- Factories are for development and testing; they don't affect production code path.
- When your models use custom primary key names (like `logbook_id`) you can still use factories; just reference the correct column when asserting.

---
This file is a quick reference; if you want, I can also create the example factory files and the sample test in the project.
