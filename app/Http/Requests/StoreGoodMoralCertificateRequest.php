<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGoodMoralCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'      => 'required|string|max:255',
            'student_number' => 'required|string|max:50|exists:students,student_number',
            'course'         => 'required|string|max:255',
            'year_graduated' => 'required|string|max:10',
            'contact_number' => 'required|string|max:20',
            'email'          => 'required|email|max:255',
            'purpose'        => 'required|string|max:1000',
        ];
    }

    /**
     * Check that the submitted full_name matches the student record.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('student_number')) {
                    return;
                }

                $student = Student::where('student_number', $this->student_number)->first();
                if (!$student) {
                    return;
                }

                $submitted  = strtolower(trim($this->full_name ?? ''));
                $firstName  = strtolower(trim($student->first_name));
                $lastName   = strtolower(trim($student->last_name));

                if (!str_contains($submitted, $firstName) || !str_contains($submitted, $lastName)) {
                    $validator->errors()->add(
                        'full_name',
                        'The name does not match the student record for this student number.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'      => 'Please enter your full name.',
            'student_number.required' => 'Please enter your student number.',
            'student_number.exists'   => 'Student number not found in our records.',
            'course.required'         => 'Please enter your course/program.',
            'year_graduated.required' => 'Please enter your year of graduation.',
            'contact_number.required' => 'Please enter your contact number.',
            'email.required'          => 'Please enter your email address.',
            'purpose.required'        => 'Please state the purpose of your request.',
        ];
    }
}
