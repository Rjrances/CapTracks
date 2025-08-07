<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class RoleController extends Controller
{
    public function index()
    {
        // Get all faculty/staff users (excluding chairperson)
        $users = User::where('role', '!=', 'chairperson')->get();
        return view('chairperson.manage-roles', compact('users'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:coordinator,adviser,panelist',
        ]);

        $user->role = $request->role;
        $user->save();

        return redirect()->back()->with('success', 'User role updated successfully.');
    }
}
