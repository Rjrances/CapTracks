<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentPasswordController extends Controller
{
    /**
     * Show the password change form
     */
    public function showChangePasswordForm()
    {
        $student = Auth::guard('student')->user();
        
        if (!$student->must_change_password) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Your password is already up to date.');
        }
        
        // Check if this is a first-time user (no password set)
        $isFirstTime = is_null($student->password);
        
        return view('student.change-password', compact('isFirstTime'));
    }
    
    /**
     * Update the student's password
     */
    public function updatePassword(Request $request)
    {
        $student = Auth::guard('student')->user();
        
        // Check if this is a first-time user (no password set)
        $isFirstTime = is_null($student->password);
        
        // Set up validation rules based on whether it's first time or not
        $rules = [
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ];
        
        $messages = [
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least 8 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password_confirmation.required' => 'Please confirm your new password.',
        ];
        
        // Only require current password if it's not a first-time user
        if (!$isFirstTime) {
            $rules['current_password'] = 'required';
            $messages['current_password.required'] = 'Current password is required.';
        }
        
        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Verify current password only if it's not a first-time user
        if (!$isFirstTime) {
            if (!Hash::check($request->current_password, $student->password)) {
                return redirect()->back()
                    ->withErrors(['current_password' => 'Current password is incorrect.'])
                    ->withInput();
            }
        }
        
        // Update password
        $student->update([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
        ]);
        
        $message = $isFirstTime 
            ? 'Password set successfully! You can now access all features.'
            : 'Password changed successfully! You can now access all features.';
        
        return redirect()->route('student.dashboard')
            ->with('success', $message);
    }
}
