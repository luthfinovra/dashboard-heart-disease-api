<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseJson;
use App\Models\Disease;
use Illuminate\Support\Facades\Log;

class CreateDiseaseRecordRequest extends FormRequest
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
            'diseaseId' => $this->route('diseaseId')
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
        ];

        if ($this->filled('diseaseId')) {
            try {
                $disease = Disease::findOrFail($this->route('diseaseId'));
                
                if ($disease && isset($disease->schema['columns'])){
                    foreach ($disease->schema['columns'] as $column) {
                        $columnName = $column['name'];
                        $columnRules = [];
        
                        // Base validation based on type
                        switch ($column['type']) {
                            case 'string':
                            case 'text':
                                $columnRules[] = 'required|string';
                                break;
                            case 'integer':
                                $columnRules[] = 'required|integer';
                                break;
                            case 'decimal':
                            case 'float':
                                $columnRules[] = 'required|numeric';
                                break;
                            case 'datetime':
                            case 'date':
                                $columnRules[] = 'required|date';
                                break;
                            case 'time':
                                $columnRules[] = 'required|date_format:H:i:s';
                                break;
                            case 'file':
                                $columnRules[] = 'required';
                                
                                if (!empty($column['multiple'])) {
                                    $rules[$columnName] = 'array';
                                    
                                    $fileRules = ['file'];
                                    
                                    if (!empty($column['format'])) {
                                        $formats = explode(',', trim($column['format'], '.'));
                                        $formats = array_map(fn($format) => ltrim($format, '.'), $formats);
                                        $fileRules[] = 'mimes:' . implode(',', $formats);
                                    }
                                    
                                    $rules[$columnName . '.*'] = implode('|', $fileRules);
                                } else {
                                    $columnRules[] = 'file';
                                    
                                    if (!empty($column['format'])) {
                                        $formats = explode(',', trim($column['format'], '.'));
                                        $formats = array_map(fn($format) => ltrim($format, '.'), $formats);
                                        $columnRules[] = 'mimes:' . implode(',', $formats);
                                    }
                                    
                                    $rules[$columnName] = implode('|', $columnRules);
                                }
                                break;
                            case 'boolean':
                                $columnRules[] = 'required|boolean';
                                break;
                            case 'enum':
                                if (!empty($column['options']) && is_array($column['options'])) {
                                    $columnRules[] = 'required|string';
                                    $columnRules[] = 'in:' . implode(',', $column['options']);
                                }
                                break;
                            case 'email':
                                $columnRules[] = 'required|email';
                                break;
                            case 'phone':
                                $columnRules[] = 'required|string';
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
            ->except('diseaseId')
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


// public function messages(): array
// {
//     return [
//         'diseaseId.required' => 'ID penyakit harus diisi.',
//         'diseaseId.integer' => 'ID penyakit harus berupa angka.',
//         'diseaseId.exists' => 'ID penyakit tidak ditemukan dalam database.',

//         'data.*.required' => ':attribute harus diisi.',
//         'data.*.string' => ':attribute harus berupa teks.',
//         'data.*.integer' => ':attribute harus berupa angka bulat.',
//         'data.*.numeric' => ':attribute harus berupa angka.',
//         'data.*.date' => ':attribute harus berupa tanggal yang valid.',
//         'data.*.date_format' => ':attribute harus dalam format waktu yang valid (HH:MM:SS).',
//         'data.*.boolean' => ':attribute harus berupa benar atau salah.',
//         'data.*.in' => ':attribute harus salah satu dari pilihan yang tersedia: :values.',
//         'data.*.email' => ':attribute harus berupa alamat email yang valid.',
//         'data.*.regex' => ':attribute harus berupa nomor telepon yang valid.',

//         // 'data.*.file' => ':attribute harus berupa file.',
//         // 'data.*.mimes' => ':attribute harus bertipe: :values.',

//         // 'data.*.array' => ':attribute harus dalam format array jika berisi banyak file.',
//         // Uncommented additional validation for required/nullable
//         // 'data.*.nullable' => ':attribute boleh dikosongkan.',
//         // Rules for minimum and maximum values
//         // 'data.*.min' => ':attribute harus minimal :min.',
//         // 'data.*.min_digits' => ':attribute harus minimal :min digit.',
//         // 'data.*.max' => ':attribute maksimal :max.',
//         // 'data.*.max_digits' => ':attribute maksimal :max digit.',
//     ];
// }