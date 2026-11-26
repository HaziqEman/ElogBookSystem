<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function reminders(Request $request): JsonResponse
    {
        $this->authorizeCronRequest($request);
        $this->allowCronRunToFinish();

        return $this->runCommands([
            ['reminders:send', $request->boolean('force') ? ['--force' => true] : []],
            ['weeks:send', ['--reminders-only' => true]],
        ]);
    }

    public function weeklyBatches(Request $request): JsonResponse
    {
        $this->authorizeCronRequest($request);
        $this->allowCronRunToFinish();

        return $this->runCommands([
            ['weeks:send', []],
        ]);
    }

    private function authorizeCronRequest(Request $request): void
    {
        $expected = (string) config('reminders.cron_token');
        $given = (string) $request->header('X-Cron-Token');

        // 404 on purpose, so the address does not reveal that it exists.
        abort_unless($expected !== '' && hash_equals($expected, $given), 404);
    }

    private function allowCronRunToFinish(): void
    {
        ignore_user_abort(true);
        set_time_limit(300);
    }

    private function runCommands(array $commands): JsonResponse
    {
        $outputs = [];

        foreach ($commands as [$command, $arguments]) {
            $exitCode = Artisan::call($command, $arguments);
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                return response()->json([
                    'ok' => false,
                    'command' => $command,
                    'output' => $output,
                ], 500);
            }

            if ($output !== '') {
                $outputs[] = $output;
            }
        }

        return response()->json([
            'ok' => true,
            'output' => implode("\n", $outputs),
        ]);
    }
}
