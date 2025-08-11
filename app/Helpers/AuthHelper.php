<?php

namespace App\Helpers;

use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class AuthHelper
{
    /**
     * Get the current authenticated user (faculty/staff or student)
     */
    public static function getCurrentUser()
    {
        // Check if faculty/staff is authenticated
        if (Auth::check()) {
            return Auth::user();
        }

        // Check if student is authenticated via session
        if (session('is_student')) {
            return Student::find(session('student_id'));
        }

        return null;
    }

    /**
     * Get the current user's primary role
     */
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

    /**
     * Get all roles for the current user
     */
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

    /**
     * Check if current user has a specific role
     */
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

    /**
     * Check if current user has any of the specified roles
     */
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

    /**
     * Check if current user is a student
     */
    public static function isStudent()
    {
        return session('is_student') === true;
    }

    /**
     * Check if current user is faculty/staff
     */
    public static function isFaculty()
    {
        return Auth::check() && !self::isStudent();
    }
}
