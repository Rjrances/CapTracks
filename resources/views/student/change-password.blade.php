<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - CapTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 1rem;
        }
        .brand {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="card-wrapper">
    <div class="bg-white rounded-4 shadow p-5">

        {{-- Header --}}
        <div class="text-center mb-4">
            <div class="brand mb-2">
                <i class="fas fa-graduation-cap me-1 text-primary"></i>CapTrack
            </div>
            <div class="mb-3">
                <i class="fas fa-key fa-3x text-warning"></i>
            </div>
            <h5 class="fw-bold mb-1">Change Password</h5>
            <p class="text-muted small mb-0">
                @if($isFirstTime)
                    Please set your password to continue using the system.
                @else
                    You must change your password before continuing.
                @endif
            </p>
        </div>

        {{-- Alerts --}}
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show py-2" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show py-2" role="alert">
                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li class="small">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('student.update-password') }}">
            @csrf

            @if(!$isFirstTime)
            <div class="mb-3">
                <label for="current_password" class="form-label fw-semibold small">
                    <i class="fas fa-lock me-1"></i>Current Password
                </label>
                <input type="password"
                       class="form-control @error('current_password') is-invalid @enderror"
                       id="current_password"
                       name="current_password"
                       required autofocus>
                @error('current_password')
                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
            @endif

            <div class="mb-3">
                <label for="new_password" class="form-label fw-semibold small">
                    <i class="fas fa-key me-1"></i>New Password
                </label>
                <input type="password"
                       class="form-control @error('new_password') is-invalid @enderror"
                       id="new_password"
                       name="new_password"
                       required minlength="8"
                       @if($isFirstTime) autofocus @endif>
                @error('new_password')
                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
                <div class="form-text small"><i class="fas fa-shield-alt me-1"></i>Password must be at least 8 characters long</div>
            </div>

            <div class="mb-4">
                <label for="new_password_confirmation" class="form-label fw-semibold small">
                    <i class="fas fa-check-circle me-1"></i>Confirm New Password
                </label>
                <input type="password"
                       class="form-control @error('new_password_confirmation') is-invalid @enderror"
                       id="new_password_confirmation"
                       name="new_password_confirmation"
                       required minlength="8">
                @error('new_password_confirmation')
                    <div class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>
                    @if($isFirstTime) Set Password @else Change Password @endif
                </button>
            </div>
        </form>

        {{-- Security tip --}}
        <div class="alert alert-light border mt-4 mb-0 py-2 small">
            <i class="fas fa-lightbulb me-1 text-warning"></i>
            <strong>Security Tip:</strong> Choose a strong password that you haven't used before and keep it secure.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const newPassword     = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');

    function validateMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    newPassword.addEventListener('input', validateMatch);
    confirmPassword.addEventListener('input', validateMatch);
});
</script>
</body>
</html>
