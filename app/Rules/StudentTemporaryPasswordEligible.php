<?php

namespace App\Rules;

use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Only registered students who still qualify for first-time (temporary) password delivery may submit.
 * Rejects faculty IDs, unknown IDs, invalid email on file, and accounts that already finished first login.
 */
final class StudentTemporaryPasswordEligible implements ValidationRule
{
    private const PLACEHOLDER_EMAIL_SUFFIX = '@student.placeholder.local';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $studentId = trim((string) $value);
        if ($studentId === '') {
            return;
        }

        if (User::query()
            ->whereNotNull('faculty_id')
            ->where('faculty_id', $studentId)
            ->exists()) {
            $fail('This page is for student accounts. Use the main login if you have a faculty or staff account.');

            return;
        }

        $student = Student::query()->where('student_id', $studentId)->first();
        $account = StudentAccount::query()->where('student_id', $studentId)->first();

        if ($student === null || $account === null) {
            $fail('No student login account was found for this ID. This option is only for registered students who have not finished first-time setup.');

            return;
        }

        $email = trim((string) $student->email);
        $emailOk = $email !== ''
            && ! str_ends_with(strtolower($email), self::PLACEHOLDER_EMAIL_SUFFIX)
            && filter_var($email, FILTER_VALIDATE_EMAIL);

        if (! $emailOk) {
            $fail('This student ID is on file, but there is no valid school email for delivery. Ask your coordinator to correct the email on your student record.');

            return;
        }

        $passwordUnset = blank($account->getRawOriginal('password'));
        $eligibleForTemporaryPassword = $passwordUnset || $account->must_change_password === true;

        if (! $eligibleForTemporaryPassword) {
            $fail('This account has already completed first-time login. Sign in with your password. If you forgot it, ask your coordinator to reset your student account.');

            return;
        }
    }
}
