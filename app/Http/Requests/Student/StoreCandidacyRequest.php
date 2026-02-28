<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidacyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'org_id' => ['required', 'exists:student_org,org_id'],
            'position_id' => ['required', 'exists:org_positions,position_id'],
            'acad_id' => ['required', 'exists:academic_calendar,calendar_id'],
            'platform_statement' => ['nullable', 'string', 'max:5000'],
            'motivation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'Please select an organization.',
            'org_id.exists' => 'The selected organization is invalid.',
            'position_id.required' => 'Please select a position.',
            'position_id.exists' => 'The selected position is invalid.',
            'acad_id.required' => 'Please select a term/semester.',
            'acad_id.exists' => 'The selected term is invalid.',
        ];
    }
}
