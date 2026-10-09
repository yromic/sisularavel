<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutgoingLetterRequest extends FormRequest
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
            'perihal' => ['required', 'string', 'max:1000'],
            'tanggal_surat' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tanggal_share' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_surat'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_surat' => $this->nomor_surat ?? $this->letter_number,
            'perihal' => $this->perihal ?? $this->subject,
            'tanggal_surat' => $this->tanggal_surat ?? $this->letter_date,
            'tanggal_share' => $this->tanggal_share ?? $this->disseminated_date ?? $this->tanggal_diseminasi,
        ]);

        $this->merge([
            'nomor_surat' => is_string($this->nomor_surat) ? trim($this->nomor_surat) : $this->nomor_surat,
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
            'perihal.required' => 'Perihal surat wajib diisi (maks. 1000 karakter).',
            'perihal.max' => 'Perihal surat wajib diisi (maks. 1000 karakter).',
            'tanggal_surat.before_or_equal' => 'Tanggal surat tidak boleh tanggal masa depan.',
            'tanggal_share.after_or_equal' => 'Tanggal di-share tidak boleh mendahului tanggal surat.',
        ];
    }
}
