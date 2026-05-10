<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentPasswordController extends Controller
{
    public function showChangePasswordForm()
    {
        $student = Auth::guard('student')->user();

        if (! $student->must_change_password) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Your password is already up to date.');
        }

        return view('student.change-password');
    }

    public function updatePassword(Request $request)
    {
        $student = Auth::guard('student')->user();

        if (! $student->must_change_password) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Your password is already up to date.');
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ], [
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least 8 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password_confirmation.required' => 'Please confirm your new password.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $student->update([
            'password' => $request->new_password,
            'must_change_password' => false,
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Password set successfully. You can now access all features.');
    }
}
