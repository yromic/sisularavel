<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePiagamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:150'],
            'npsn' => ['required', 'digits:8', 'exists:schools,npsn'],
            'competition_category_id' => ['required', 'integer', 'exists:competition_categories,id'],
            'competition_year' => ['required', 'integer', 'between:1900,2100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_name' => is_string($this->student_name) ? trim($this->student_name) : $this->student_name,
            'npsn' => is_string($this->npsn) ? preg_replace('/\D+/', '', $this->npsn) : $this->npsn,
        ]);
    }
}
