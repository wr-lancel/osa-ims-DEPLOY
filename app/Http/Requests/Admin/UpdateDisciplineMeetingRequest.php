<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisciplineMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'purpose_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'in:scheduled,rescheduled,completed,cancelled'],
        ];
    }
}
