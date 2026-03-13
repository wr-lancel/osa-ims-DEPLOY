<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicationArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'status' => ['nullable', 'string', 'in:draft,pending_review,published'],
        ];
    }
}
