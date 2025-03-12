<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Aquí puedes agregar lógica de autorización si fuera necesario
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name'             => 'required|string|max:255',
            'lastname'         => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email',
            'phone'            => 'required|string|max:20',
            'type_document'    => 'nullable|string|max:50',
            'document_number'  => 'nullable|string|max:50|unique:users,document_number',
            'gender'           => 'required|in:M,F',
            'birthday'         => 'nullable|date',
            'role_id'          => 'required|exists:roles,id',
            'password'         => 'required|string|min:6',
            'imagen'           => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
