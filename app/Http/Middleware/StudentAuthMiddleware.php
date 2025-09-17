<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Symfony\Component\HttpFoundation\Response;
class StudentAuthMiddleware extends Middleware
{
    public function handle($request, Closure $next, ...$guards): Response
    {
        if (auth()->check()) {
            return $next($request);
        }
        if (session('is_student') && session('student_id')) {
            return $next($request);
        }
        return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
    }
}
