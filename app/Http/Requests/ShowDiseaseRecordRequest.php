<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;

class ShowDiseaseRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'diseaseId' => $this->route('diseaseId'),
            'recordId' => $this->route('recordId')
        ]);
    }

    public function rules(): array
    {
        return [
            'diseaseId' => 'required|integer|exists:diseases,id',
            'recordId' => [
                'required',
                'integer',
                'exists:disease_records,id',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\DiseaseRecord::where('id', $value)
                        ->where('disease_id', $this->route('diseaseId'))
                        ->exists();
                    
                    if (!$exists) {
                        $fail('The selected record does not belong to the specified disease.');
                    }
                },
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseJson::failedResponse('Validation error', $validator->errors()->toArray())
        );
    }
}