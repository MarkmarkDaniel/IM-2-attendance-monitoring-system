<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id;

        return [
            'subject_code' => [
                'required', 'string', 'max:30',
                Rule::unique('subjects', 'subject_code')->ignore($subjectId),
            ],
            'subject_name'  => ['required', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'units'         => ['required', 'integer', 'min:1', 'max:12'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_code.unique'  => 'That subject code is already in use.',
            'department_id.exists' => 'Please select a valid department.',
        ];
    }
}
