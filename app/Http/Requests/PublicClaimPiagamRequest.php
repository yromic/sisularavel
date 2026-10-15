<?php

namespace App\Http\Requests;

use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicClaimPiagamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Backward-compatible legacy pre-registration mode
        if ($this->filled('piagam_id')) {
            return [
                'piagam_id' => ['required', 'integer', 'exists:piagams,id'],
                'npsn' => ['required', 'digits:8', 'exists:schools,npsn'],
                'recipient_name' => ['required', 'string', 'max:150'],
                'recipient_position' => ['required', 'string', 'max:100'],
                'recipient_unit' => ['required', 'string', 'max:150'],
                'signature_canvas' => ['required', 'string', 'starts_with:data:image/'],
            ];
        }

        // Enhanced Single-Action Handover Receipt mode
        return [
            'division_id' => [
                'required',
                'integer',
                Rule::exists('divisions', 'id')->where('status', 'active'),
            ],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_position' => ['required', 'string', 'max:100'],
            'recipient_unit' => ['required', 'string', 'max:150'],
            'school_origin' => ['required', 'string', 'max:255'],
            'school_id' => [
                'nullable',
                'integer',
                Rule::exists('schools', 'id')->where('is_active', true),
            ],
            'work_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('work_units', 'id')->where('is_active', true),
            ],
            'npsn' => ['nullable', 'string', 'max:20'],
            'pickup_category_id' => [
                'nullable',
                'integer',
                Rule::exists('pickup_categories', 'id')->where('is_active', true),
            ],
            'pickup_category_name' => ['nullable', 'string', 'max:150'],
            'competition_category_id' => [
                'nullable',
                'integer',
                Rule::exists('competition_categories', 'id')->where('is_active', true),
            ],
            'competition_category_name' => ['nullable', 'string', 'max:150'],
            'competition_year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'student_name' => ['required', 'string', 'max:150'],
            'piagam_description' => ['required', 'string', 'max:255'],
            'signature_canvas' => ['required', 'string', 'starts_with:data:image/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('piagam_id')) {
            $this->merge([
                'npsn' => is_string($this->npsn) ? preg_replace('/\D+/', '', $this->npsn) : $this->npsn,
                'recipient_name' => is_string($this->recipient_name) ? trim($this->recipient_name) : $this->recipient_name,
                'recipient_position' => is_string($this->recipient_position) ? trim($this->recipient_position) : $this->recipient_position,
                'recipient_unit' => is_string($this->recipient_unit) ? trim($this->recipient_unit) : $this->recipient_unit,
            ]);

            return;
        }

        // 1. Resolve Division Tujuan (Dropdown or Manual with unique official match)
        $divisionId = $this->input('division_id');
        $divisionManual = trim((string) $this->input('division_manual'));
        if ((! $divisionId || $divisionId === 'manual') && $divisionManual !== '') {
            $normalized = strtolower($divisionManual);
            $matches = Division::query()
                ->where('status', 'active')
                ->where(function ($q) use ($normalized) {
                    $q->whereRaw('LOWER(code) = ?', [$normalized])
                        ->orWhereRaw('LOWER(name) = ?', [$normalized])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$normalized}%"]);
                })
                ->get();

            if ($matches->count() === 1) {
                $divisionId = $matches->first()->id;
            } else {
                // Ambiguous or not found: leave invalid so rule fails
                $divisionId = -1;
            }
        } elseif ($divisionId === 'manual' && $divisionManual === '') {
            $divisionId = null;
        }

        // 2. Resolve Unit Kerja / Instansi
        $workUnitSelection = (string) $this->input('work_unit_selection');
        $schoolId = $this->input('school_id');
        $workUnitId = $this->input('work_unit_id');
        $recipientUnitManual = trim((string) $this->input('recipient_unit_manual'));
        $npsn = trim((string) $this->input('npsn'));

        if (str_starts_with($workUnitSelection, 'school:')) {
            $schoolId = (int) substr($workUnitSelection, 7);
        } elseif (str_starts_with($workUnitSelection, 'work_unit:')) {
            $workUnitId = (int) substr($workUnitSelection, 10);
        }

        $unitLabel = $recipientUnitManual ?: ($this->input('recipient_unit') ?: $this->input('school_origin'));

        if ($schoolId) {
            $school = School::query()->where('is_active', true)->find($schoolId);
            if ($school) {
                if (! $unitLabel || $workUnitSelection === 'school:'.$schoolId) {
                    $unitLabel = $school->name;
                }
                if ($npsn === '') {
                    $npsn = $school->npsn;
                }
            }
        } elseif ($workUnitId) {
            $workUnit = WorkUnit::query()->where('is_active', true)->find($workUnitId);
            if ($workUnit && (! $unitLabel || $workUnitSelection === 'work_unit:'.$workUnitId)) {
                $unitLabel = $workUnit->name;
            }
        }

        // 3. Resolve Jenis Pengambilan
        $pickupCatId = $this->input('pickup_category_id');
        $pickupCatManual = trim((string) $this->input('pickup_category_manual'));
        $pickupCatName = null;

        if ($pickupCatId === 'manual' || (! $pickupCatId && $pickupCatManual !== '')) {
            $pickupCatId = null;
            $pickupCatName = $pickupCatManual;
        } elseif ($pickupCatId && is_numeric($pickupCatId)) {
            $pickupCat = PickupCategory::query()->where('is_active', true)->find($pickupCatId);
            $pickupCatName = $pickupCat?->name;
        }

        if (! $pickupCatName && ! $pickupCatId) {
            $pickupCatName = 'Diambil Langsung';
        }

        // 4. Resolve Kelas Kompetisi
        $compCatId = $this->input('competition_category_id');
        $compCatManual = trim((string) $this->input('competition_category_manual'));
        $compCatName = null;

        if ($compCatId === 'manual' || (! $compCatId && $compCatManual !== '')) {
            $compCatId = null;
            $compCatName = $compCatManual;
        } elseif ($compCatId && is_numeric($compCatId)) {
            $compCat = CompetitionCategory::query()->where('is_active', true)->find($compCatId);
            $compCatName = $compCat?->name;
        }

        // 5. Resolve Siswa & Deskripsi Piagam
        $studentName = trim((string) ($this->input('student_name') ?: $this->input('piagam_description')));
        $piagamDesc = trim((string) ($this->input('piagam_description') ?: ($compCatName ? "{$compCatName} – {$studentName}" : $studentName)));

        $this->merge([
            'division_id' => $divisionId ? (int) $divisionId : null,
            'school_id' => $schoolId ? (int) $schoolId : null,
            'work_unit_id' => $workUnitId ? (int) $workUnitId : null,
            'recipient_unit' => is_string($unitLabel) ? trim($unitLabel) : $unitLabel,
            'school_origin' => is_string($unitLabel) ? trim($unitLabel) : $unitLabel,
            'npsn' => $npsn !== '' ? $npsn : null,
            'pickup_category_id' => $pickupCatId ? (int) $pickupCatId : null,
            'pickup_category_name' => $pickupCatName,
            'competition_category_id' => $compCatId ? (int) $compCatId : null,
            'competition_category_name' => $compCatName,
            'competition_year' => (int) ($this->input('competition_year') ?: now()->year),
            'student_name' => $studentName,
            'piagam_description' => $piagamDesc,
            'recipient_name' => is_string($this->recipient_name) ? trim($this->recipient_name) : $this->recipient_name,
            'recipient_position' => is_string($this->recipient_position) ? trim($this->recipient_position) : $this->recipient_position,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'division_id.required' => 'Divisi tujuan wajib dipilih dari bidang resmi dinas.',
            'division_id.exists' => 'Divisi tujuan tidak dikenali atau tidak aktif. Silakan pilih bidang resmi dinas.',
            'recipient_name.required' => 'Nama lengkap pengambil wajib diisi.',
            'recipient_position.required' => 'Posisi atau jabatan pengambil wajib diisi.',
            'recipient_unit.required' => 'Unit kerja atau asal instansi pengambil wajib diisi.',
            'school_origin.required' => 'Asal sekolah atau instansi wajib diisi.',
            'student_name.required' => 'Nama siswa atau kandidat prestasi wajib diisi.',
            'piagam_description.required' => 'Keterangan piagam / prestasi wajib diisi.',
            'competition_year.required' => 'Tahun acara perlombaan wajib diisi.',
            'signature_canvas.required' => 'Tanda tangan digital wajib dibubuhkan.',
            'signature_canvas.starts_with' => 'Tanda tangan digital tidak valid.',
            'pickup_category_id.exists' => 'Jenis pengambilan yang dipilih tidak valid atau tidak aktif.',
            'competition_category_id.exists' => 'Kelas kompetisi yang dipilih tidak valid atau tidak aktif.',
            'school_id.exists' => 'Sekolah yang dipilih tidak valid atau tidak aktif.',
            'work_unit_id.exists' => 'Unit kerja yang dipilih tidak valid atau tidak aktif.',
            'npsn.exists' => 'NPSN sekolah harus 8 digit dan terdaftar.',
            'piagam_id.exists' => 'Data piagam tidak ditemukan.',
        ];
    }
}
