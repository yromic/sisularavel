<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Laporan Administrasi SISURAT</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 1.2cm 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .header h1 {
            font-size: 13px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            font-size: 15px;
            font-weight: bold;
            margin: 2px 0 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 9px;
            color: #475569;
            margin: 3px 0 0;
        }
        .meta-box {
            margin-bottom: 14px;
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            font-size: 9px;
            padding: 2px 4px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 12px 0 6px;
            text-transform: uppercase;
            border-left: 3px solid #047857;
            padding-left: 6px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 18px;
            font-size: 8px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <!-- Official Header -->
    <div class="header">
        <h1>Pemerintah Kabupaten Semarang</h1>
        <h2>Dinas Pendidikan, Kebudayaan, Kepemudaan dan Olahraga</h2>
        <p>Laporan Rekapitulasi Administrasi Persuratan & Piagam Penghargaan Siswa Berprestasi</p>
    </div>

    <!-- Meta Information -->
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width: 15%; font-weight: bold;">Konteks Divisi</td>
                <td style="width: 35%;">: {{ $activeDivision ? $activeDivision->name . ' (' . $activeDivision->code . ')' : 'Semua Divisi (Global)' }}</td>
                <td style="width: 15%; font-weight: bold;">Tanggal Cetak</td>
                <td style="width: 35%;">: {{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Jenis Laporan</td>
                <td>: {{ strtoupper($type) }}</td>
                <td style="font-weight: bold;">Petugas</td>
                <td>: {{ $printedBy->name }} ({{ $printedBy->role }})</td>
            </tr>
            @if ($dateFrom || $dateTo || $year)
                <tr>
                    <td style="font-weight: bold;">Filter Periode</td>
                    <td colspan="3">: {{ $dateFrom ? 'Dari: ' . $dateFrom : '' }} {{ $dateTo ? 's/d ' . $dateTo : '' }} {{ $year ? 'Tahun: ' . $year : '' }}</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- 1. Incoming Letters -->
    @if ($type === 'incoming' || $type === 'all')
        <div class="section-title">Rekapitulasi Surat Masuk ({{ $incomingLetters->count() }} Data)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 18%;">Nomor Surat</th>
                    <th style="width: 20%;">Asal Surat</th>
                    <th style="width: 30%;">Perihal</th>
                    <th style="width: 9%;">Tgl Surat</th>
                    <th style="width: 9%;">Diterima</th>
                    <th style="width: 10%;">Divisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($incomingLetters as $index => $letter)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td style="font-weight: 600;">{{ $letter->letter_number }}</td>
                        <td>{{ $letter->origin_agency }}</td>
                        <td>{{ $letter->subject }}</td>
                        <td>{{ $letter->letter_date ? $letter->letter_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $letter->received_date ? $letter->received_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $letter->division?->code ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 12px; color: #64748b;">Tidak ada data surat masuk untuk kriteria yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- 2. Outgoing Letters -->
    @if ($type === 'outgoing' || $type === 'all')
        <div class="section-title">Rekapitulasi Surat Keluar ({{ $outgoingLetters->count() }} Data)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 20%;">Nomor Surat</th>
                    <th style="width: 6%;">Tahun</th>
                    <th style="width: 38%;">Perihal</th>
                    <th style="width: 10%;">Tgl Surat</th>
                    <th style="width: 10%;">Tgl Shared</th>
                    <th style="width: 12%;">Divisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($outgoingLetters as $index => $letter)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td style="font-weight: 600;">{{ $letter->letter_number }}</td>
                        <td class="text-center">{{ $letter->letter_year }}</td>
                        <td>{{ $letter->subject }}</td>
                        <td>{{ $letter->letter_date ? $letter->letter_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $letter->disseminated_date ? $letter->disseminated_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $letter->division?->code ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 12px; color: #64748b;">Tidak ada data surat keluar untuk kriteria yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- 3. Piagam & Sertifikat -->
    @if ($type === 'piagam' || $type === 'all')
        <div class="section-title">Rekapitulasi Pengambilan Piagam ({{ $piagams->count() }} Data)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 18%;">Nama Siswa</th>
                    <th style="width: 20%;">Sekolah</th>
                    <th style="width: 14%;">Kategori Lomba</th>
                    <th style="width: 6%;">Tahun</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 16%;">Penerima</th>
                    <th style="width: 10%;">Divisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($piagams as $index => $piagam)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td style="font-weight: 600;">{{ $piagam->piagam_description ?: $piagam->student_name }}</td>
                        <td>{{ $piagam->school?->name ?? $piagam->school_origin ?? $piagam->recipient_unit ?? '-' }}</td>
                        <td>{{ $piagam->competition_category_name ?: ($piagam->competitionCategory?->name ?? '-') }}</td>
                        <td class="text-center">{{ $piagam->competition_year ?? '-' }}</td>
                        <td>Tercatat</td>
                        <td>{{ $piagam->recipient_name ? $piagam->recipient_name . ' (' . ($piagam->recipient_position ?? '-') . ')' : '-' }}</td>
                        <td>{{ $piagam->division?->code ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 12px; color: #64748b;">Tidak ada data piagam untuk kriteria yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        <span>Dokumen resmi ini di-generate secara otomatis melalui SISURAT Disdikbudpora Kab. Semarang</span>
        <span>Halaman 1 / 1 &bull; Dicetak oleh: {{ $printedBy->name }}</span>
    </div>
</body>
</html>
