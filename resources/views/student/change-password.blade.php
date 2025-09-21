@extends('layouts.student')

@section('title', 'Change Password')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 500px;">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="fas fa-key fa-3x text-warning"></i>
            </div>
            <h2 class="fw-bold mb-2">
                @if($isFirstTime)
                    Set Your Password
                @else
                    Change Your Password
                @endif
            </h2>
            <p class="text-muted">
                @if($isFirstTime)
                    Please set your password to continue using the system.
                @else
                    You must change your password before continuing to use the system.
                @endif
            </p>
        </div>

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('student.update-password') }}">
            @csrf
            
            @if(!$isFirstTime)
            <div class="mb-4">
                <label for="current_password" class="form-label fw-semibold">
                    <i class="fas fa-lock me-2"></i>Current Password
                </label>
                <input type="password" 
                       class="form-control @error('current_password') is-invalid @enderror" 
                       id="current_password" 
                       name="current_password" 
                       required 
                       autofocus>
                @error('current_password')
                    <div class="invalid-feedback">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    Enter your current password
                </div>
            </div>
            @endif

            <div class="mb-4">
                <label for="new_password" class="form-label fw-semibold">
                    <i class="fas fa-key me-2"></i>New Password
                </label>
                <input type="password" 
                       class="form-control @error('new_password') is-invalid @enderror" 
                       id="new_password" 
                       name="new_password" 
                       required 
                       minlength="8">
                @error('new_password')
                    <div class="invalid-feedback">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror
                <div class="form-text">
                    <i class="fas fa-shield-alt me-1"></i>
                    Password must be at least 8 characters long
                </div>
            </div>

            <div class="mb-4">
                <label for="new_password_confirmation" class="form-label fw-semibold">
                    <i class="fas fa-check-circle me-2"></i>Confirm New Password
                </label>
                <input type="password" 
                       class="form-control @error('new_password_confirmation') is-invalid @enderror" 
                       id="new_password_confirmation" 
                       name="new_password_confirmation" 
                       required 
                       minlength="8">
                @error('new_password_confirmation')
                    <div class="invalid-feedback">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>
                    @if($isFirstTime)
                        Set Password
                    @else
                        Change Password
                    @endif
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <div class="alert alert-light border">
                <i class="fas fa-lightbulb me-2 text-warning"></i>
                <strong>Security Tip:</strong> Choose a strong password that you haven't used before and keep it secure.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on appropriate field
    @if($isFirstTime)
        document.getElementById('new_password').focus();
    @else
        document.getElementById('current_password').focus();
    @endif
    
    // Password strength indicator (optional)
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');
    
    function validatePasswordMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
});
</script>
@endsection
