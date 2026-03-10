<?php

namespace App\Http\Requests\Admin;

use App\Models\SystemSetting;
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
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'org_code' => ['required', 'string', 'max:50', 'unique:student_org,org_code'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:' . implode(',', SystemSetting::getList('organization_types'))],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}

