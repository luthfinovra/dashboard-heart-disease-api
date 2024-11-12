<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;
use App\Models\Disease;

class CreateDiseaseRecordRequest extends FormRequest
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
        $diseaseId = $this->route('diseaseId');
        $disease = Disease::findOrFail($diseaseId);

        foreach ($disease->schema['columns'] as $column) {
            $columnName = 'data.' . $column['name'];
            $columnRules = [];

            switch ($column['type']) {
                case 'string':
                    $columnRules[] = 'string';
                    break;
                case 'integer':
                    $columnRules[] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                    $columnRules[] = 'numeric';
                    break;
                case 'file':
                    $columnRules[] = 'file';
                    if (!empty($column['format'])) {
                        $formats = explode(',', trim($column['format'], '.'));
                        $columnRules[] = 'mimes:' . implode(',', $formats);
                    }
                    if (!empty($column['multiple'])) {
                        $columnRules[] = 'array';
                        $columnName .= '.*';
                    }
                    break;
            }

            // MORE
            // if (!empty($column['required'])) {
            //     $columnRules[] = 'required';
            // }
            // if (!empty($column['min'])) {
            //     $columnRules[] = 'min:' . $column['min'];
            // }
            // if (!empty($column['max'])) {
            //     $columnRules[] = 'max:' . $column['max'];
            // }

            $rules[$columnName] = implode('|', $columnRules);
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseJson::failedResponse('Validation error', $validator->errors()->toArray())
        );
    }
}
