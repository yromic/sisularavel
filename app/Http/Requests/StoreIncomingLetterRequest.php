<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomingLetterRequest extends FormRequest
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
            'nomor_surat' => ['required', 'string', 'max:100'],
            'asal_surat' => ['required', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:1000'],
            'tanggal_surat' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tanggal_diterima' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_surat'],
            'confirm_duplicate' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_surat' => is_string($this->nomor_surat) ? trim($this->nomor_surat) : $this->nomor_surat,
            'asal_surat' => is_string($this->asal_surat) ? trim($this->asal_surat) : $this->asal_surat,
            'perihal' => is_string($this->perihal) ? trim($this->perihal) : $this->perihal,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_surat.required' => 'Nomor surat wajib diisi (maks. 100 karakter).',
            'nomor_surat.max' => 'Nomor surat wajib diisi (maks. 100 karakter).',
            'asal_surat.required' => 'Asal surat wajib diisi (maks. 255 karakter).',
            'asal_surat.max' => 'Asal surat wajib diisi (maks. 255 karakter).',
            'perihal.required' => 'Perihal surat wajib diisi (maks. 1000 karakter).',
            'perihal.max' => 'Perihal surat wajib diisi (maks. 1000 karakter).',
            'tanggal_surat.before_or_equal' => 'Tanggal surat tidak boleh tanggal masa depan.',
            'tanggal_diterima.after_or_equal' => 'Tanggal diterima tidak boleh lebih awal dari tanggal surat.',
        ];
    }
}
