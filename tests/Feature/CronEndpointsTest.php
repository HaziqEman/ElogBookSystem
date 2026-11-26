<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CronEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['reminders.cron_token' => 'test-cron-token']);
    }

    public function test_daily_endpoint_requires_the_cron_token(): void
    {
        $this->getJson('/internal/run-reminders')
            ->assertNotFound();
    }

    public function test_daily_endpoint_runs_both_daily_reminder_commands(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('reminders:send', [])
            ->ordered()
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->once()
            ->with('weeks:send', ['--reminders-only' => true])
            ->ordered()
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->twice()
            ->andReturn('Student reminders sent.', 'Review follow-ups sent.');

        $this->withHeader('X-Cron-Token', 'test-cron-token')
            ->getJson('/internal/run-reminders')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('output', "Student reminders sent.\nReview follow-ups sent.");
    }

    public function test_weekly_endpoint_runs_week_batch_command(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('weeks:send', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Sent 1 week batch.');

        $this->withHeader('X-Cron-Token', 'test-cron-token')
            ->getJson('/internal/run-weekly-batches')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('output', 'Sent 1 week batch.');
    }

    public function test_daily_endpoint_returns_failure_when_a_command_fails(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('reminders:send', [])
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Reminder command failed.');

        $this->withHeader('X-Cron-Token', 'test-cron-token')
            ->getJson('/internal/run-reminders')
            ->assertInternalServerError()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('command', 'reminders:send')
            ->assertJsonPath('output', 'Reminder command failed.');
    }
}
