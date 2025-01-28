<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;

class EditDiseaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'diseaseId' => $this->route('diseaseId'),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'diseaseId' => 'required|integer|exists:diseases,id',
            'name' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|nullable|string|max:65535',
            'visibilitas' => 'sometimes|required|in:publik,privat',
            //'schema' => 'required|json',
            'cover_page' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama penyakit harus diisi.',
            'name.max' => 'Nama penyakit maksimal 255 karakter.',
            'deskripsi.max' => 'Deskripsi penyakit maksimal 65535 karakter.',
            //'schema.required' => 'Schema harus diisi.',
            //'schema.json' => 'Schema harus dalam format JSON yang valid.',
            'cover_page.image' => 'Cover page harus berupa file gambar.',
            'cover_page.mimes' => 'Cover page harus berupa file bertipe: jpeg, png, jpg, gif, atau svg.',
            'cover_page.max' => 'Ukuran cover page maksimal 2MB.',
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
