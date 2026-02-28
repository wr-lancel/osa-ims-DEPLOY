<?php

namespace App\Http\Requests\Admin;

use App\Models\DisciplineWorkflowStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDisciplineRequest extends FormRequest
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
            'violation_date' => ['required', 'date'],
            'violation_type' => ['required', 'string', 'max:255', 'exists:discipline_violation_types,name'],
            'description' => ['required', 'string'],
            'severity' => ['nullable', 'in:Minor,Moderate,Major'],
            'status' => ['required', Rule::in(DisciplineWorkflowStep::getStepNames())],
            'sanction' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'date_resolved' => ['nullable', 'date'],
            'reported_by' => ['nullable', 'exists:users,user_id'],
            'narrative_report' => ['nullable', 'string'],
            'narrative_report_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'remove_narrative_file' => ['nullable', 'boolean'],
        ];
    }
}

