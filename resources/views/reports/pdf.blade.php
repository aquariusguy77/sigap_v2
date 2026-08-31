<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 22mm 14mm 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #12303a; margin: 0; }

        .kop { border-bottom: 2px solid #0d5c6d; padding-bottom: 10px; margin-bottom: 14px; }
        .kop table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; border: none; padding: 0; }
        .kop .logo { width: 62px; }
        .kop .logo img { width: 54px; }
        .kop .instansi { font-size: 8.5pt; letter-spacing: .6px; color: #4a6b75; text-transform: uppercase; }
        .kop .nama { font-size: 14pt; font-weight: bold; color: #0d5c6d; margin: 2px 0; }
        .kop .alamat { font-size: 8pt; color: #5d7884; }

        .judul { text-align: center; margin: 0 0 12px; }
        .judul h1 { font-size: 12pt; margin: 0 0 3px; text-transform: uppercase; letter-spacing: .5px; }
        .judul p { font-size: 8.5pt; color: #5d7884; margin: 0; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #0d5c6d; color: #fff; font-size: 8pt; text-align: left;
            padding: 6px 7px; border: 1px solid #0d5c6d;
        }
        table.data td { padding: 5px 7px; border: 1px solid #cfdde1; font-size: 8.5pt; }
        table.data tr:nth-child(even) td { background: #f4fafb; }
        table.data td.nomor { text-align: center; width: 26px; }
        .kosong { text-align: center; padding: 20px; color: #6b8792; font-style: italic; }

        .ttd { margin-top: 22px; width: 100%; }
        .ttd td { border: none; font-size: 8.5pt; vertical-align: top; }
        .ttd .kanan { text-align: left; width: 40%; }
        .ttd .garis { margin-top: 44px; border-top: 1px solid #12303a; width: 165px; padding-top: 3px; }

        .catatan { margin-top: 14px; font-size: 7.5pt; color: #6b8792; font-style: italic; }
    </style>
</head>
<body>

<div class="kop">
    <table>
        <tr>
            <td class="logo">@if (filled($logo))<img src="{{ $logo }}" alt="Lambang Imigrasi">@endif</td>
            <td>
                <div class="instansi">Kementerian Imigrasi dan Pemasyarakatan</div>
                <div class="nama">Rumah Detensi Imigrasi Surabaya</div>
                <div class="alamat">
                    SIGAP &mdash; Sistem Informasi &amp; Gerakan Administratif Pengungsi
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="judul">
    <h1>{{ $title }}</h1>
    <p>{{ $note }}</p>
</div>

<table class="data">
    <thead>
        <tr>
            <th class="nomor">No</th>
            @foreach ($headings as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $index => $row)
            <tr>
                <td class="nomor">{{ $index + 1 }}</td>
                @foreach ($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td class="kosong" colspan="{{ count($headings) + 1 }}">
                    Belum ada data untuk laporan ini.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="ttd">
    <tr>
        <td>
            Jumlah data: <strong>{{ count($rows) }}</strong> baris<br>
            Dicetak oleh: {{ $printedBy }}
        </td>
        <td class="kanan">
            Surabaya, {{ $printedAt }}<br>
            Petugas yang mencetak,
            <div class="garis">{{ $printedBy }}</div>
        </td>
    </tr>
</table>

<p class="catatan">
    Dokumen ini dihasilkan secara otomatis oleh aplikasi SIGAP. Seluruh data bersifat
    internal dan hanya boleh digunakan untuk keperluan kedinasan.
</p>

</body>
</html>
