<?php

namespace App\Services;

use App\Mail\StudentTemporaryPasswordMail;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class StudentCredentialProvisioner
{
    /**
     * Set a new random password (hashed), require change on next use, and optionally email the plain secret.
     *
     * @return string The plain-text password (for logging in tests or admin copy); avoid persisting.
     */
    public function assignTemporaryPasswordAndNotify(Student $student, StudentAccount $account, bool $sendEmail): string
    {
        $plain = Str::password(16);

        $account->password = $plain;
        $account->must_change_password = true;
        $account->save();

        if (! $sendEmail) {
            Log::warning('Student account provisioned without sending email (no address or placeholder).', [
                'student_id' => $student->student_id,
            ]);

            return $plain;
        }

        try {
            Mail::to($account->email)->send(new StudentTemporaryPasswordMail($student, $plain));
        } catch (\Throwable $e) {
            Log::error('Failed to send student credentials email.', [
                'student_id' => $student->student_id,
                'email' => $account->email,
                'message' => $e->getMessage(),
            ]);
        }

        return $plain;
    }
}
