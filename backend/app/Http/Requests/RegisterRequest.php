<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route — anyone can register
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'username'      => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'date_of_birth' => ['required', 'date', 'before:-13 years'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
