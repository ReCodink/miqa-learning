<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'qr_token' => 'required|string|uuid',
            'session_id' => 'required|exists:presence_sessions,id',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'device_fingerprint' => 'nullable|array',
            'device_fingerprint.user_agent' => 'nullable|string|max:500',
            'device_fingerprint.device_id' => 'nullable|string|max:255',
            'device_fingerprint.device_type' => 'nullable|in:mobile,tablet,desktop',
            'device_fingerprint.os_name' => 'nullable|string|max:100',
            'device_fingerprint.os_version' => 'nullable|string|max:100',
            'device_fingerprint.app_version' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'qr_token.required' => 'QR token is required',
            'qr_token.uuid' => 'QR token must be a valid UUID',
            'session_id.required' => 'Session ID is required',
            'session_id.exists' => 'Session does not exist',
            'gps_latitude.between' => 'Latitude must be between -90 and 90',
            'gps_longitude.between' => 'Longitude must be between -180 and 180',
            'device_fingerprint.array' => 'Device fingerprint must be an array',
            'device_fingerprint.*.in' => 'Invalid device fingerprint data',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('device_fingerprint') && !is_array($this->device_fingerprint)) {
            $this->merge(['device_fingerprint' => null]);
        }
    }
}
