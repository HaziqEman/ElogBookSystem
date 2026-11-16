<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function reminders(Request $request)
    {
        $expected = (string) config('reminders.cron_token');
        $given = (string) $request->header('X-Cron-Token');

        // 404 on purpose, so the address does not reveal that it exists.
        abort_unless($expected !== '' && hash_equals($expected, $given), 404);

        Artisan::call('reminders:send', $request->boolean('force') ? ['--force' => true] : []);

        return response()->json([
            'ok' => true,
            'output' => trim(Artisan::output()),
        ]);
    }
}