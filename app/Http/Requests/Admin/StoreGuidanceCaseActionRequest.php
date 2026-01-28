<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuidanceCaseActionRequest extends FormRequest
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
            'guidance_case_id' => ['required', 'exists:guidance_cases,guidance_case_id'],
            'note' => ['nullable', 'string'],
            'action_status' => ['nullable', 'string', 'in:pending,ongoing,resolved,closed'],
            'action_at' => ['nullable', 'date'],
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
            'guidance_case_id.required' => 'Guidance case is required.',
            'guidance_case_id.exists' => 'The selected guidance case is invalid.',
            'action_status.in' => 'The action status must be pending, ongoing, resolved, or closed.',
            'action_at.date' => 'The action date must be a valid date.',
        ];
    }
}
