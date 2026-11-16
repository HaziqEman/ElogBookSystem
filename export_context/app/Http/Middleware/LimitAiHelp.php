<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LimitAiHelp
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = Auth::guard('student')->user();

        // Do nothing until the usage table exists.
        if (! $student || ! Schema::hasTable('ai_help_usages')) {
            return $next($request);
        }

        $limit = max(1, (int) config('aihelp.daily_limit', 10));
        $today = now(Attendance::TIMEZONE)->toDateString();
        $now = now();

        DB::table('ai_help_usages')->insertOrIgnore([
            'student_id' => $student->student_id,
            'used_on' => $today,
            'used_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $usage = fn () => DB::table('ai_help_usages')
            ->where('student_id', $student->student_id)
            ->where('used_on', $today);

        // The increment only succeeds while the student is under the limit, so two quick clicks cannot both pass.
        $claimed = $usage()->where('used_count', '<', $limit)->increment('used_count', 1, ['updated_at' => $now]);

        if ($claimed === 0) {
            return response()->json([
                'message' => "You have used all {$limit} AI drafts for today. The limit resets at midnight (Malaysia time).",
            ], 429)->header('X-AI-Remaining', '0');
        }

        $used = (int) $usage()->value('used_count');

        $response = $next($request);
        $response->headers->set('X-AI-Remaining', (string) max($limit - $used, 0));

        return $response;
    }
}