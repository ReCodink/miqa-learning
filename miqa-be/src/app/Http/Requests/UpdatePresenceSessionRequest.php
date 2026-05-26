<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePresenceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('teacher'));
    }

    public function rules(): array
    {
        return [
            'session_name' => 'nullable|string|max:255|min:3',
            'session_type' => 'nullable|in:class,event,exam_preparation',
            'scheduled_start_at' => 'nullable|date|after_or_equal:now',
            'scheduled_end_at' => 'nullable|date|after:scheduled_start_at',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'gps_radius_meters' => 'nullable|integer|min:10|max:500',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'session_name.min' => 'Session name must be at least 3 characters',
            'session_type.in' => 'Session type must be class, event, or exam_preparation',
            'scheduled_start_at.after_or_equal' => 'Start time must be in the future',
            'scheduled_end_at.after' => 'End time must be after start time',
            'gps_latitude.between' => 'Latitude must be between -90 and 90',
            'gps_longitude.between' => 'Longitude must be between -180 and 180',
            'gps_radius_meters.min' => 'Geofence radius must be at least 10 meters',
            'gps_radius_meters.max' => 'Geofence radius cannot exceed 500 meters',
        ];
    }
}
