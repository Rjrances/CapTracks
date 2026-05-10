<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestStudentTemporaryPasswordRequest;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Services\StudentCredentialProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class StudentTemporaryPasswordController extends Controller
{
    private const PLACEHOLDER_EMAIL_SUFFIX = '@student.placeholder.local';

    public function create(): View
    {
        return view('auth.request-student-temporary-password');
    }

    public function store(
        RequestStudentTemporaryPasswordRequest $request,
        StudentCredentialProvisioner $provisioner,
    ): RedirectResponse {
        $studentId = trim($request->validated('student_id'));

        $student = Student::where('student_id', $studentId)->first();
        $account = StudentAccount::where('student_id', $studentId)->first();

        if ($this->shouldSendTemporaryPassword($student, $account)) {
            $this->syncAccountEmailFromStudent($student, $account);
            $account->refresh();
            $result = $provisioner->assignTemporaryPasswordAndNotify($student, $account, true);

            if ($result['email_sent']) {
                return redirect()
                    ->route('login')
                    ->with(
                        'status',
                        'We sent a temporary password to the email address on file for that student ID. Check your inbox and spam folder.',
                    );
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'mail' => 'We could not send the email, so your password was not changed. Copy the MAIL_* block from `.env.example` into `.env`, run `php artisan config:clear`, then test with `php artisan mail:test your@email.com`.',
                ]);
        }

        if ($student !== null && $account !== null) {
            $email = trim((string) $student->email);
            $emailOk = $email !== ''
                && ! str_ends_with(strtolower($email), self::PLACEHOLDER_EMAIL_SUFFIX)
                && filter_var($email, FILTER_VALIDATE_EMAIL);

            if (! $emailOk) {
                return redirect()
                    ->route('login')
                    ->with(
                        'credential_warning',
                        'That student ID is registered, but there is no valid email on file for delivery. Ask your coordinator to correct the email on your student record.',
                    );
            }

            return redirect()
                ->route('login')
                ->with(
                    'credential_warning',
                    'That student ID is registered, but a temporary password is only sent before first login is finished. This account already has an active password — sign in with it. If you forgot it, ask your coordinator to reset the student account.',
                );
        }

        return redirect()
            ->route('login')
            ->with(
                'status',
                'If that student ID is registered with a school email on file, we sent a temporary password. Check your inbox and spam folder.',
            );
    }

    private function shouldSendTemporaryPassword(?Student $student, ?StudentAccount $account): bool
    {
        if ($student === null || $account === null) {
            return false;
        }

        $email = trim((string) $student->email);
        if ($email === '' || str_ends_with(strtolower($email), self::PLACEHOLDER_EMAIL_SUFFIX)) {
            return false;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $passwordUnset = blank($account->getRawOriginal('password'));

        if ($passwordUnset) {
            return true;
        }

        return $account->must_change_password === true;
    }

    private function syncAccountEmailFromStudent(Student $student, StudentAccount $account): void
    {
        $email = trim((string) $student->email);
        if ($email !== '' && $account->email !== $email) {
            $account->email = $email;
            $account->save();
        }
    }
}
