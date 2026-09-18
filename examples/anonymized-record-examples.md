# ANONYMIZED RECORD EXAMPLES

> [!NOTE]
> All records in this document are strictly synthetic and illustrative. Names, school details, letter numbers, and contact credentials have been randomized and anonymized. No real personal data or production records are contained herein.

---

## 1. Division Entity Example (`divisions`)

```json
{
  "id": 1,
  "code": "DIKDAS",
  "name": "Bidang Pembinaan Sekolah Dasar",
  "status": "active",
  "legacy_drive_folder_id": "1A2B3C4D5E6F7G8H9I0J_REDACTED",
  "created_at": "2026-01-10 08:00:00",
  "updated_at": "2026-01-10 08:00:00"
}
```

---

## 2. User Entity Example (`users`)

```json
{
  "id": 101,
  "username": "op_dikdas01",
  "name": "Ahmad Fauzi",
  "email": "ahmad.fauzi@example.go.id",
  "role": "operator",
  "division_id": 1,
  "is_active": true,
  "must_change_password": false,
  "created_at": "2026-01-15 09:30:00",
  "updated_at": "2026-01-15 09:30:00"
}
```

---

## 3. Incoming Letter Entity Example (`incoming_letters`)

```json
{
  "id": 5001,
  "division_id": 1,
  "nomor_surat": "421.2/085/DISDIK/2026",
  "asal_surat": "Badan Kepegawaian Daerah",
  "tanggal_surat": "2026-02-14",
  "tanggal_diterima": "2026-02-16",
  "perihal": "Undangan Sosialisasi Penilaian Kinerja Guru SD Tahun 2026",
  "created_by": 101,
  "created_at": "2026-02-16 10:15:22",
  "updated_at": "2026-02-16 10:15:22",
  "deleted_at": null,
  "deleted_by": null
}
```

---

## 4. Outgoing Letter Entity Example (`outgoing_letters`)

```json
{
  "id": 6001,
  "division_id": 1,
  "nomor_surat": "005/112/DIKDAS/2026",
  "tanggal_surat": "2026-02-20",
  "tanggal_share": "2026-02-21",
  "perihal": "Pemberitahuan Pelaksanaan Olimpiade Sains Nasional Tingkat Kecamatan",
  "created_by": 101,
  "created_at": "2026-02-20 14:00:00",
  "updated_at": "2026-02-20 14:00:00",
  "deleted_at": null
}
```

---

## 5. Attachment Entity Example (`attachments`)

```json
{
  "id": 9001,
  "uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "division_id": 1,
  "attachable_type": "App\\Models\\IncomingLetter",
  "attachable_id": 5001,
  "file_path": "documents/DIKDAS/2026/02/7c9e6679-7425-40de-944b-e07fc1f90ae7.pdf",
  "original_name": "surat_undangan_bkd_2026.pdf",
  "file_size_bytes": 1548291,
  "mime_type": "application/pdf",
  "file_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "legacy_drive_file_id": "1V_REDACTED_DRIVE_ID",
  "created_by": 101,
  "created_at": "2026-02-16 10:15:22"
}
```

---

## 6. Piagam Record Example (`piagams`)

```json
{
  "id": 8001,
  "division_id": 1,
  "student_name": "Siti Rahmawati",
  "school_id": 204,
  "competition_category_id": 3,
  "competition_year": 2026,
  "pickup_category_id": 1,
  "recipient_name": "Budi Santoso, S.Pd.",
  "recipient_position": "Guru Pendamping",
  "recipient_unit": "SD Negeri Karangjati 01",
  "created_by": 101,
  "created_at": "2026-03-05 11:20:00"
}
```

---

## 7. Common Legacy Transformation Examples

| Legacy Input Value | Legacy Column | Normalized Target Column | Target Normalized Value |
| :--- | :--- | :--- | :--- |
| `"15/02/2026"` | `tanggal_surat` | `tanggal_surat` | `'2026-02-15'` |
| `" 421 / 012 / SD "` | `nomor_surat` | `nomor_surat` | `'421/012/SD'` |
| `"AKTIF"` | `aktif` | `is_active` | `true` (1) |
| `""` (Empty string) | `tanggal_share` | `tanggal_share` | `null` |
| `"2032025"` (7 digits) | `npsn` | `npsn` | `'02032025'` (Padded to 8 digits) |
| `"https://drive.google.com/uc?id=1AbCdEfGhIjKlMn"` | `upload_file` | `legacy_drive_file_id` | `'1AbCdEfGhIjKlMn'` |
