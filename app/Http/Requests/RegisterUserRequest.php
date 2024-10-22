<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;

class RegisterUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email|max:100',
            'password' => 'required|string|min:8|max:64|confirmed',
            //Front end include: 'password_confirmation' field
            'institution' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,prefer not to say',
            'phone_number' => 'nullable|string|max:50',
            'tujuan_permohonan' => 'nullable|string|max:65535',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama harus diisi.',
            'name.max' => 'Nama maksimal 100 karakter',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Email tidak valid.',
            'email.max' => 'Email maksimal 100 karakter',
            'email.unique' => 'Email tersebut sudah terdaftar, silahkan login.',
            'password.required' => 'Password harus diisi.',
            'password.confirmed' => 'Password konfirmasi tidak sesuai.',
            'password.min' => 'Password minimal 8 karakter',
            'password.max' => 'Password maksimal 64 karakter',
            'institution.max' => 'Institusi maksimal 255 karakter.',
            'gender.in' => 'Gender tidak sesuai.',
            'phone_number.max' => 'Nomor telepon maksimal 50 karakter.',
            'tujuan_permohonan.max' => 'Tujuan permohonan maksimal 65535 karakter',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        foreach ($errors as $field => $message) {
            throw new HttpResponseException(ResponseJson::failedResponse("field error", [$field => $message[0]]));
        }
    }
}
