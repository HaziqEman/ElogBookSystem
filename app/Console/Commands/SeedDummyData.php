<?php

namespace App\Console\Commands;

use Database\Seeders\DummyDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeedDummyData extends Command
{
    protected $signature = 'dummy:seed
        {--admins=1 : Number of dummy admin accounts}
        {--lecturers=2 : Number of dummy lecturers}
        {--supervisors=2 : Number of dummy company supervisors}
        {--students=8 : Number of dummy students}
        {--weeks=12 : Internship duration in weeks}
        {--expect-host= : Required exact PostgreSQL host confirmation before writing}
        {--expect-database= : Required exact database name confirmation before writing}
        {--yes : Confirm writing fixture data to this non-production database}';

    protected $description = 'Create isolated, repeatable dummy eLogBook data';

    public function handle(): int
    {
        if (! config('dummydata.enabled') || app()->environment('production')) {
            $this->error('Seeding is disabled. Set DUMMY_DATA_SEEDING_ENABLED=true in a non-production environment.');

            return self::FAILURE;
        }

        $connectionName = (string) config('database.default');
        $connection = DB::connection($connectionName);
        $connectionConfig = $connection->getConfig();
        $driver = (string) ($connectionConfig['driver'] ?? $connectionName);
        $database = (string) ($connectionConfig['database'] ?? '');
        $host = (string) ($connectionConfig['host'] ?? '');

        $this->components->info('Dummy data seeding started.');
        $targetDescription = 'Connection: '.$driver;
        if ($driver === 'pgsql' && $host !== '') {
            $targetDescription .= ' / host '.$host.':'.($connectionConfig['port'] ?? '5432');
        }
        if ($database !== '') {
            $targetDescription .= ' / database '.$database;
        }
        $this->line($targetDescription);

        if ($driver === 'pgsql') {
            if (! $this->option('expect-host') || ! $this->option('expect-database')) {
                $this->error('No records were written. For PostgreSQL, pass --expect-host and --expect-database after confirming this exact endpoint and database are your intended development target in Neon.');

                return self::FAILURE;
            }

            if (! hash_equals(strtolower($host), strtolower((string) $this->option('expect-host')))
                || ! hash_equals(strtolower($database), strtolower((string) $this->option('expect-database')))) {
                $this->error('No records were written. The configured PostgreSQL host/database does not match the expected target confirmation.');

                return self::FAILURE;
            }
        }

        if (! $this->option('yes') && ! $this->confirm('Create dummy accounts and linked records in the configured database?')) {
            $this->warn('Cancelled; no data was written.');

            return self::SUCCESS;
        }

        config()->set('dummydata.counts', [
            'admins' => (int) $this->option('admins'),
            'lecturers' => (int) $this->option('lecturers'),
            'supervisors' => (int) $this->option('supervisors'),
            'students' => (int) $this->option('students'),
            'weeks' => (int) $this->option('weeks'),
        ]);

        try {
            $target = match ($driver) {
                'pgsql' => DB::connection($connectionName)->selectOne('select current_database() as database, current_schema() as schema'),
                'sqlite' => DB::connection($connectionName)->selectOne("select 'sqlite' as database, 'main' as schema"),
                'mysql', 'mariadb' => DB::connection($connectionName)->selectOne('select database() as database, database() as schema'),
                default => throw new \RuntimeException("Unsupported database driver for safe dummy seeding: {$driver}."),
            };

            if ($driver === 'pgsql' && ! hash_equals(strtolower($database), strtolower((string) $target->database))) {
                $this->error('No records were written. PostgreSQL connected to a database different from the confirmed target.');

                return self::FAILURE;
            }

            $this->line('Verified target: '.$driver.' / '.(string) $target->database.' / schema '.(string) $target->schema);
            $this->line(sprintf(
                'Requested accounts and duration: admins=%d, lecturers=%d, supervisors=%d, students=%d, weeks=%d.',
                (int) $this->option('admins'),
                (int) $this->option('lecturers'),
                (int) $this->option('supervisors'),
                (int) $this->option('students'),
                (int) $this->option('weeks')
            ));

            $seeder = app(DummyDataSeeder::class);
            $seeder->setContainer(app())->setCommand($this);
            $seeder->run();

            $this->components->success('Dummy data seeding completed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $state = $exception instanceof \Illuminate\Database\QueryException
                ? ($exception->errorInfo[0] ?? null)
                : null;
            $exceptionMessage = $exception->getMessage();

            if (($state === '08006' || str_contains($exceptionMessage, 'SQLSTATE[08006]'))
                && str_contains($exceptionMessage, 'Endpoint ID is not specified')) {
                $this->error('Database connection failed: this PHP/libpq build does not provide Neon SNI. Use Laragon PHP 8.3.35 or newer with libpq 14+, then retry. No fixture transaction was committed.');
            } elseif ($exception instanceof \Illuminate\Database\QueryException) {
                $this->error('Database operation failed'.($state ? " (SQLSTATE {$state})" : '').'. No fixture transaction was committed; inspect the Laravel log locally after redacting sensitive values.');
            } elseif ($exception instanceof \RuntimeException) {
                $this->error('Dummy data seeding failed ('.class_basename($exception).'): '.$exception->getMessage());
                $this->warn('The seeder transaction was rolled back; no partial fixture set was committed.');
            } else {
                $this->error('Dummy data seeding failed unexpectedly ('.class_basename($exception).'). No fixture transaction was committed; inspect the Laravel log locally after redacting sensitive values.');
            }

            return self::FAILURE;
        }
    }
}