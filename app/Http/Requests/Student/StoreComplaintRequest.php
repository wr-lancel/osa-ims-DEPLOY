<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('student') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categories = ['Academic Integrity', 'Campus Conduct', 'Prohibited Activities', 'Other'];

        return [
            'category' => ['required', 'string', 'in:' . implode(',', $categories)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'incident_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'respondent_student_number' => ['nullable', 'string', 'max:50'],
            'anonymous' => ['nullable', 'boolean'],
        ];
    }
}
