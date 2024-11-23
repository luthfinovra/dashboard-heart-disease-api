<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditCommentRequest extends FormRequest
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
            'commentId' => $this->route('commentId')
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
            'commentId' => 'required|integer|exists:comments,id',
            'content' => 'required|string|max:1000',
        ];
    }
}
