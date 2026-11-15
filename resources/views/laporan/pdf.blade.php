<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 15px; color: #14532d; margin: 0 0 2px; }
        h2 { font-size: 12px; color: #166534; margin: 18px 0 6px; border-bottom: 1px solid #bbf7d0; padding-bottom: 3px; }
        .sub { color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th { background: #dcfce7; text-align: left; padding: 4px 5px; border: 1px solid #bbf7d0; }
        td { padding: 4px 5px; border: 1px solid #e5e7eb; vertical-align: top; }
        .r { text-align: right; }
        .kosong { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <h1>{{ $judul }}</h1>
    <div class="sub">{{ $bank->alamat }} · dibuat {{ now()->translatedFormat('d F Y H.i') }}</div>

    @php($r = $data['ringkasan'])
    @php($rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.'))
    <h2>Ringkasan</h2>
    <table>
        <tr><td>Setoran selesai</td><td class="r">{{ $r['jumlah_setoran'] }}</td></tr>
        <tr><td>Total berat diterima</td><td class="r">{{ number_format($r['total_berat_kg'], 2, ',', '.') }} kg</td></tr>
        <tr><td>Total nilai setoran</td><td class="r">{{ $rp($r['total_nilai_setoran']) }}</td></tr>
        <tr><td>Jumlah pengolahan</td><td class="r">{{ $r['jumlah_pengolahan'] }}</td></tr>
        <tr><td>Jumlah penjualan ke mitra</td><td class="r">{{ $r['jumlah_penjualan'] }}</td></tr>
        <tr><td>Total nilai penjualan</td><td class="r">{{ $rp($r['total_penjualan']) }}</td></tr>
    </table>

    <h2>Sampah diterima per jenis</h2>
    @if (count($data['per_jenis']))
        <table>
            <tr><th>Jenis</th><th class="r">Berat (kg)</th><th class="r">Nilai</th></tr>
            @foreach ($data['per_jenis'] as $x)
                <tr><td>{{ $x['nama'] }}</td><td class="r">{{ number_format($x['berat'], 2, ',', '.') }}</td><td class="r">{{ $rp($x['nilai']) }}</td></tr>
            @endforeach
        </table>
    @else <p class="kosong">Tidak ada data pada periode ini.</p> @endif

    <h2>Setoran</h2>
    @if (count($data['setoran']))
        <table>
            <tr><th>Tanggal</th><th>No</th><th>Customer</th><th>Jenis</th><th class="r">Berat (kg)</th><th class="r">Nilai final</th></tr>
            @foreach ($data['setoran'] as $x)
                <tr><td>{{ $x['tanggal'] }}</td><td>{{ $x['nomor'] }}</td><td>{{ $x['customer'] }}</td><td>{{ $x['jenis'] }}</td><td class="r">{{ number_format($x['berat'], 2, ',', '.') }}</td><td class="r">{{ $rp($x['nilai']) }}</td></tr>
            @endforeach
        </table>
    @else <p class="kosong">Tidak ada data pada periode ini.</p> @endif

    <h2>Pengolahan</h2>
    @if (count($data['pengolahan']))
        <table>
            <tr><th>Tanggal</th><th>Kategori</th><th>Jenis olahan</th><th class="r">Berat (kg)</th><th>Catatan</th></tr>
            @foreach ($data['pengolahan'] as $x)
                <tr><td>{{ $x['tanggal'] }}</td><td>{{ $x['kategori'] }}</td><td>{{ $x['olahan'] }}</td><td class="r">{{ number_format($x['berat'], 2, ',', '.') }}</td><td>{{ $x['catatan'] }}</td></tr>
            @endforeach
        </table>
    @else <p class="kosong">Tidak ada data pada periode ini.</p> @endif

    <h2>Penjualan ke Mitra</h2>
    @if (count($data['penjualan']))
        <table>
            <tr><th>Tanggal</th><th>Mitra</th><th>Produk</th><th class="r">Nilai</th><th>Pengiriman</th><th>Bukti</th></tr>
            @foreach ($data['penjualan'] as $x)
                <tr><td>{{ $x['tanggal'] }}</td><td>{{ $x['mitra'] }}</td><td>{{ $x['produk'] }}</td><td class="r">{{ $rp($x['nilai']) }}</td><td>{{ ucfirst($x['pengiriman']) }}</td><td>{{ $x['bukti'] }}</td></tr>
            @endforeach
        </table>
    @else <p class="kosong">Tidak ada data pada periode ini.</p> @endif
</body>
</html>
