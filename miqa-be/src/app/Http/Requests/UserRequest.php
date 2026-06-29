<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handle authorization via Middleware/Policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Safe resolution whether route parameter is a Model instance or a ULID string
        $userRoute = $this->route('user');
        $userId = is_object($userRoute) ? $userRoute->id : $userRoute;

        $isPost = $this->isMethod('post');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            // Changed 'sometimes' to 'nullable' for updates to handle empty payload elements safely
            'password' => $isPost
                ? 'required|string|min:8|confirmed'
                : 'nullable|string|min:8|confirmed',
            'gender' => 'required|string|in:male,female',
            'photo' => $isPost
                ? 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'gender.required' => 'Gender selection is required.',
            'gender.in' => 'Gender must be either male or female.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'photo.required' => 'A profile photo is required.',
            'photo.image' => 'Photo must be an image file.',
            'photo.mimes' => 'Photo must be jpeg, png, jpg, or gif format.',
            'photo.max' => 'Photo size must not exceed 2MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email Address',
            'gender' => 'Gender',
            'password' => 'Password',
            'photo' => 'Profile Photo',
        ];
    }
}
