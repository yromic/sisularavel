<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

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
            'attachment' => [
                'sometimes',
                'file',
                'max:2048',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png',
            ],
            'photos' => [
                'sometimes',
                'array',
                'max:15',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_array($value)) {
                        $totalBytes = 0;
                        foreach ($value as $file) {
                            if ($file instanceof UploadedFile) {
                                $totalBytes += $file->getSize() ?: 0;
                            }
                        }
                        if ($totalBytes > 5 * 1024 * 1024) {
                            $fail('Total ukuran seluruh foto lampiran tidak boleh melebihi 5 MB.');
                        }
                    }
                },
            ],
            'photos.*' => ['file', 'image', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_surat' => $this->nomor_surat ?? $this->letter_number,
            'asal_surat' => $this->asal_surat ?? $this->origin_agency,
            'perihal' => $this->perihal ?? $this->subject,
            'tanggal_surat' => $this->tanggal_surat ?? $this->letter_date,
            'tanggal_diterima' => $this->tanggal_diterima ?? $this->received_date,
        ]);

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
            'attachment.mimetypes' => 'Berkas harus berupa PDF, Word, atau Gambar dengan ukuran maksimal 2 MB.',
            'attachment.max' => 'Ukuran berkas lampiran utama tidak boleh melebihi 2 MB.',
            'photos.*.max' => 'Ukuran masing-masing lembar foto tidak boleh melebihi 2 MB.',
            'photos.*.image' => 'Masing-masing berkas foto harus berupa gambar valid.',
        ];
    }
}
