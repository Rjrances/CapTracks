<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class CheckRole
{
    public function handle(Request $request, Closure $next, $roles)
    {
        if (Auth::check() && Auth::user()->hasAnyRole($roles)) {
            return $next($request);
        }
        abort(403, 'Unauthorized.');
    }
}
