# 09. VALIDATION CONTRACT

## 1. Validation Philosophy & Directives

1. **Server-Side Enforcement:** Validation rules are executed in dedicated Laravel `FormRequest` classes prior to controller dispatch. Client-side validation is strictly an ergonomics aid; backend validation is the definitive security gate.
2. **Standardized Response Envelope:** Validation failures return HTTP 422 Unprocessable Content matching `14-ERROR-HANDLING-CONTRACT.md`:
   ```json
   {
     "success": false,
     "code": "ERR_422_VALIDATION",
     "message": "Data yang dikirimkan tidak valid.",
     "errors": {
       "nomor_surat": ["Nomor surat keluar sudah terdaftar pada divisi dan tahun ini."]
     },
     "timestamp": "2026-09-18T12:00:00Z"
   }
   ```
3. **Data Normalization Before Validation:**
   - Trim leading and trailing whitespace on all text strings.
   - Strip formula prefix characters (`=`, `+`, `-`, `@`) from user-entered text.
   - Clean non-digit characters from numerical codes (e.g., NPSN).

---

## 2. Field-Level Validation Specifications

### 2.1 Request: `StoreIncomingLetterRequest` & `UpdateIncomingLetterRequest`
* **Target Table:** `incoming_letters`, `attachments`
* **Duplicate Detection Contract:** The field `nomor_surat` alone is NOT validated with a unique rule, as external sending agencies legitimately generate colliding numbers. Duplicate advisory checking is performed separately against `(division_id, asal_surat, nomor_surat, tanggal_surat)`.

| Field | Label | Required? | Type / Rules | Normalization | Error Message |
|---|---|:---:|---|---|---|
| `nomor_surat` | Nomor Surat | **Yes** | `string|max:100` | Trim | *"Nomor surat wajib diisi (maks. 100 karakter)."* |
| `asal_surat` | Asal Surat | **Yes** | `string|max:255` | Trim | *"Asal surat wajib diisi (maks. 255 karakter)."* |
| `perihal` | Perihal | **Yes** | `string|max:1000` | Trim | *"Perihal surat wajib diisi (maks. 1000 karakter)."* |
| `tanggal_surat`| Tanggal Surat | **Yes** | `date|date_format:Y-m-d|before_or_equal:today` | Format `YYYY-MM-DD` | *"Tanggal surat tidak boleh tanggal masa depan."* |
| `tanggal_diterima`| Tanggal Diterima | **Yes** | `date|date_format:Y-m-d|after_or_equal:tanggal_surat` | Format `YYYY-MM-DD` | *"Tanggal diterima tidak boleh lebih awal dari tanggal surat."* |
| `attachment` | Berkas Lampiran| Optional | `file|mimetypes:pdf,doc,docx,jpg,jpeg,png|max:2048` | Binary file | *"Berkas harus berupa PDF, Word, atau Gambar dengan ukuran maksimal 2 MB."* |
| `photos` | Foto Kamera Multi-Lembar | Optional | `array|max:15|cumulative_bytes<=5120KB` | Array of images | *"Total ukuran seluruh foto lampiran tidak boleh melebihi 5 MB."* |
| `photos.*` | Lembar Foto Kamera | Optional | `file|image|max:2048` | Binary image | *"Ukuran masing-masing lembar foto tidak boleh melebihi 2 MB."* |

---

### 2.2 Request: `StoreOutgoingLetterRequest` & `UpdateOutgoingLetterRequest`
* **Target Table:** `outgoing_letters`, `attachments`
* **Uniqueness Rule:** Must be unique per issuing division and calendar year (`letter_year`).

| Field | Label | Required? | Type / Rules | Normalization | Error Message |
|---|---|:---:|---|---|---|
| `nomor_surat` | Nomor Surat | **Yes** | `string|max:100|unique_outgoing_number` | Trim | *"Nomor surat keluar sudah terdaftar pada divisi dan tahun ini."* |
| `perihal` | Perihal | **Yes** | `string|max:1000` | Trim | *"Perihal surat wajib diisi (maks. 1000 karakter)."* |
| `tanggal_surat`| Tanggal Surat | **Yes** | `date|date_format:Y-m-d|before_or_equal:today` | Format `YYYY-MM-DD` | *"Tanggal surat tidak boleh tanggal masa depan."* |
| `tanggal_share`| Tanggal Disahkan/Share | **Yes** | `date|date_format:Y-m-d|after_or_equal:tanggal_surat` | Format `YYYY-MM-DD` | *"Tanggal di-share tidak boleh mendahului tanggal surat."* |
| `attachment` | Berkas Lampiran| Optional | `file|mimetypes:pdf,doc,docx,jpg,jpeg,png|max:2048` | Binary file | *"Berkas harus berupa PDF, Word, atau Gambar dengan ukuran maksimal 2 MB."* |
| `photos` | Foto Kamera Multi-Lembar | Optional | `array|max:15|cumulative_bytes<=5120KB` | Array of images | *"Total ukuran seluruh foto lampiran tidak boleh melebihi 5 MB."* |
| `photos.*` | Lembar Foto Kamera | Optional | `file|image|max:2048` | Binary image | *"Ukuran masing-masing lembar foto tidak boleh melebihi 2 MB."* |

---

### 2.3 Request: `PublicClaimPiagamRequest`
* **Target Table:** `piagams`, `attachments`

| Field | Label | Required? | Type / Rules | Normalization | Error Message |
|---|---|:---:|---|---|---|
| `piagam_id` | ID Piagam | **Yes** | `integer|exists:piagams,id` | Integer | *"Sertifikat piagam tidak valid."* |
| `npsn` | NPSN Sekolah | **Yes** | `digits:8|exists:schools,npsn` | Clean non-digits | *"NPSN sekolah harus 8 digit dan terdaftar."* |
| `recipient_name`| Nama Pengambil | **Yes** | `string|max:150` | Trim | *"Nama pengambil wajib diisi."* |
| `recipient_position`| Jabatan Pengambil | **Yes** | `string|max:100` | Trim | *"Jabatan pengambil wajib diisi."* |
| `recipient_unit` | Unit Kerja | **Yes** | `string|max:150` | Trim | *"Unit kerja wajib diisi."* |
| `signature_canvas` | Tanda Tangan | **Yes** | `string|starts_with:data:image/` | Base64 string | *"Tanda tangan digital wajib dibubuhkan."* |

---

### 2.4 Header Validation: `Idempotency-Key`
* **Applies to:** `POST /api/v1/surat-masuk`, `POST /api/v1/surat-keluar`, `POST /api/v1/piagam`, `POST /api/v1/piagam/ambil`
* **Rule:** `header:Idempotency-Key|uuid`
* **Error Message:** *"Header Idempotency-Key harus berupa format UUID v4 valid."*
