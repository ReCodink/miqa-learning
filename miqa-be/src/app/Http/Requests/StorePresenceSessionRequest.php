<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePresenceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('teacher'));
    }

    public function rules(): array
    {
        return [
            'class_room_id' => 'required|exists:class_rooms,id',
            'session_name' => 'required|string|max:255|min:3',
            'session_type' => 'required|in:class,event,exam_preparation',
            'scheduled_start_at' => 'nullable|date|after_or_equal:now',
            'scheduled_end_at' => 'nullable|date|after:scheduled_start_at',
            'gps_latitude' => 'required_with:gps_longitude|numeric|between:-90,90',
            'gps_longitude' => 'required_with:gps_latitude|numeric|between:-180,180',
            'gps_radius_meters' => 'nullable|integer|min:10|max:500',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'class_room_id.required' => 'Classroom is required',
            'class_room_id.exists' => 'Selected classroom does not exist',
            'session_name.required' => 'Session name is required',
            'session_name.min' => 'Session name must be at least 3 characters',
            'session_type.required' => 'Session type is required',
            'session_type.in' => 'Session type must be class, event, or exam_preparation',
            'scheduled_start_at.after_or_equal' => 'Start time must be in the future',
            'scheduled_end_at.after' => 'End time must be after start time',
            'gps_latitude.between' => 'Latitude must be between -90 and 90',
            'gps_longitude.between' => 'Longitude must be between -180 and 180',
            'gps_latitude.required_with' => 'Both latitude and longitude are required together',
            'gps_longitude.required_with' => 'Both latitude and longitude are required together',
            'gps_radius_meters.min' => 'Geofence radius must be at least 10 meters',
            'gps_radius_meters.max' => 'Geofence radius cannot exceed 500 meters',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('gps_radius_meters') && $this->gps_radius_meters === '') {
            $this->merge(['gps_radius_meters' => null]);
        }
    }
}
