<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;
use App\Models\Disease;
use Illuminate\Support\Facades\Log;

class EditDiseaseRecordRequest extends FormRequest
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
            'recordId' => $this->route('recordId')
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
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

        if ($this->filled('diseaseId')) {
            try {
                $disease = Disease::findOrFail($this->route('diseaseId'));
                if ($disease && isset($disease->schema['columns'])) {

                    foreach ($disease->schema['columns'] as $column) {
                        $columnName = $column['name'];
                        $columnRules = [];

                        switch ($column['type']) {
                            case 'string':
                            case 'text':
                                $columnRules[] = 'sometimes|required|string';
                                break;
                            case 'integer':
                                $columnRules[] = 'sometimes|required|integer';
                                break;
                            case 'decimal':
                            case 'float':
                                $columnRules[] = 'sometimes|required|numeric';
                                break;
                            case 'datetime':
                            case 'date':
                                $columnRules[] = 'sometimes|required|date';
                                break;
                            case 'time':
                                $columnRules[] = 'sometimes|required|date_format:H:i:s';
                                break;
                            case 'file':
                                if (!empty($column['multiple'])) {
                                    // For multiple files
                                    $rules[$columnName] = 'sometimes|required|array';
                                    
                                    $fileRules = ['file'];
                                    if (!empty($column['format'])) {
                                        $formats = explode(',', trim($column['format'], '.'));
                                        $formats = array_map(fn($format) => ltrim($format, '.'), $formats);
                                        $fileRules[] = 'mimes:' . implode(',', $formats);
                                    }
                                    
                                    $rules[$columnName . '.*'] = implode('|', $fileRules);
                                } else {
                                    // For single file
                                    $fileRules = ['sometimes', 'required', 'file'];
                                    if (!empty($column['format'])) {
                                        $formats = explode(',', trim($column['format'], '.'));
                                        $formats = array_map(fn($format) => ltrim($format, '.'), $formats);
                                        $fileRules[] = 'mimes:' . implode(',', $formats);
                                    }
                                    $rules[$columnName] = implode('|', $fileRules);
                                }
                                break;
                            case 'boolean':
                                $columnRules[] = 'sometimes|required|boolean';
                                break;
                            case 'enum':
                                if (!empty($column['options']) && is_array($column['options'])) {
                                    $columnRules[] = 'sometimes|required|string';
                                    $columnRules[] = 'in:' . implode(',', $column['options']);
                                }
                                break;
                            case 'email':
                                $columnRules[] = 'sometimes|required|email';
                                break;
                            case 'phone':
                                $columnRules[] = 'sometimes|required|string';
                                $columnRules[] = 'regex:/^([0-9\s\-\+\(\)]*)$/';
                                break;
                            }
            
                            // // Add required/nullable validation
                            // if (!empty($column['required'])) {
                            //     $columnRules[] = 'required';
                            // } else {
                            //     $columnRules[] = 'nullable';
                            // }
            
                            // // Add min/max validation if specified
                            // if (!empty($column['min'])) {
                            //     switch ($column['type']) {
                            //         case 'string':
                            //         case 'text':
                            //             $columnRules[] = 'min:' . $column['min'];
                            //             break;
                            //         case 'integer':
                            //         case 'decimal':
                            //         case 'float':
                            //             $columnRules[] = 'min_digits:' . $column['min'];
                            //             break;
                            //         case 'file':
                            //             $columnRules[] = 'min:' . $column['min']; // min in kilobytes
                            //             break;
                            //     }
                            // }
            
                            // if (!empty($column['max'])) {
                            //     switch ($column['type']) {
                            //         case 'string':
                            //         case 'text':
                            //             $columnRules[] = 'max:' . $column['max'];
                            //             break;
                            //         case 'integer':
                            //         case 'decimal':
                            //         case 'float':
                            //             $columnRules[] = 'max_digits:' . $column['max'];
                            //             break;
                            //         case 'file':
                            //             $columnRules[] = 'max:' . $column['max']; // max in kilobytes
                            //             break;
                            //     }
                            // }
            
                            if (!empty($columnRules)) {
                                $rules[$columnName] = implode('|', $columnRules);
                            }
                        }
                    }
                }  catch (\Throwable $e) {
                    Log::error('Error loading disease schema: ' . $e->getMessage());
                }
            }
    
            return $rules;
        }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();
        
        return [
            'diseaseId' => $validated['diseaseId'],
            'data' => collect($validated)
            ->except('diseaseId', 'recordId')
            ->toArray()
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseJson::failedResponse('Validation error', $validator->errors()->toArray())
        );
    }
}
