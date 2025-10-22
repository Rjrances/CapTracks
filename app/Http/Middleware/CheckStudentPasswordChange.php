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
        //student auth checker
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            
            //password change checker
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
