<?php
namespace App\Helpers;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
class AuthHelper
{
    public static function getCurrentUser()
    {
        if (Auth::guard('student')->check()) {
            return Auth::guard('student')->user();
        }
        if (Auth::check()) {
            return Auth::user();
        }
        return null;
    }
    public static function getCurrentUserRole()
    {
        if (Auth::guard('student')->check()) {
            return 'student';
        }
        if (Auth::check()) {
            return Auth::user()->primary_role;
        }
        return null;
    }
    public static function getCurrentUserRoles()
    {
        if (Auth::guard('student')->check()) {
            return ['student'];
        }
        if (Auth::check()) {
            return Auth::user()->roles->pluck('name')->toArray();
        }
        return [];
    }
    public static function hasRole($role)
    {
        if (Auth::guard('student')->check()) {
            return $role === 'student';
        }
        if (Auth::check()) {
            return Auth::user()->hasRole($role);
        }
        return false;
    }
    public static function hasAnyRole($roles)
    {
        if (Auth::guard('student')->check()) {
            if (is_string($roles)) {
                $roles = explode(',', $roles);
            }
            return in_array('student', $roles);
        }
        if (Auth::check()) {
            return Auth::user()->hasAnyRole($roles);
        }
        return false;
    }
    public static function isStudent()
    {
        return Auth::guard('student')->check();
    }
    public static function isFaculty()
    {
        return Auth::check() && !self::isStudent();
    }
}
