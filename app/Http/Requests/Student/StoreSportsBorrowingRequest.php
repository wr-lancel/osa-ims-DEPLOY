<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreSportsBorrowingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'borrow_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after:borrow_date'],
            'notes' => ['nullable', 'string'],
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
            'item_name.required' => 'The item name is required.',
            'item_name.max' => 'The item name may not be greater than 255 characters.',
            'borrow_date.required' => 'The borrow date is required.',
            'borrow_date.date' => 'The borrow date must be a valid date.',
            'expected_return_date.required' => 'The expected return date is required.',
            'expected_return_date.date' => 'The expected return date must be a valid date.',
            'expected_return_date.after' => 'The expected return date must be after the borrow date.',
        ];
    }
}
