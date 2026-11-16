<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentReminderController extends Controller
{
    public function index()
    {
        $student = Auth::guard('student')->user();
        $now = Carbon::now(Attendance::TIMEZONE);

        try {
            $items = (new ReminderService($student, $now))->items();
        } catch (\Throwable $e) {
            Log::error('Reminders failed: '.$e->getMessage());
            $items = [];
        }

        return response()
            ->json(['today' => $now->toDateString(), 'items' => $items])
            ->header('Cache-Control', 'no-store');
    }
}