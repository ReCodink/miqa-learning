<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
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
     */
    public function rules(): array
    {
        // Fallbacks to get the bound model or raw route parameter correctly
        $teacher = $this->route('teacher');
        $teacherId = is_object($teacher) ? $teacher->id : ($teacher ?? $this->route('id'));

        return [
            'name'     => 'sometimes|required|string|max:255',
            'gender'   => 'sometimes|required|in:male,female',
            'email'    => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($teacherId),
            ],
            // Password is completely optional when editing
            'password' => 'nullable|string|min:8|confirmed',
            'photo'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required'       => 'Teacher name is required.',
            'name.max'            => 'Teacher name must not exceed 255 characters.',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Please provide a valid email address.',
            'email.unique'        => 'This email address is already taken.',
            'password.min'        => 'Password must be at least 8 characters.',
            'password.confirmed'  => 'Password confirmation does not match.',
            'photo.image'         => 'Photo must be an image file.',
            'photo.mimes'         => 'Photo must be jpeg, png, jpg, or gif format.',
            'photo.max'           => 'Photo size must not exceed 2MB.',
            'gender.required'     => 'Gender selection is required.',
            'gender.in'           => 'Gender must be either male or female.',
        ];
    }
}