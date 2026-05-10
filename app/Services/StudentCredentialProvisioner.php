<?php

namespace App\Services;

use App\Mail\StudentTemporaryPasswordMail;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class StudentCredentialProvisioner
{
    /**
     * Set a new random password (hashed), require change on next use, and optionally email the plain secret.
     *
     * When sending email, password changes are committed only if SMTP succeeds (transaction rollbacks on failure).
     *
     * @return array{temporary_password: string, email_sent: bool}
     */
    public function assignTemporaryPasswordAndNotify(Student $student, StudentAccount $account, bool $sendEmail): array
    {
        $plain = Str::password(16);

        if (! $sendEmail) {
            $account->password = $plain;
            $account->must_change_password = true;
            $account->save();

            Log::warning('Student account provisioned without sending email (no address or placeholder).', [
                'student_id' => $student->student_id,
            ]);

            return ['temporary_password' => $plain, 'email_sent' => false];
        }

        try {
            DB::transaction(function () use ($student, $account, $plain): void {
                $account->password = $plain;
                $account->must_change_password = true;
                $account->save();

                Mail::to($account->email)->send(new StudentTemporaryPasswordMail($student, $plain));
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send student credentials email.', [
                'student_id' => $student->student_id,
                'email' => $account->email,
                'message' => $e->getMessage(),
            ]);

            return ['temporary_password' => $plain, 'email_sent' => false];
        }

        return ['temporary_password' => $plain, 'email_sent' => true];
    }
}
