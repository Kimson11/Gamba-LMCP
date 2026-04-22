<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ActivatePrivilegedSessionRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trusted_device_id' => ['required', 'integer'],
            'mfa_verified' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trusted_device_id.required' => 'Select a trusted device.',
            'mfa_verified.accepted' => 'Confirm that MFA verification has been completed.',
        ];
    }
}
