<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateClusterRequest extends FormRequest
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
            'cooperative_id' => ['required', 'integer', 'exists:cooperatives,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'supervisor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
