<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupervisorPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $supervisor = Auth::guard('supervisor')->user();

        if ($supervisor && $supervisor->must_change_password) {
            return redirect('/supervisor/password');
        }

        return $next($request);
    }
}