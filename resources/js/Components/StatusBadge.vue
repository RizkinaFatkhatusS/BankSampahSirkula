<script setup>
import { computed } from 'vue'
const props = defineProps({ status: String })
// Status setoran, pengiriman penjualan, pengajuan Supplier, dan akun (PRD bagian 4)
const map = {
  diajukan: ['Diajukan', 'bg-blue-100 text-blue-700'],
  disetujui: ['Disetujui', 'bg-emerald-100 text-emerald-700'],
  menunggu_penjemputan: ['Menunggu penjemputan', 'bg-amber-100 text-amber-700'],
  dalam_penjemputan: ['Dalam penjemputan', 'bg-orange-100 text-orange-700'],
  diterima: ['Diterima', 'bg-teal-100 text-teal-700'],
  ditimbang: ['Ditimbang', 'bg-indigo-100 text-indigo-700'],
  selesai: ['Selesai', 'bg-green-100 text-green-700'],
  dibatalkan: ['Dibatalkan', 'bg-gray-200 text-gray-600'],
  ditolak: ['Ditolak', 'bg-red-100 text-red-700'],
  ditunda: ['Ditunda', 'bg-yellow-100 text-yellow-800'],
  diproses: ['Diproses', 'bg-amber-100 text-amber-700'],
  dikirim: ['Dikirim', 'bg-sky-100 text-sky-700'],
  // pengajuan Supplier
  menunggu_verifikasi: ['Menunggu verifikasi', 'bg-blue-100 text-blue-700'],
  sedang_diverifikasi: ['Sedang diverifikasi', 'bg-indigo-100 text-indigo-700'],
  perlu_revisi: ['Perlu revisi', 'bg-amber-100 text-amber-800'],
  kedaluwarsa: ['Kedaluwarsa', 'bg-gray-200 text-gray-600'],
  // akun
  menunggu_aktivasi: ['Menunggu aktivasi', 'bg-blue-100 text-blue-700'],
  aktif: ['Aktif', 'bg-green-100 text-green-700'],
  ditangguhkan: ['Ditangguhkan', 'bg-yellow-100 text-yellow-800'],
  dalam_penyelesaian: ['Dalam penyelesaian', 'bg-orange-100 text-orange-700'],
  nonaktif: ['Nonaktif', 'bg-gray-200 text-gray-600'],
  // dokumen & status tayang
  belum: ['Belum diperiksa', 'bg-gray-100 text-gray-600'],
  sesuai: ['Sesuai', 'bg-green-100 text-green-700'],
  perlu_diperbaiki: ['Perlu diperbaiki', 'bg-amber-100 text-amber-800'],
  tayang: ['Tayang', 'bg-green-100 text-green-700'],
  tidak_tayang: ['Tidak tayang', 'bg-gray-200 text-gray-600'],
  // email
  antre: ['Antre', 'bg-blue-100 text-blue-700'],
  terkirim: ['Terkirim', 'bg-green-100 text-green-700'],
  gagal: ['Gagal', 'bg-red-100 text-red-700'],
}
const info = computed(() => map[props.status] ?? [props.status, 'bg-gray-100 text-gray-600'])
</script>
<template>
  <span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="info[1]">{{ info[0] }}</span>
</template>
