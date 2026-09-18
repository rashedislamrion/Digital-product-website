<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'order_id' => ['nullable', 'string', 'exists:orders,id'],
            'license_id' => ['nullable', 'string', 'exists:licenses,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => strip_tags(trim((string) $this->input('subject'))),
            'message' => strip_tags(trim((string) $this->input('message'))),
            'order_id' => $this->filled('order_id') ? trim((string) $this->input('order_id')) : null,
            'license_id' => $this->filled('license_id') ? trim((string) $this->input('license_id')) : null,
        ]);
    }
}
