<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugSession
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Log safe session diagnostics after Laravel has saved the response session.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $cookieName = (string) config('session.cookie');
            $sessionId = $request->hasSession()
                ? $request->session()->getId()
                : null;
            $sessionConnection = config('session.connection');
            $row = $sessionId
                ? DB::connection($sessionConnection)
                    ->table(config('session.table', 'sessions'))
                    ->where('id', $sessionId)
                    ->first()
                : null;

            $payload = $row
                ? base64_decode((string) $row->payload, true)
                : false;
            $setCookie = collect($response->headers->getCookies())
                ->first(fn ($cookie) => $cookie->getName() === $cookieName);

            Log::channel('stderr')->info('SESSION_DEBUG', [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'is_secure_request' => $request->isSecure(),
                'driver' => config('session.driver'),
                'cookie_name' => $cookieName,
                'config_secure' => config('session.secure'),
                'config_domain' => config('session.domain'),
                'config_path' => config('session.path'),
                'config_same_site' => config('session.same_site'),
                'session_connection' => $sessionConnection,
                'session_table' => config('session.table', 'sessions'),
                'request_has_cookie' => $request->cookies->has($cookieName),
                'session_id_exists' => is_string($sessionId) && $sessionId !== '',
                'session_hash' => $sessionId
                    ? substr(hash('sha256', $sessionId), 0, 8)
                    : null,
                'session_row_exists' => (bool) $row,
                'row_has_lecturer_key' => $payload !== false
                    ? str_contains($payload, 'login_lecturer_')
                    : null,
                'guard_student' => Auth::guard('student')->check(),
                'guard_lecturer' => Auth::guard('lecturer')->check(),
                'guard_admin' => Auth::guard('admin')->check(),
                'response_sets_cookie' => (bool) $setCookie,
                'resp_cookie_secure' => $setCookie?->isSecure(),
                'resp_cookie_samesite' => $setCookie?->getSameSite(),
                'resp_cookie_domain' => $setCookie?->getDomain(),
                'resp_cookie_path' => $setCookie?->getPath(),
            ]);
        } catch (\Throwable $exception) {
            // Do not log the exception message; it may include sensitive values.
            Log::channel('stderr')->info('SESSION_DEBUG_ERROR', [
                'exception_class' => $exception::class,
            ]);
        }
    }
}
