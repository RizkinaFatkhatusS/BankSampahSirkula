# SIRKULA — Website Bank Sampah Sirkular

Satu aplikasi **Laravel** (backend, routing, database, autentikasi) dengan **Vue 3** sebagai lapisan tampilan
melalui **Inertia.js**, dan **Tailwind CSS** untuk gaya. Tidak ada server/API kedua: Laravel yang melayani
semua halaman, Vue hanya merender komponen di `resources/js/Pages`.

## Menjalankan (Windows / WAMP)

```bash
composer install
composer require maatwebsite/excel barryvdh/laravel-dompdf   # laporan Excel & PDF
npm install
```

1. Buat database MySQL kosong bernama `bank_sampah_sirkula` (sesuai `.env`).
2. Jalankan:
```bash
php artisan key:generate        # hanya jika APP_KEY di .env kosong
php artisan migrate:fresh --seed
php artisan storage:link
composer dev                    # php artisan serve + vite
```
3. Buka http://localhost:8000

Produksi: `npm run build` (jangan biarkan file `public/hot` tertinggal).

## Akun contoh (kata sandi: `password`)
| Role | Email |
|---|---|
| Supplier (data lengkap) | supplier@sirkula.test |
| Supplier lain | supplier2@sirkula.test, supplier3@sirkula.test |
| Customer | customer@sirkula.test, budi@sirkula.test |

## Struktur
- `app/Services/SetoranService.php` — estimasi, nilai final, saldo, pencairan (dalam DB transaction)
- `app/Policies/SetoranPolicy.php` + middleware `role:` — hak akses
- `app/Http/Controllers/{Customer,Supplier}` — controller per peran
- `resources/js/Pages/{Customer,Supplier}` — halaman Vue
- `tests/Feature/PenimbanganTest.php` — uji nilai final, saldo, dan hak akses (`php artisan test`)

## Pembaruan PRD v1.6 (Admin, Super Admin, pendaftaran Supplier)

Setelah menempelkan patch:
```bash
npm install                                # paket baru: qrcode (kode QR 2FA)
php artisan migrate                        # 4 migration baru, data lama tetap aman
php artisan db:seed --class=AdminSeeder    # katalog hak akses, role bawaan, data master, akun Super Admin/Admin contoh
php artisan optimize:clear
```
Tambahkan ke `.env` (lihat `.env.example`): `MFA_WAJIB=true` (isi `false` hanya untuk pengembangan lokal), `SESI_STAF_MENIT=30`.

Akun contoh (kata sandi `password`): `superadmin@sirkula.test`, `superadmin2@sirkula.test`,
`verifikator@sirkula.test`, `pengelola@sirkula.test`, `pemantau@sirkula.test`.
Super Admin dan Admin pemegang hak akses sensitif wajib mengatur 2FA (aplikasi autentikator) saat login pertama.

Tugas terjadwal (jalankan `php artisan schedule:run` tiap menit lewat cron / Task Scheduler):
`sirkula:pengajuan-kedaluwarsa`, `sirkula:pengajuan-pengingat`, `sirkula:setoran-ditunda-batal`, `sirkula:penyelesaian-nonaktif`.
Email dikirim lewat antrean (`QUEUE_CONNECTION=database` + `php artisan queue:work` di produksi; `sync` untuk lokal). `MAIL_MAILER=log` menulis email ke `storage/logs`.

Pemulihan darurat Super Admin: `php artisan sirkula:pulihkan-superadmin email@contoh.id --reset-mfa`
