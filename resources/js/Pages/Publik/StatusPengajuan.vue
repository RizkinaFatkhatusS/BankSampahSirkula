<script setup>
import { computed } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import PengajuanForm from '@/Components/PengajuanForm.vue'

const props = defineProps({ token: String, pengajuan: Object, riwayat: Array, dokumen: Array, jenisDokumen: Array, batas: Object, aktivasiBisaDiminta: Boolean })
const flash = computed(() => usePage().props.flash ?? {})

const p = props.pengajuan
const form = useForm({
  nama_bank_sampah: p.nama_bank_sampah, alamat: p.alamat, latitude: p.latitude, longitude: p.longitude, nomor_kontak: p.nomor_kontak,
  jam_operasional: p.jam_operasional, nama_penanggung_jawab: p.nama_penanggung_jawab, email: p.email, nomor_hp: p.nomor_hp, dokumen: {},
})
// Hanya dokumen yang diminta diperbaiki yang dapat diganti
const jenisPerbaikan = computed(() => [...new Set(props.dokumen.filter((d) => d.status === 'perlu_diperbaiki').map((d) => d.jenis_id))])
const kirimUlang = () => form.post(route('pengajuan.kirim-ulang', props.token), { forceFormData: true, preserveScroll: true })
const aktivasiUlang = () => router.post(route('pengajuan.aktivasi-ulang', props.token), {}, { preserveScroll: true })

const pesan = {
  menunggu_verifikasi: 'Pengajuan Anda sudah kami terima dan menunggu diperiksa Admin.',
  sedang_diverifikasi: 'Admin sedang memeriksa data dan dokumen Anda.',
  perlu_revisi: 'Pengajuan belum dapat disetujui dan perlu diperbaiki. Lihat catatan Admin di bawah.',
  disetujui: 'Pengajuan disetujui. Periksa email penanggung jawab untuk tautan aktivasi akun.',
  ditolak: 'Mohon maaf, pengajuan Anda belum dapat disetujui.',
  kedaluwarsa: 'Pengajuan kedaluwarsa karena tidak diperbaiki dalam batas waktu.',
}
</script>

<template>
  <GuestLayout lebar>
    <Head title="Status Pengajuan" />
    <div v-if="flash.success" class="mb-4 rounded-xl bg-green-50 p-3 text-sm font-medium text-green-800">{{ flash.success }}</div>
    <div v-if="flash.error" class="mb-4 rounded-xl bg-red-50 p-3 text-sm font-medium text-red-700">{{ flash.error }}</div>

    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs text-gray-400">Kode pengajuan</p>
        <p class="text-xl font-extrabold tracking-wide text-green-900">{{ p.kode }}</p>
        <p class="text-sm text-gray-500">{{ p.nama_bank_sampah }} · diajukan {{ p.dibuat }}</p>
      </div>
      <StatusBadge :status="p.status" />
    </div>
    <p class="mt-3 rounded-xl bg-green-50 p-3 text-sm text-green-900">{{ pesan[p.status] }}</p>
    <p class="mt-2 text-xs text-gray-400">Simpan kode pengajuan dan tautan halaman ini untuk mengecek lagi.</p>

    <div v-if="p.catatan_admin && ['perlu_revisi', 'ditolak'].includes(p.status)" class="mt-4 rounded-xl p-4 text-sm" :class="p.status === 'ditolak' ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-900'">
      <b>Catatan Admin:</b> {{ p.catatan_admin }}
    </div>

    <div v-if="aktivasiBisaDiminta" class="mt-4 rounded-xl bg-blue-50 p-4 text-sm text-blue-900">
      Akun Supplier Anda belum diaktifkan. Tautan aktivasi dikirim ke email penanggung jawab dan berlaku terbatas.
      <button class="btn btn-outline mt-3" @click="aktivasiUlang">Kirim ulang tautan aktivasi</button>
    </div>

    <div v-if="['ditolak', 'kedaluwarsa'].includes(p.status)" class="mt-4">
      <Link :href="route('pengajuan.daftar')" class="btn btn-primary">Ajukan pendaftaran baru</Link>
    </div>

    <section class="mt-6">
      <h3 class="mb-2 font-bold text-green-900">Dokumen</h3>
      <ul class="divide-y divide-green-50 rounded-xl border border-green-100 text-sm">
        <li v-for="d in dokumen" :key="d.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
          <div class="min-w-0"><p class="font-medium">{{ d.jenis }}</p><a :href="route('pengajuan.dokumen', { token, dokumen: d.id })" target="_blank" class="text-xs text-green-700 hover:underline">{{ d.nama }}</a><p v-if="d.catatan" class="text-xs text-amber-700">Catatan: {{ d.catatan }}</p></div>
          <StatusBadge :status="d.status" />
        </li>
      </ul>
    </section>

    <section class="mt-6">
      <h3 class="mb-2 font-bold text-green-900">Riwayat</h3>
      <ol class="space-y-2 border-l-2 border-green-200 pl-4 text-sm">
        <li v-for="(r, i) in riwayat" :key="i"><b>{{ r.label }}</b> <span class="text-xs text-gray-400">· {{ r.waktu }}</span><p v-if="r.catatan" class="text-gray-600">{{ r.catatan }}</p></li>
      </ol>
    </section>

    <form v-if="p.status === 'perlu_revisi'" class="mt-8 space-y-5 border-t border-green-100 pt-6" @submit.prevent="kirimUlang">
      <div>
        <h3 class="text-lg font-bold text-green-900">Perbaiki pengajuan</h3>
        <p class="text-sm text-gray-500">Ubah data yang perlu diperbaiki dan unggah ulang dokumen yang diminta. Pengajuan yang tidak diperbaiki dalam {{ p.batas_revisi_hari }} hari akan kedaluwarsa.</p>
      </div>
      <PengajuanForm :form="form" :jenis-dokumen="jenisDokumen" :batas="batas" :hanya-jenis="jenisPerbaikan.length ? jenisPerbaikan : []" email-terkunci />
      <button class="btn btn-primary w-full py-3" :disabled="form.processing">Kirim perbaikan</button>
    </form>
  </GuestLayout>
</template>
