<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSecurityFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('teacher'));
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approved,rejected,investigate',
            'review_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be approved, rejected, or investigate',
            'review_notes.string' => 'Review notes must be a string',
            'review_notes.max' => 'Review notes cannot exceed 1000 characters',
        ];
    }
}
