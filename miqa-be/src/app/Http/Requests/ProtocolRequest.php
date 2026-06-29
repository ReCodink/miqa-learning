<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProtocolRequest extends FormRequest
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
        // Menyesuaikan parameter route, pastikan di route menggunakan {id} atau {protocol}
        $id = $this->route('id') ?? $this->route('protocol');

        return [
            'name' => 'required|string|max:255|unique:protocols,name,' . $id,
            'description' => 'required|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Protocol name is required',
            'name.unique' => 'Protocol name already exists',
            'description.required' => 'Protocol description is required',
        ];
    }
}
