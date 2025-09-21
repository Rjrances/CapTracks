<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckStudentPasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if student is authenticated using the student guard
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            
            // Check if student must change password and is not already on password change page
            if ($studentAccount->must_change_password && 
                !$request->routeIs('student.change-password') && 
                !$request->routeIs('student.update-password')) {
                
                return redirect()->route('student.change-password')
                    ->with('warning', 'You must change your password before continuing.');
            }
        }

        return $next($request);
    }
}
