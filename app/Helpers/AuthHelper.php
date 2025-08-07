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
     * Get the current user's role
     */
    public static function getCurrentUserRole()
    {
        if (Auth::check()) {
            return Auth::user()->role;
        }

        if (session('is_student')) {
            return 'student';
        }

        return null;
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
