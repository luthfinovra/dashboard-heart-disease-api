<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;

class CreateDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:65535',
            'schema' => 'required|array',
            'schema.columns' => 'required|array',
            'schema.columns.*.name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
            'schema.columns.*.type' => 'required|string|in:string,text,integer,decimal,float,datetime,time,file,boolean,enum,email,phone',
            //'schema.columns.*.type' => 'required|string|in:string,integer,enum,decimal,date,file,time,datetime,boolean,array,float,text,email,phone,json,range',
            'schema.columns.*.options' => 'required_if:schema.columns.*.type,enum|array',
            'schema.columns.*.format' => 'required_if:schema.columns.*.type,file|string',
            'schema.columns.*.multiple' => 'boolean|nullable',
            'schema.columns.*.is_visible' => 'boolean|nullable',
            //'schema.columns.*.required' => 'boolean|nullable',
            //'schema.columns.*.min' => 'numeric|nullable',
            //'schema.columns.*.max' => 'numeric|nullable',
            //'schema.columns.*.step' => 'numeric|nullable',
            //'schema.columns.*.default' => 'nullable',
            //'schema.columns.*.description' => 'string|nullable',
            //'schema.columns.*.unit' => 'string|nullable',
            'cover_page' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama penyakit harus diisi.',
            'name.max' => 'Nama penyakit maksimal 255 karakter.',
            'deskripsi.max' => 'Deskripsi penyakit maksimal 65535 karakter.',
            'schema.required' => 'Schema harus diisi.',
            'schema.array' => 'Schema harus dalam format yang valid.',
            'schema.columns.required' => 'Columns dalam schema harus diisi.',
            'schema.columns.array' => 'Columns harus dalam format array.',
            'schema.columns.*.name.required' => 'Nama kolom harus diisi.',
            'schema.columns.*.name.regex' => 'Nama kolom hanya boleh berisi huruf, angka, dan underscore tanpa spasi.',
            'schema.columns.*.type.required' => 'Tipe kolom harus diisi.',
            'schema.columns.*.type.in' => 'Tipe kolom tidak valid.',
            'schema.columns.*.options.required_if' => 'Options harus diisi untuk tipe enum.',
            'schema.columns.*.format.required_if' => 'Format harus diisi untuk tipe file.',
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