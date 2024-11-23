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

        $mimeTypeMap = [
            'audio' => [
                'aac', 'midi', 'mp3', 'ogg', 'wav', 'webm', 'flac', 'aiff', 'amr', 'opus'
            ],
            'video' => [
                'mp4', 'avi', 'mkv', 'webm', 'ogg', '3gp', 'flv', 'mov', 
                'wmv', 'mpg', 'mpeg', 'm4v', 'h264', 'hevc'
            ],
            'image' => [
                'jpeg', 'jpg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg', 'heif', 'heic', 
                'ico', 'jp2', 'j2k', 'avif'
            ],
            'text-document' => [
                'pdf', 'doc', 'docx', 'xml', 'json', 'html', 'txt', 'rtf', 'odt'
            ],
            'compressed-document' => [
                'zip', '7z', 'tar', 'gz', 'rar', 'bz2', 'xz'
            ],
            'spreadsheet' => [
                'xls', 'xlsx', 'csv', 'ods'
            ],
        ];
        
        
        
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
                                    // Multiple file upload handling
                                    $rules[$columnName] = 'required|array|min:1'; 
                                
                                    $fileRules = ['file'];
                                    
                                    if (!empty($column['format'])) {
                                        $category = trim($column['format'], '.');
                                        if (array_key_exists($category, $mimeTypeMap)) {
                                            $formats = $mimeTypeMap[$category];
                                            $fileRules[] = 'mimes:' . implode(',', $formats);
                                        } else {
                                            Log::warning("Unsupported format '{$column['format']}' for column '{$columnName}'");
                                        }
                                    }
                                    $rules[$columnName . '.*'] = implode('|', $fileRules);
                                    // print_r($rules);
                                } else {
                                    // Single file upload handling.
                                    $columnRules[] = 'required|file';
                                    
                                    if (!empty($column['format'])) {
                                        $category = trim($column['format'], '.');
                                        if (array_key_exists($category, $mimeTypeMap)) {
                                            $formats = $mimeTypeMap[$category];
                                            $columnRules[] = 'mimes:' . implode(',', $formats);
                                        } else {
                                            Log::warning("Unsupported format '{$column['format']}' for column '{$columnName}'");
                                        }
                                    }
                                
                                    $rules[$columnName] = implode('|', $columnRules);
                                    // print_r($rules);
                                }
                                
                                // print_r($columnRules);
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