<?php

namespace App\Http\Requests\Student;

use App\Models\SystemSetting;
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
        $categories = SystemSetting::getList('complaint_categories');

        return [
            'category' => ['required', 'string', 'in:' . implode(',', $categories)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'incident_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'respondent_type' => ['nullable', 'in:student,employee,other'],
            'respondent_student_number' => ['nullable', 'required_if:respondent_type,student', 'exists:students,student_number'],
            'respondent_employee_id' => ['nullable', 'required_if:respondent_type,employee', 'exists:employees,employee_id'],
            'respondent_name' => ['nullable', 'required_if:respondent_type,other', 'string', 'max:255'],
            'anonymous' => ['nullable', 'boolean'],
        ];
    }
}
