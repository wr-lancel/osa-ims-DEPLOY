<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSportsBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'exists:students,student_id'],
            'employee_id' => ['nullable', 'exists:employees,employee_id'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'borrow_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:borrow_date'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:borrow_date'],
            'status' => ['required', 'in:pending,approved,rejected,borrowed,returned'],
            'notes' => ['nullable', 'string'],
            'admin_remarks' => ['nullable', 'string'],
        ];
    }
}

