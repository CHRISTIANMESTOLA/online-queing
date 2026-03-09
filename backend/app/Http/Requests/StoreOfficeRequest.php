<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare request data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('prefix')) {
            $this->merge([
                'prefix' => strtoupper(trim((string) $this->input('prefix'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('offices', 'name')],
            'prefix' => ['required', 'string', 'regex:/^[A-Z]{2,10}$/', Rule::unique('offices', 'prefix')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
