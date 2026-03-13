<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'student_number' => 'required|string|max:50',
            'course'         => 'required|string|max:255',
            'year_graduated' => 'required|string|max:10',
            'contact_number' => 'required|string|max:20',
            'email'          => 'required|email|max:255',
            'purpose'        => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'      => 'Please enter your full name.',
            'student_number.required' => 'Please enter your student number.',
            'course.required'         => 'Please enter your course/program.',
            'year_graduated.required' => 'Please enter your year of graduation.',
            'contact_number.required' => 'Please enter your contact number.',
            'email.required'          => 'Please enter your email address.',
            'purpose.required'        => 'Please state the purpose of your request.',
        ];
    }
}
