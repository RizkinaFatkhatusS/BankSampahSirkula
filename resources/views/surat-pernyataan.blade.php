<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Pernyataan Pendaftaran Supplier SIRKULA</title>
    <style>
        body { font-family: "Times New Roman", serif; max-width: 720px; margin: 40px auto; padding: 0 24px; line-height: 1.7; color: #111; }
        h1 { text-align: center; font-size: 18px; text-transform: uppercase; }
        .ttd { margin-top: 56px; display: flex; justify-content: flex-end; }
        .ttd div { width: 260px; text-align: center; }
        .garis { border-bottom: 1px dotted #444; display: inline-block; min-width: 260px; }
        button { margin-top: 24px; padding: 10px 18px; background: #15803d; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h1>Surat Pernyataan<br>Pendaftaran Supplier SIRKULA</h1>
    <p>Saya yang bertanda tangan di bawah ini:</p>
    <p>Nama penanggung jawab : <span class="garis">{{ $nama }}</span><br>
       Nama bank sampah : <span class="garis">{{ $bank }}</span><br>
       Jabatan : <span class="garis"></span></p>
    <p>Dengan ini menyatakan bahwa:</p>
    <ol>
        <li>Seluruh data dan dokumen yang saya sampaikan pada pendaftaran Supplier SIRKULA adalah benar dan dapat dipertanggungjawabkan.</li>
        <li>Saya bersedia menggunakan sistem SIRKULA untuk mencatat setoran, penimbangan, saldo, pengolahan, dan penjualan secara jujur dan akurat.</li>
        <li>Saya bersedia menerima penangguhan atau penonaktifan akun apabila ditemukan data yang tidak benar atau pelanggaran penggunaan.</li>
    </ol>
    <p>Demikian pernyataan ini saya buat dengan sebenar-benarnya.</p>
    <div class="ttd"><div>
        <span class="garis" style="min-width:200px">&nbsp;</span>, <span class="garis" style="min-width:120px">&nbsp;</span><br>
        Penanggung jawab,<br><br><br><br>
        ( <span class="garis" style="min-width:160px">{{ $nama }}</span> )
    </div></div>
    <button onclick="window.print()">Cetak atau simpan sebagai PDF</button>
</body>
</html>
