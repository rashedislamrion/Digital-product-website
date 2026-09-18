<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'review_text' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->filled('title') ? strip_tags(trim((string) $this->input('title'))) : null,
            'review_text' => strip_tags(trim((string) $this->input('review_text'))),
            'rating' => $this->filled('rating') ? (int) $this->input('rating') : null,
        ]);
    }
}
