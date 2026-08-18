<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        // On update, ignore this record's own row in the unique check
        $departmentId = $this->route('department')?->id;

        return [
            'department_code' => [
                'required', 'string', 'max:20',
                Rule::unique('departments', 'department_code')->ignore($departmentId),
            ],
            'department_name' => ['required', 'string', 'max:150'],
            'description'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_code.unique' => 'That department code is already in use.',
        ];
    }
}
