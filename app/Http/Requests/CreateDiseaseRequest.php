<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDiseaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:65535',
            'schema' => 'required|json',
            'cover_page' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama penyakit harus diisi.',
            'name.max' => 'Nama penyakit maksimal 255 karakter.',
            'deskripsi.max' => 'Deskripsi penyakit maksimal 65535 karakter.',
            'schema.required' => 'Schema harus diisi.',
            'schema.json' => 'Schema harus dalam format JSON yang valid.',
            'cover_page.string' => 'Cover page harus berupa string.',
            'cover_page.image' => 'Cover page harus berupa file gambar.',
            'cover_page.mimes' => 'Cover page harus berupa file bertipe: jpeg, png, jpg, gif, atau svg.',
            'cover_page.max' => 'Ukuran cover page maksimal 2MB.',
        ];
}
}
