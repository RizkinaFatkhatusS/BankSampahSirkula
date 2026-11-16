<?php

namespace App\Support;

/** Katalog hak akses tetap (PRD 3.4). Penambahan hak akses baru = pengembangan fitur baru. */
final class HakAksesKatalog
{
    public const DAPAT = 'dapat_diberikan';
    public const SENSITIF = 'sensitif';
    public const KHUSUS = 'khusus_super_admin';

    /** [kode, kelompok, deskripsi, jenis] */
    public static function semua(): array
    {
        return [
            ['pengajuan.lihat', 'Pendaftaran Supplier', 'Melihat antrean dan detail pengajuan', self::DAPAT],
            ['pengajuan.dokumen', 'Pendaftaran Supplier', 'Membuka dokumen verifikasi (KTP, legalitas)', self::SENSITIF],
            ['pengajuan.putuskan', 'Pendaftaran Supplier', 'Menyetujui, meminta revisi, atau menolak pengajuan', self::SENSITIF],
            ['pengajuan.buat', 'Pendaftaran Supplier', 'Membuat pengajuan manual atas nama calon Supplier', self::DAPAT],
            ['pengajuan.alihkan', 'Pendaftaran Supplier', 'Mengalihkan pengajuan yang sedang ditangani Admin lain', self::DAPAT],
            ['supplier.lihat', 'Akun Supplier', 'Melihat daftar dan detail akun Supplier', self::DAPAT],
            ['supplier.status', 'Akun Supplier', 'Mengubah status akun (aktif, ditangguhkan, dalam penyelesaian)', self::SENSITIF],
            ['supplier.nonaktifkan', 'Akun Supplier', 'Menonaktifkan akun Supplier, termasuk saat masih ada saldo atau setoran belum final (dengan alasan dan rencana penyelesaian)', self::SENSITIF],
            ['supplier.akses', 'Akun Supplier', 'Mengirim ulang aktivasi dan memicu reset kata sandi Supplier', self::DAPAT],
            ['customer.lihat', 'Akun Customer', 'Melihat daftar dan data akun Customer (kontak disamarkan)', self::DAPAT],
            ['customer.kontak', 'Akun Customer', 'Melihat nomor HP dan email Customer secara lengkap', self::SENSITIF],
            ['customer.status', 'Akun Customer', 'Mengaktifkan atau menonaktifkan akun Customer dan memicu reset kata sandi', self::SENSITIF],
            ['banksampah.lihat', 'Bank sampah', 'Melihat seluruh bank sampah beserta status', self::DAPAT],
            ['banksampah.kelola', 'Bank sampah', 'Mengubah status tayang dan memperbaiki lokasi atau alamat', self::DAPAT],
            ['banksampah.saldo', 'Bank sampah', 'Melihat ringkasan dampak dan daftar Saldo Belum Dicairkan saat penghentian bank sampah', self::DAPAT],
            ['master.lihat', 'Data master', 'Melihat data master', self::DAPAT],
            ['master.kelola', 'Data master', 'Mengubah data master (jenis dokumen, kategori, satuan)', self::DAPAT],
            ['dashboard.lihat', 'Monitoring dan laporan', 'Melihat dashboard platform', self::DAPAT],
            ['laporan.unduh', 'Monitoring dan laporan', 'Mengunduh laporan platform', self::DAPAT],
            ['audit.lihat', 'Audit dan email', 'Melihat log audit (baca saja)', self::DAPAT],
            ['email.kelola', 'Audit dan email', 'Melihat log email dan mengirim ulang email yang gagal', self::DAPAT],
            ['admin.kelola', 'Pengelolaan akses', 'Membuat, mengubah, dan menonaktifkan akun Admin', self::KHUSUS],
            ['role.kelola', 'Pengelolaan akses', 'Mengelola role dan checklist hak akses (Manajemen Role)', self::KHUSUS],
            ['sistem.atur', 'Pengelolaan akses', 'Mengelola pengaturan sistem', self::KHUSUS],
            ['akses.log', 'Pengelolaan akses', 'Melihat log akses (login, penolakan 403, perubahan role)', self::KHUSUS],
        ];
    }

    /** Role bawaan (usulan PRD) => [deskripsi, [kode hak akses]] */
    public static function roleBawaan(): array
    {
        return [
            'Verifikator Supplier' => ['Admin yang memeriksa pendaftaran Supplier', ['pengajuan.lihat', 'pengajuan.dokumen', 'pengajuan.putuskan', 'pengajuan.buat', 'banksampah.lihat']],
            'Pengelola Akun' => ['Admin layanan pengguna', ['supplier.lihat', 'supplier.status', 'supplier.akses', 'customer.lihat', 'customer.status', 'banksampah.lihat', 'banksampah.kelola']],
            'Penanganan Penutupan Bank Sampah' => ['Admin senior yang menangani penghentian bank sampah', ['supplier.lihat', 'supplier.status', 'supplier.nonaktifkan', 'banksampah.lihat', 'banksampah.saldo']],
            'Pengelola Data Master' => ['Admin data', ['master.lihat', 'master.kelola']],
            'Pemantau' => ['Pimpinan atau pemantau; hanya melihat', ['dashboard.lihat', 'laporan.unduh', 'banksampah.lihat']],
            'Auditor' => ['Pemeriksa kepatuhan; hanya melihat', ['audit.lihat', 'dashboard.lihat']],
        ];
    }
}
