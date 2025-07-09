<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanRegisterMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'chairperson') {
            return $next($request);
        }

        abort(403, 'Unauthorized action. Only chairperson can register users.');
    }
}
