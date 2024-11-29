<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // You can add authorization logic if needed
    }

    public function rules()
    {
        return [
            'store_name' => 'required|string|max:255|unique:stores,store_name',
            'description' => 'nullable|string|max:500',
        ];
    }
}
