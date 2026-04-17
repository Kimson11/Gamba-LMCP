<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateCooperativeRequest extends FormRequest
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
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:cooperatives,code'],
            'country_code' => ['required', 'string', 'size:2'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * Normalize key fields before validation so duplicate checks are deterministic.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper((string) $this->input('country_code')),
            'code' => strtoupper((string) $this->input('code')),
        ]);
    }
}
