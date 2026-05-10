<?php

namespace App\Http\Requests;

use App\Rules\StudentTemporaryPasswordEligible;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RequestStudentTemporaryPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()
                ->route('login.student-credentials')
                ->withErrors($validator)
                ->withInput()
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('student_id') && is_string($this->input('student_id'))) {
            $this->merge([
                'student_id' => trim($this->input('student_id')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'max:64', new StudentTemporaryPasswordEligible],
        ];
    }
}
