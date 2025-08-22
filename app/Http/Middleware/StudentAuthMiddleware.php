<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Symfony\Component\HttpFoundation\Response;

class StudentAuthMiddleware extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        // Allow if user is authenticated via Laravel Auth
        if (auth()->check()) {
            return $next($request);
        }
        
        // Allow if user is authenticated via session (student)
        if (session('is_student') && session('student_id')) {
            return $next($request);
        }
        
        // If neither, redirect to login
        return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
    }
}
