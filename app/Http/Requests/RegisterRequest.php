<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name is required',
            'email.required' => 'The email is required',
            'email.email' => 'Invalid email format',
            'email.unique' => 'This email is already registered',
            'password.required' => 'The password is required',
            'password.confirmed' => 'The passwords do not match',
        ];
    }
}
