<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut memuat pesan kesalahan default yang digunakan oleh
    | kelas validator. Beberapa aturan memiliki beberapa versi seperti aturan ukuran.
    |
    */

    'accepted' => ':Attribute harus diterima.',
    'accepted_if' => ':Attribute harus diterima ketika :other adalah :value.',
    'active_url' => ':Attribute bukan URL yang valid.',
    'after' => ':Attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'array' => ':Attribute harus berupa sebuah array.',
    'ascii' => ':Attribute hanya boleh berisi karakter alfanumerik byte tunggal dan simbol.',
    'before' => ':Attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus memiliki antara :min dan :max anggota.',
        'file' => ':Attribute harus berukuran antara :min dan :max kilobita.',
        'numeric' => ':Attribute harus bernilai antara :min dan :max.',
        'string' => ':Attribute harus berisi antara :min dan :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'can' => ':Attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => ':Attribute tidak memiliki nilai yang diperlukan.',
    'current_password' => 'Kata sandi saat ini salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'date_equals' => ':Attribute harus berisi tanggal yang sama dengan :date.',
    'date_format' => ':Attribute tidak cocok dengan format :format.',
    'decimal' => ':Attribute harus memiliki :decimal tempat desimal.',
    'declined' => ':Attribute harus ditolak.',
    'declined_if' => ':Attribute harus ditolak ketika :other adalah :value.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus terdiri dari :digits digit angka.',
    'digits_between' => ':Attribute harus terdiri dari antara :min dan :max digit angka.',
    'dimensions' => ':Attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => ':Attribute memiliki nilai yang duplikat.',
    'doesnt_end_with' => ':Attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => ':Attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email' => ':Attribute harus berupa alamat surel yang valid.',
    'ends_with' => ':Attribute harus diakhiri salah satu dari berikut: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid atau tidak ditemukan.',
    'extensions' => ':Attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => ':Attribute harus berupa berkas.',
    'filled' => ':Attribute harus memiliki nilai.',
    'gt' => [
        'array' => ':Attribute harus memiliki lebih dari :value anggota.',
        'file' => ':Attribute harus berukuran lebih besar dari :value kilobita.',
        'numeric' => ':Attribute harus bernilai lebih besar dari :value.',
        'string' => ':Attribute harus berisi lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus memiliki :value anggota atau lebih.',
        'file' => ':Attribute harus berukuran lebih besar dari atau sama dengan :value kilobita.',
        'numeric' => ':Attribute harus bernilai lebih besar dari atau sama dengan :value.',
        'string' => ':Attribute harus berisi lebih dari atau sama dengan :value karakter.',
    ],
    'hex_color' => ':Attribute harus berupa kode warna heksadesimal yang valid.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute tidak ada di dalam :other.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'ip' => ':Attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':Attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':Attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':Attribute harus berupa string JSON yang valid.',
    'list' => ':Attribute harus berupa daftar.',
    'lowercase' => ':Attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => ':Attribute harus memiliki kurang dari :value anggota.',
        'file' => ':Attribute harus berukuran kurang dari :value kilobita.',
        'numeric' => ':Attribute harus bernilai kurang dari :value.',
        'string' => ':Attribute harus berisi kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':Attribute tidak boleh memiliki lebih dari :value anggota.',
        'file' => ':Attribute harus berukuran kurang dari atau sama dengan :value kilobita.',
        'numeric' => ':Attribute harus bernilai kurang dari atau sama dengan :value.',
        'string' => ':Attribute harus berisi kurang dari atau sama dengan :value karakter.',
    ],
    'mac_address' => ':Attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':Attribute tidak boleh memiliki lebih dari :max anggota.',
        'file' => ':Attribute tidak boleh berukuran lebih dari :max kilobita.',
        'numeric' => ':Attribute tidak boleh bernilai lebih dari :max.',
        'string' => ':Attribute tidak boleh berisi lebih dari :max karakter.',
    ],
    'max_digits' => ':Attribute tidak boleh memiliki lebih dari :max digit angka.',
    'mimes' => ':Attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => ':Attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => ':Attribute minimal harus memiliki :min anggota.',
        'file' => ':Attribute minimal harus berukuran :min kilobita.',
        'numeric' => ':Attribute minimal harus bernilai :min.',
        'string' => ':Attribute minimal harus berisi :min karakter.',
    ],
    'min_digits' => ':Attribute minimal harus memiliki :min digit angka.',
    'missing' => ':Attribute tidak boleh ada.',
    'missing_if' => ':Attribute tidak boleh ada ketika :other adalah :value.',
    'missing_unless' => ':Attribute tidak boleh ada kecuali :other adalah :value.',
    'missing_with' => ':Attribute tidak boleh ada ketika :values ada.',
    'missing_with_all' => ':Attribute tidak boleh ada ketika :values ada.',
    'multiple_of' => ':Attribute harus merupakan kelipatan dari :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus berisi setidaknya satu huruf.',
        'mixed' => ':Attribute harus berisi setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus berisi setidaknya satu angka.',
        'symbols' => ':Attribute harus berisi setidaknya satu simbol.',
        'uncompromised' => ':Attribute yang diberikan telah teridentifikasi dalam kebocoran data. Silakan pilih :attribute yang berbeda.',
    ],
    'present' => ':Attribute harus ada.',
    'present_if' => ':Attribute harus ada ketika :other adalah :value.',
    'present_unless' => ':Attribute harus ada kecuali :other adalah :value.',
    'present_with' => ':Attribute harus ada ketika :values ada.',
    'present_with_all' => ':Attribute harus ada ketika :values ada.',
    'prohibited' => ':Attribute dilarang diisi.',
    'prohibited_if' => ':Attribute dilarang diisi ketika :other adalah :value.',
    'prohibited_unless' => ':Attribute dilarang diisi kecuali :other ada di dalam :values.',
    'prohibits' => ':Attribute melarang :other untuk ada.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => ':Attribute harus memuat entri untuk: :values.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other adalah :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi ketika :other diterima.',
    'required_if_declined' => 'Kolom :attribute wajib diisi ketika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other ada di dalam :values.',
    'required_with' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi ketika tidak ada satu pun :values yang ada.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'array' => ':Attribute harus memuat :size anggota.',
        'file' => ':Attribute harus berukuran :size kilobita.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus berisi :size karakter.',
    ],
    'starts_with' => ':Attribute harus diawali salah satu dari berikut: :values.',
    'string' => ':Attribute harus berupa string teks.',
    'timezone' => ':Attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah terdaftar dan tidak boleh duplikat.',
    'uploaded' => 'Berkas :attribute gagal diunggah.',
    'uppercase' => ':Attribute harus berupa huruf besar.',
    'url' => ':Attribute harus berupa URL yang valid.',
    'ulid' => ':Attribute harus berupa ULID yang valid.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Kustom Validasi
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat menentukan pesan validasi kustom untuk atribut menggunakan
    | konvensi "attribute.rule" untuk menamai baris.
    |
    */

    'custom' => [
        'npsn' => [
            'digits' => 'Nomor Pokok Sekolah Nasional (NPSN) harus terdiri tepat 8 digit angka.',
            'unique' => 'Nomor Pokok Sekolah Nasional (NPSN) tersebut sudah terdaftar di sistem.',
        ],
        'nomor_surat' => [
            'unique' => 'Nomor surat keluar tersebut sudah terdaftar pada tahun dan divisi ini.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kustomisasi Nama Atribut
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan untuk menukar placeholder atribut kita
    | dengan sesuatu yang lebih ramah pembaca seperti "Alamat Surel" daripada "email".
    |
    */

    'attributes' => [
        'name' => 'Nama',
        'username' => 'Username',
        'email' => 'Alamat Surel',
        'password' => 'Kata Sandi',
        'password_confirmation' => 'Konfirmasi Kata Sandi',
        'role' => 'Peran Pengguna',
        'division_id' => 'Divisi Pengarsip',
        'school_id' => 'Sekolah',
        'npsn' => 'NPSN',
        'nomor_surat' => 'Nomor Surat',
        'letter_number' => 'Nomor Surat',
        'perihal' => 'Perihal',
        'ringkasan' => 'Ringkasan',
        'tanggal_surat' => 'Tanggal Surat',
        'tanggal_diterima' => 'Tanggal Diterima',
        'tanggal_share' => 'Tanggal Pengiriman / Diseminasi',
        'pengirim' => 'Pengirim',
        'penerima' => 'Penerima',
        'sender' => 'Pengirim',
        'recipient' => 'Penerima',
        'tujuan' => 'Tujuan',
        'tanda_tangan' => 'Tanda Tangan Digital',
        'attachment' => 'Berkas Lampiran',
        'berkas' => 'Berkas Lampiran',
        'file' => 'Berkas Lampiran',
    ],

];
