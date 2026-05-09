<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckStudentPasswordChange
{
    
    public function handle(Request $request, Closure $next): Response
    {
        
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            
            
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
