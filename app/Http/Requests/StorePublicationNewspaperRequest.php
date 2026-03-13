<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicationNewspaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'issue_number' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,pending_review,published'],
        ];
    }
}
