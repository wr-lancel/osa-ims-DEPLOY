<?php

namespace App\Http\Requests\Admin;

use App\Models\SystemSetting;
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
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'org_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_org', 'org_code')->ignore($this->route('organization')->org_id, 'org_id'),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:' . implode(',', SystemSetting::getList('organization_types'))],
            'status' => ['required', 'in:active,inactive'],
            'adviser_name' => ['nullable', 'string', 'max:255'],
            'mission' => ['nullable', 'string'],
            'mission_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'vision' => ['nullable', 'string'],
            'vision_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'goals' => ['nullable', 'string'],
            'goals_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'constitution_bylaws' => ['nullable', 'string'],
            'constitution_bylaws_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'remove_mission_file' => ['nullable', 'boolean'],
            'remove_vision_file' => ['nullable', 'boolean'],
            'remove_goals_file' => ['nullable', 'boolean'],
            'remove_constitution_bylaws_file' => ['nullable', 'boolean'],
        ];
    }
}

