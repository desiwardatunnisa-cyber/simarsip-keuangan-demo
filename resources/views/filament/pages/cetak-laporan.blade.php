<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Dokumen - SIMARSIP</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 24px; }
    h1 { font-size: 18px; text-align: center; margin-bottom: 2px; }
    p.sub { text-align: center; font-size: 11px; color: #555; margin-top: 0; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #0F3D6E; color: #fff; }
    tr:nth-child(even) { background: #F1F5F9; }
    .no { text-align: center; width: 32px; }
    .footer { margin-top: 16px; font-size: 10px; color: #666; text-align: right; }
    @media print {
        .no-print { display: none; }
    }
</style>
</head>
<body onload="window.print()">
    <h1>LAPORAN ARSIP DOKUMEN KEUANGAN</h1>
    <p class="sub">PT. PG Rajawali I - Unit Krebet Baru &middot; Dicetak oleh {{ $dicetakOleh }} pada {{ now()->format('d F Y H:i') }} WIB</p>

    <table>
        <thead>
            <tr>
                <th class="no">No</th>
                <th>Judul Dokumen</th>
                <th>Kategori</th>
                <th>No. Referensi</th>
                <th>Tanggal Dokumen</th>
                <th>Diupload Oleh</th>
                <th>Waktu Upload</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $i => $doc)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td>{{ $doc->judul_dokumen }}</td>
                    <td>{{ $doc->category->nama_kategori ?? '-' }}</td>
                    <td>{{ $doc->nomor_referensi ?? '-' }}</td>
                    <td>{{ optional($doc->tanggal_dokumen)->format('d-m-Y') ?? '-' }}</td>
                    <td>{{ $doc->user->name ?? '-' }}</td>
                    <td>{{ $doc->created_at->format('d-m-Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Total {{ $data->count() }} dokumen &middot; Dokumen ini dihasilkan otomatis oleh SIMARSIP</p>

    <p class="no-print" style="text-align:center; font-size:12px; color:#888; margin-top:24px;">
        Untuk menyimpan sebagai PDF, pilih "Save as PDF" pada dialog print yang muncul.
    </p>
</body>
</html>
