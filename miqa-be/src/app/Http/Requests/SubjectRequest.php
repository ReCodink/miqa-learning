<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handle authorization in middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|string|max:255|unique:subjects,name,' . $id,
            'tagline' => 'required|string|max:255',
            'about' => 'required|string|max:1000',
            'photo' => $this->isMethod('post')
                        ? 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                        : 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'content' => $this->isMethod('post')
                        ? 'required|file|mimes:pdf|max:10240'
                        : 'sometimes|file|mimes:pdf|max:10240',
            'topic_id' => 'required|string|exists:topics,id',
            'teacher_id' => [
                'required',
                'string',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && !User::find($value)?->hasRole('teacher')) {
                        $fail('The selected user must have a teacher role.');
                    }
                }
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name'       => 'subject name',
            'tagline'    => 'subject tagline',
            'about'      => 'subject description',
            'photo'      => 'photo file',
            'content'    => 'content PDF file',
            'topic_id'   => 'topic',
            'teacher_id' => 'teacher',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Leaving specific file constraints or structural overrides intact
            'photo.mimes'       => 'The photo file must be a jpeg, png, jpg, or gif format.',
            'photo.max'         => 'The photo file size must not exceed 2MB.',
            'content.mimes'     => 'The content must be a valid PDF file.',
            'content.max'       => 'The content PDF file size must not exceed 10MB.',
            'topic_id.exists'   => 'The selected topic does not exist.',
            'teacher_id.exists' => 'The selected teacher does not exist.',
        ];
    }
}
