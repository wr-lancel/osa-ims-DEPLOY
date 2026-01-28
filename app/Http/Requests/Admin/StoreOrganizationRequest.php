<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_name' => ['required', 'string', 'max:255'],
            'org_code' => ['required', 'string', 'max:50', 'unique:student_org,org_code'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:Academic,Cultural,Governance,Special Interest'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}

