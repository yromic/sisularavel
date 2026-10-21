<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxIds = config('bulk.max_ids', 200);

        return [
            'ids' => ['required_without:filter', 'array', 'min:1', "max:{$maxIds}"],
            'ids.*' => ['integer', 'distinct'],
            'filter' => ['required_without:ids', 'array'],
            'expected_count' => ['required_with:filter', 'integer', 'min:1'],
            'confirm_phrase' => ['nullable', 'string'],
            'action' => ['nullable', 'string', 'in:delete,restore'],
            'is_restore' => ['nullable', 'boolean'],
            'preview' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxIds = config('bulk.max_ids', 200);

        return [
            'ids.required_without' => 'Pilih setidaknya satu item untuk diproses atau tentukan filter.',
            'ids.array' => 'Daftar ID harus berupa array.',
            'ids.min' => 'Pilih setidaknya satu ID item.',
            'ids.max' => "Jumlah item yang dipilih tidak boleh melebihi {$maxIds} dalam satu permintaan.",
            'ids.*.integer' => 'Setiap ID item harus berupa bilangan bulat.',
            'ids.*.distinct' => 'Daftar ID item tidak boleh memiliki duplikasi.',
            'filter.required_without' => 'Filter pencarian wajib diisi jika tidak memilih ID spesifik.',
            'expected_count.required_with' => 'Perkiraan jumlah item (expected_count) wajib disertakan saat menggunakan filter seleksi massal.',
            'expected_count.min' => 'Perkiraan jumlah item harus minimal 1.',
        ];
    }

    public function isPreview(): bool
    {
        return str_ends_with($this->path(), '/preview') || $this->boolean('preview');
    }

    public function isRestore(): bool
    {
        return str_contains($this->path(), 'restore')
            || $this->boolean('is_restore')
            || $this->input('action') === 'restore';
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->isPreview() || $this->isRestore()) {
                return;
            }

            $threshold = (int) config('bulk.confirm_threshold', 50);
            $count = is_array($this->input('ids'))
                ? count($this->input('ids'))
                : (int) $this->input('expected_count', 0);

            if ($count >= $threshold) {
                $expectedPhrase = "HAPUS {$count}";
                $actualPhrase = trim((string) $this->input('confirm_phrase', ''));

                if ($actualPhrase !== $expectedPhrase) {
                    $validator->errors()->add(
                        'confirm_phrase',
                        "Untuk menghapus {$count} data sekaligus, Anda wajib mengetik konfirmasi tepat: '{$expectedPhrase}'."
                    );
                }
            }
        });
    }
}
