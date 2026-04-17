<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the incoming login payload for the API.
 *
 * Expected request body (JSON):
 * {
 *   "email":       "farmer@example.com",
 *   "password":    "secret123",
 *   "device_name": "Gamba Android App"   ← used as the Sanctum token name
 * }
 *
 * 'device_name' is required because Sanctum tokens are named per device.
 * This lets users revoke individual device tokens from their profile.
 */
class LoginRequest extends FormRequest
{
    /**
     * All API login requests are publicly accessible — no prior auth needed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the login payload.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // Standard email format validation.
            'email' => ['required', 'string', 'email'],

            // Password is a raw string; hashing comparison happens in the controller.
            'password' => ['required', 'string'],

            // Device name becomes the Sanctum token label.
            // Helps users manage their active sessions per device.
            // Example: 'Gamba Android v1.2', 'Gamba Web Admin'
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
