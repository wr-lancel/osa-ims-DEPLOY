<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_name' => ['required', 'string', 'max:255'],
            'org_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_org', 'org_code')->ignore($this->route('organization')->org_id, 'org_id'),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:Academic,Cultural,Governance,Special Interest'],
            'status' => ['required', 'in:active,inactive'],
            'adviser_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}

