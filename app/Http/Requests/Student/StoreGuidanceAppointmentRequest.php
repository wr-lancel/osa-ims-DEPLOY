<?php

namespace App\Http\Requests\Student;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreGuidanceAppointmentRequest extends FormRequest
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
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'concern' => ['required', 'string', 'max:1000'],
            'appointment_type' => ['required', 'string', 'in:' . implode(',', SystemSetting::getList('guidance_appointment_types'))],
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
            'appointment_date.required' => 'The appointment date is required.',
            'appointment_date.date' => 'The appointment date must be a valid date.',
            'appointment_date.after_or_equal' => 'The appointment date must be today or a future date.',
            'appointment_time.required' => 'The appointment time is required.',
            'appointment_time.date_format' => 'The appointment time must be in HH:mm format.',
            'concern.required' => 'The concern is required.',
            'concern.max' => 'The concern may not be greater than 1000 characters.',
            'appointment_type.required' => 'The appointment type is required.',
            'appointment_type.in' => 'The appointment type must be one of: counseling, consultation, referral, or other.',
        ];
    }
}
