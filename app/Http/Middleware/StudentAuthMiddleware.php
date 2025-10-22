<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        //check auth
        if (!Auth::guard('student')->check()) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        
        //get auth
        $student = Auth::guard('student')->user();
        if (!$student->isStudent()) {
            return redirect('/login')->withErrors(['auth' => 'Access denied. Student account required.']);
        }
        
        return $next($request);
    }
}
