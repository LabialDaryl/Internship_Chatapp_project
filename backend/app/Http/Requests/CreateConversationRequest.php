<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:direct,group'],
            'user_id' => ['required_if:type,direct', 'integer', 'exists:users,id'],
            'name' => ['required_if:type,group', 'string', 'max:255'],
            'participants' => ['required_if:type,group', 'array', 'min:1'],
            'participants.*' => ['integer', 'exists:users,id'],
        ];
    }
}
