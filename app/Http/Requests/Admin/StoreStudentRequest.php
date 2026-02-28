<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studentNumberRules = [
            'required',
            'string',
            'max:50',
        ];

        // Only enforce unique on create (when student doesn't exist)
        // For existing students, we allow re-enrollment in different terms
        if (!$this->isMethod('PUT') && !$this->isMethod('PATCH')) {
            // Check if student exists - if not, require unique student_number
            $existingStudent = \App\Models\Student::where('student_number', $this->student_number)->first();
            if (!$existingStudent) {
                $studentNumberRules[] = Rule::unique('students', 'student_number');
            }
        }

        return [
            'acad_id' => ['required', 'exists:academic_calendar,calendar_id'],
            'student_number' => $studentNumberRules,
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'course_id' => ['required', 'exists:courses,course_id'],
            'section' => ['nullable', 'string', 'max:10'],
            'year_level' => ['required', 'string', 'in:1,2,3,4,5'],
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
            'acad_id.required' => 'Please select an academic term.',
            'acad_id.exists' => 'The selected academic term is invalid.',
            'student_number.unique' => 'The student number has already been taken.',
            'course_id.required' => 'Please select a course.',
            'course_id.exists' => 'The selected course is invalid.',

            'year_level.required' => 'Please select a year level.',
            'year_level.in' => 'The year level must be between 1 and 5.',
        ];
    }
}

