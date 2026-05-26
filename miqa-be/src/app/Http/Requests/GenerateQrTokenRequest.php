<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQrTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('teacher'));
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:presence_sessions,id',
            'expires_in_seconds' => 'nullable|integer|min:15|max:300',
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required',
            'session_id.exists' => 'Selected session does not exist',
            'expires_in_seconds.integer' => 'Expiration time must be an integer',
            'expires_in_seconds.min' => 'Token must expire in at least 15 seconds',
            'expires_in_seconds.max' => 'Token cannot expire in more than 300 seconds (5 minutes)',
        ];
    }
}
