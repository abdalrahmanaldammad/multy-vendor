<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For now, return true to allow all users.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Define validation rules for user input.
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'required|string|regex:/^\+?[0-9]{10,15}$/|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,store_owner,customer',
        ];
    }

    /**
     * Custom error messages (optional).
     */
    public function messages()
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'phone.regex' => 'Phone must be a valid number with 10-15 digits.',
            'role.in' => 'Role must be either admin, store_owner, or customer.',
        ];
    }
}
