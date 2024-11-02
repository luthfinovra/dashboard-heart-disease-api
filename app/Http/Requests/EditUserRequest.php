<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;
use App\Models\User;

class EditUserRequest extends FormRequest
{
    public function rules(): array
{
    $user = $this->route('id');
    $userRecord = User::find($user);
    $userRole = $userRecord ? $userRecord->role : null;

    return [
        'name' => 'sometimes|required|string|max:100',
        'email' => 'sometimes|nullable|email|unique:users,email,' . $user . '|max:100',
        'password' => 'sometimes|nullable|string|min:8|max:64|confirmed',
        'role' => 'sometimes|required|in:admin,operator,researcher',
        'institution' => 'nullable|string|max:255',
        'gender' => 'nullable|in:male,female,prefer not to say',
        'phone_number' => 'nullable|string|max:50',
        'approval_status' => 'sometimes|required|in:approved,pending,rejected',
        'disease_ids' => $userRole === 'operator' ? 'sometimes|nullable|array' : 'nullable',
        'disease_ids.*' => $userRole === 'operator' ? 'exists:diseases,id' : 'nullable',
    ];
}

public function messages()
{
    return [
        'name.required' => 'Nama harus diisi.',
        'name.max' => 'Nama maksimal 100 karakter',
        'email.email' => 'Email tidak valid.',
        'email.max' => 'Email maksimal 100 karakter',
        'email.unique' => 'Email tersebut sudah terdaftar',
        'password.min' => 'Password minimal 8 karakter',
        'password.max' => 'Password maksimal 64 karakter',
        'password.confirmed' => 'Password konfirmasi tidak sesuai.',
        'role.required' => 'Role harus diisi',
        'role.in' => 'Role tidak sesuai',
        'institution.max' => 'Institusi maksimal 255 karakter.',
        'gender.in' => 'Gender tidak sesuai.',
        'phone_number.max' => 'Nomor telepon maksimal 50 karakter.',
        'approval_status.required' => 'Status Approval harus diisi.',
        'approval_status.in' => 'Status Approval tidak sesuai.',
        'disease_ids.array' => 'Managed diseases harus berupa array',
        'disease_ids.*.exists' => 'Setiap Disease ID yang dipilih harus valid dan ada dalam database.',
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
