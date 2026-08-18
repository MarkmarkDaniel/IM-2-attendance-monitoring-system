<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],

            'student_number' => [
                'required', 'string', 'max:30',
                'unique:students,student_number',
            ],

            'email' => [
                'required', 'string', 'email', 'max:150',
                'unique:users,email',
                'unique:students,email',
            ],

            'phone' => ['nullable', 'string', 'max:30'],

            'department_id' => ['required', 'integer', 'exists:departments,id'],

            'year_level' => [
                'required',
                Rule::in(['1st Year', '2nd Year', '3rd Year', '4th Year']),
            ],

            'section' => ['required', 'string', 'max:20'],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.unique' => 'That student number is already registered.',
            'email.unique'          => 'That email address is already registered.',
            'department_id.exists'  => 'Please select a valid department.',
            'password.confirmed'    => 'The password confirmation does not match.',
        ];
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'department',
        ];
    }
}
