<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $student   = $this->route('student');
        $studentId = $student?->id;
        $userId    = $student?->user_id;

        // Password is required when creating, optional when editing
        $passwordRule = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],

            'student_number' => [
                'required', 'string', 'max:30',
                Rule::unique('students', 'student_number')->ignore($studentId),
            ],

            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('students', 'email')->ignore($studentId),
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'phone'         => ['nullable', 'string', 'max:30'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],

            'year_level' => [
                'required',
                Rule::in(['1st Year', '2nd Year', '3rd Year', '4th Year']),
            ],

            'section' => ['required', 'string', 'max:20'],

            'status' => [
                'required',
                Rule::in(['Active', 'Inactive', 'Dropped']),
            ],

            'password' => [
                $passwordRule,
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.unique' => 'That student number is already registered.',
            'email.unique'          => 'That email address is already in use.',
            'password.confirmed'    => 'The password confirmation does not match.',
        ];
    }

    public function attributes(): array
    {
        return ['department_id' => 'department'];
    }
}
