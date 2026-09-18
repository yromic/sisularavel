<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicClaimPiagamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'piagam_id' => ['required', 'integer', 'exists:piagams,id'],
            'npsn' => ['required', 'digits:8', 'exists:schools,npsn'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_position' => ['required', 'string', 'max:100'],
            'recipient_unit' => ['required', 'string', 'max:150'],
            'signature_canvas' => ['required', 'string', 'starts_with:data:image/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'npsn' => is_string($this->npsn) ? preg_replace('/\D+/', '', $this->npsn) : $this->npsn,
            'recipient_name' => is_string($this->recipient_name) ? trim($this->recipient_name) : $this->recipient_name,
            'recipient_position' => is_string($this->recipient_position) ? trim($this->recipient_position) : $this->recipient_position,
            'recipient_unit' => is_string($this->recipient_unit) ? trim($this->recipient_unit) : $this->recipient_unit,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature_canvas.required' => 'Tanda tangan digital wajib dibubuhkan.',
            'signature_canvas.starts_with' => 'Tanda tangan digital wajib dibubuhkan.',
            'npsn.exists' => 'NPSN sekolah harus 8 digit dan terdaftar.',
        ];
    }
}
