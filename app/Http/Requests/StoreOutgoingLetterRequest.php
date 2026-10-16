<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

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
            'attachment.mimetypes' => 'Berkas harus berupa PDF, Word, atau Gambar dengan ukuran maksimal 2 MB.',
            'attachment.max' => 'Ukuran berkas lampiran utama tidak boleh melebihi 2 MB.',
            'photos.*.max' => 'Ukuran masing-masing lembar foto tidak boleh melebihi 2 MB.',
            'photos.*.image' => 'Masing-masing berkas foto harus berupa gambar valid.',
        ];
    }
}
