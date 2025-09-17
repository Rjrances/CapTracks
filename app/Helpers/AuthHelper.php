<?php
namespace App\Helpers;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
class AuthHelper
{
    public static function getCurrentUser()
    {
        if (Auth::check()) {
            return Auth::user();
        }
        if (session('is_student')) {
            return Student::find(session('student_id'));
        }
        return null;
    }
    public static function getCurrentUserRole()
    {
        if (Auth::check()) {
            return Auth::user()->primary_role;
        }
        if (session('is_student')) {
            return 'student';
        }
        return null;
    }
    public static function getCurrentUserRoles()
    {
        if (Auth::check()) {
            return Auth::user()->roles->pluck('name')->toArray();
        }
        if (session('is_student')) {
            return ['student'];
        }
        return [];
    }
    public static function hasRole($role)
    {
        if (Auth::check()) {
            return Auth::user()->hasRole($role);
        }
        if (session('is_student')) {
            return $role === 'student';
        }
        return false;
    }
    public static function hasAnyRole($roles)
    {
        if (Auth::check()) {
            return Auth::user()->hasAnyRole($roles);
        }
        if (session('is_student')) {
            if (is_string($roles)) {
                $roles = explode(',', $roles);
            }
            return in_array('student', $roles);
        }
        return false;
    }
    public static function isStudent()
    {
        return session('is_student') === true;
    }
    public static function isFaculty()
    {
        return Auth::check() && !self::isStudent();
    }
}
