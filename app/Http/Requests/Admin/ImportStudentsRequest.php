<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'staff']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acad_id' => ['required', 'exists:academic_calendar,calendar_id'],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10MB max
            ],
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
            'acad_id.required' => 'Please select an academic term before importing.',
            'acad_id.exists' => 'The selected academic term is invalid.',
            'file.required' => 'Please select a file to import.',
            'file.mimes' => 'The file must be an Excel file (.xlsx, .xls) or CSV file.',
            'file.max' => 'The file size must not exceed 10MB.',
        ];
    }
}

