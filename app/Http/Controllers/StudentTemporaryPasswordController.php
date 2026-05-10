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
    public function create(): View
    {
        return view('auth.request-student-temporary-password');
    }

    public function store(
        RequestStudentTemporaryPasswordRequest $request,
        StudentCredentialProvisioner $provisioner,
    ): RedirectResponse {
        $studentId = $request->validated('student_id');

        $student = Student::query()->where('student_id', $studentId)->firstOrFail();
        $account = StudentAccount::query()->where('student_id', $studentId)->firstOrFail();

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

    private function syncAccountEmailFromStudent(Student $student, StudentAccount $account): void
    {
        $email = trim((string) $student->email);
        if ($email !== '' && $account->email !== $email) {
            $account->email = $email;
            $account->save();
        }
    }
}
