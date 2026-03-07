<?php

namespace App\Http\Requests\Admin;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreGuidanceCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin', 'guidance_admin', 'staff']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'exists:enrolled_students,enrollment_id'],
            'case_no' => ['required', 'string', 'max:50', 'unique:guidance_cases,case_no'],
            'case_type' => ['required', 'string', 'in:' . implode(',', SystemSetting::getList('guidance_case_types'))],
            'concern' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:pending,ongoing,resolved,closed'],
            'assigned_staff_id' => ['nullable', 'exists:employees,employee_id'],
            'requested_at' => ['nullable', 'date'],
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
            'enrollment_id.required' => 'Enrollment is required.',
            'enrollment_id.exists' => 'The selected enrollment is invalid.',
            'case_no.required' => 'Case number is required.',
            'case_no.unique' => 'The case number has already been taken.',
            'case_type.required' => 'Case type is required.',
            'case_type.in' => 'The case type must be counseling, consultation, or referral.',
            'status.required' => 'Status is required.',
            'status.in' => 'The status must be pending, ongoing, resolved, or closed.',
            'assigned_staff_id.exists' => 'The selected staff member is invalid.',
            'requested_at.date' => 'The requested date must be a valid date.',
        ];
    }
}
