<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Otorisasi ditangani di middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Menangkap ID dari parameter route, mendukung format resource {class_room} maupun manual {id}
        $id = $this->route('class_room') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255|unique:class_rooms,name,' . $id,
            'photo' => $this->isMethod('post')
                        ? 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                        : 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'protocol_id' => 'required|string|exists:protocols,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Classroom name is required',
            'name.unique' => 'Classroom name already exists',
            'photo.required' => 'Photo is required',
            'photo.image' => 'Photo must be an image file',
            'photo.mimes' => 'Photo must be jpeg, png, jpg, or gif format',
            'photo.max' => 'Photo size must not exceed 2MB',
            'protocol_id.required' => 'Protocol assignment is required',
            'protocol_id.exists' => 'The selected protocol is invalid or does not exist',
        ];
    }
}
