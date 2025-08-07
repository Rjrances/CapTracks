<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Laravel Auth (faculty/staff)
        if (auth()->check()) {
            return $next($request);
        }

        // Check if student is authenticated via session
        if (session('is_student') && session('student_id')) {
            return $next($request);
        }

        // Not authenticated, redirect to login
        return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
    }
}
