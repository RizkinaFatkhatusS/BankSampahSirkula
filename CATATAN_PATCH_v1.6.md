# Patch SIRKULA — PRD v1.6

Isi zip ini hanya file yang BERUBAH atau BARU, dengan struktur folder sama seperti proyek.
Cara pakai: ekstrak ke folder proyek, pilih "Replace/Timpa semua". Tidak ada file yang perlu dihapus.
`.env` milikmu TIDAK disentuh (lihat langkah 3).

## Langkah setelah menempel
1. `npm install`                      (paket baru: qrcode untuk kode QR 2FA)
2. `php artisan migrate`              (4 migration baru, aditif; data lama aman, saldo lama dihitung ulang per bank)
3. Tambahkan ke `.env` (contoh ada di `.env.example`):
   ```
   MFA_WAJIB=true      # isi false hanya untuk pengembangan lokal
   SESI_STAF_MENIT=30
   ```
4. `php artisan db:seed --class=AdminSeeder`   (katalog hak akses, role bawaan, data master, Super Admin & Admin contoh)
5. `php artisan optimize:clear` lalu `composer dev`

Akun contoh (kata sandi `password`): superadmin@sirkula.test, superadmin2@sirkula.test,
verifikator@sirkula.test, pengelola@sirkula.test, pemantau@sirkula.test.
Super Admin dan Admin pemegang hak akses sensitif wajib mengatur 2FA di login pertama (aplikasi autentikator).
Calon Supplier demo: halaman /status-pengajuan/demo-token-status-pengajuan-sirkula-0123456789abcd (kode SRK-DEMO2345).

Produksi: jalankan `php artisan schedule:run` tiap menit; `QUEUE_CONNECTION=database` + `php artisan queue:work` agar email lewat antrean.
Pemulihan darurat: `php artisan sirkula:pulihkan-superadmin email@contoh.id --reset-mfa`

## Peta fitur PRD v1.6 -> implementasi
| PRD | Implementasi |
|---|---|
| FR-35 daftar Supplier (publik) | /daftar-supplier, peta pilih titik, dokumen dari data master, surat pernyataan cetak, rate limit |
| FR-36 verifikasi | /admin/pengajuan: mulai, periksa tiap dokumen, catatan internal, setujui/revisi/tolak, alihkan, kedaluwarsa 14 hari |
| FR-37 aktivasi | akun Supplier "Menunggu aktivasi", tautan sekali pakai (/aktivasi/{token}), sandi ditetapkan sendiri |
| FR-38 pengajuan manual | /admin/pengajuan/create + aturan dua pasang mata |
| FR-39 akun Supplier | /admin/supplier: status + alasan, ringkasan dampak, aktivasi ulang, reset sandi |
| FR-40 akun Customer | /admin/customer: kontak disamarkan (customer.kontak), nonaktifkan/aktifkan, reset sandi |
| FR-41 bank sampah | /admin/bank-sampah: status tayang, perbaikan lokasi; scope `tampil` untuk pencarian Customer |
| FR-42 data master | /admin/master: jenis dokumen, kategori sampah/olahan, satuan (dipakai langsung oleh formulir) |
| FR-43/44 dashboard & laporan | /admin/dashboard, /admin/laporan (Excel & PDF) |
| FR-45 audit | /admin/log-audit (baca saja, model menolak update/delete) |
| FR-46 email & cek status | /daftar-supplier/status, /status-pengajuan/{token}, antrean + retry + log email + kirim ulang |
| FR-47..49 Super Admin | /superadmin/admin, /superadmin/role (checklist), middleware `izin:` di server, 403 + log akses |
| FR-50 penghentian | PenghentianService: Ditunda/Dibatalkan otomatis, notifikasi Customer, daftar saldo, nonaktif khusus + rencana, saldo per bank |
| FR-51 pengaturan | /superadmin/pengaturan (9 parameter, dipakai oleh sistem) |
| 2FA | TOTP buatan sendiri (RFC 6238, tanpa paket), wajib Super Admin & Admin sensitif, sesi staf 30 menit, login admin 3 percobaan |
| Matriks notifikasi | semua peristiwa: aplikasi dan/atau email sesuai tabel |
