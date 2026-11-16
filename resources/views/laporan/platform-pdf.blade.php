<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 15px; color: #14532d; margin: 0 0 2px; }
        h2 { font-size: 12px; color: #166534; margin: 18px 0 6px; border-bottom: 1px solid #bbf7d0; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th { background: #dcfce7; text-align: left; padding: 4px 5px; border: 1px solid #bbf7d0; }
        td { padding: 4px 5px; border: 1px solid #e5e7eb; vertical-align: top; }
        .r { text-align: right; } .kosong { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <h1>{{ $judul }}</h1>
    <div style="color:#6b7280;margin-bottom:10px">Dibuat {{ now()->translatedFormat('d F Y H.i') }}</div>
    @php($rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.'))
    @php($r = $data['ringkasan'])

    <h2>Ringkasan</h2>
    <table>
        <tr><td>Pengajuan Supplier pada periode</td><td class="r">{{ $r['pengajuan'] }}</td></tr>
        <tr><td>Jumlah bank sampah</td><td class="r">{{ $r['bank'] }}</td></tr>
        <tr><td>Setoran selesai</td><td class="r">{{ $r['setoran'] }}</td></tr>
        <tr><td>Total berat</td><td class="r">{{ number_format($r['berat'], 2, ',', '.') }} kg</td></tr>
        <tr><td>Total nilai setoran</td><td class="r">{{ $rp($r['nilai']) }}</td></tr>
    </table>

    <h2>Pengajuan Supplier dan hasil verifikasi</h2>
    @if (count($data['pengajuan']))
        <table>
            <tr><th>Kode</th><th>Tanggal</th><th>Bank sampah</th><th>Penanggung jawab</th><th>Status</th><th>Diputuskan</th><th>Catatan</th></tr>
            @foreach ($data['pengajuan'] as $x)
                <tr><td>{{ $x['kode'] }}</td><td>{{ $x['tanggal'] }}</td><td>{{ $x['bank'] }}</td><td>{{ $x['penanggung_jawab'] }}</td><td>{{ $x['status'] }}</td><td>{{ $x['diputuskan_oleh'] }} {{ $x['diputuskan_pada'] }}</td><td>{{ $x['catatan'] }}</td></tr>
            @endforeach
        </table>
    @else <p class="kosong">Tidak ada pengajuan pada periode ini.</p> @endif

    <h2>Bank sampah dan statusnya</h2>
    <table>
        <tr><th>Bank sampah</th><th>Status akun</th><th>Tayang</th><th class="r">Setoran</th><th class="r">Berat (kg)</th><th class="r">Nilai setoran</th><th class="r">Penjualan</th></tr>
        @foreach ($data['bank'] as $x)
            <tr><td>{{ $x['nama'] }}</td><td>{{ $x['status_akun'] }}</td><td>{{ $x['status_tayang'] }}</td><td class="r">{{ $x['setoran'] }}</td><td class="r">{{ number_format($x['berat'], 2, ',', '.') }}</td><td class="r">{{ $rp($x['nilai']) }}</td><td class="r">{{ $rp($x['penjualan']) }}</td></tr>
        @endforeach
    </table>
</body>
</html>
