<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'username'   => ['sometimes', 'string', 'max:50', Rule::unique('users')->ignore($this->user()->id)],
            'email'      => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'bio'        => ['sometimes', 'nullable', 'string', 'max:500'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
