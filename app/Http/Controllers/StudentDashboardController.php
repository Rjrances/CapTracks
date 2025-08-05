<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Auth::user()->student ?? null;
        $group = null;
        
        if ($student) {
            // Check if student is in a group
            $group = Group::whereHas('members', function($q) use ($student) {
                $q->where('group_members.student_id', $student->id);
            })->with(['adviser', 'members'])->first();
        }
        
        return view('dashboards.student', compact('group'));
    }
}
