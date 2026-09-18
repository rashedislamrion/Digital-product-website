<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', 'string', 'in:sslcommerz,paddle'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'country' => strip_tags(trim((string) $this->input('country'))),
            'payment_method' => trim((string) $this->input('payment_method')),
        ]);
    }
}
