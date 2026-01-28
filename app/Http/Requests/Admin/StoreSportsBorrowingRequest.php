<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSportsBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['nullable', 'exists:students,student_number'],
            'employee_id' => ['nullable', 'exists:employees,employee_id'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'borrow_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after:borrow_date'],
            'status' => ['required', 'in:borrowed,returned'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.exists' => 'The selected student does not exist.',
            'employee_id.exists' => 'The selected employee does not exist.',
            'expected_return_date.after' => 'Expected return date must be after borrow date.',
        ];
    }
}

