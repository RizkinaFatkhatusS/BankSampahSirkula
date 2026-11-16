<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import MapView from '@/Components/MapView.vue'

const props = defineProps({ p: Object, dokumen: Array, riwayat: Array, hak: Object, adminTujuan: Array })

const aksi = (nama, data = {}) => router.post(route(`admin.pengajuan.${nama}`, props.p.id), data, { preserveScroll: true })
const periksa = (d, status, catatan = d.catatan) => router.post(route('admin.pengajuan.periksa', { pengajuan: props.p.id, dokumen: d.id }), { status, catatan }, { preserveScroll: true })
const catatan = useForm({ catatan_verifikasi: props.p.catatan_verifikasi ?? '' })

// Keputusan: setujui | revisi | tolak
const modal = ref(null)
const keputusan = useForm({ catatan: '' })
const judulModal = { setujui: 'Setujui pengajuan?', revisi: 'Minta revisi', tolak: 'Tolak pengajuan' }
const kirimKeputusan = () => keputusan.post(route(`admin.pengajuan.${modal.value}`, props.p.id), { onSuccess: () => (modal.value = null), onFinish: () => keputusan.reset() })

const alih = ref(false)
const target = ref('')
const kirimAlih = () => router.post(route('admin.pengajuan.alihkan', props.p.id), { target: target.value }, { preserveScroll: true, onSuccess: () => (alih.value = false) })
</script>

<template>
  <AppLayout :title="p.kode">
    <div class="mx-auto max-w-4xl space-y-5">
      <Link :href="route('admin.pengajuan.index')" class="text-sm font-semibold text-green-700 hover:underline">← Antrean pengajuan</Link>

      <div class="card flex flex-wrap items-start justify-between gap-3">
        <div><p class="text-xs text-gray-400">{{ p.kode }} · diajukan {{ p.dibuat }}</p><h2 class="text-xl font-bold text-green-900">{{ p.bank }}</h2><p v-if="p.pembuat" class="text-xs text-blue-700">Dibuat manual oleh {{ p.pembuat }}</p><p v-if="p.penangan" class="text-xs text-gray-500">Ditangani: <b>{{ p.penangan }}</b></p></div>
        <StatusBadge :status="p.status" />
      </div>

      <p v-if="Object.values($page.props.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ Object.values($page.props.errors)[0] }}</p>
      <p v-if="hak.buatSaya" class="rounded-xl bg-blue-50 p-3 text-sm text-blue-900">Anda membuat pengajuan manual ini, sehingga tidak dapat memeriksa atau memutuskannya. Minta Admin lain.</p>
      <p v-else-if="hak.ditanganiLain && !hak.putuskan" class="rounded-xl bg-yellow-50 p-3 text-sm text-yellow-900">Pengajuan ini sedang ditangani {{ p.penangan }}. Hanya yang menanganinya yang dapat memutuskan, kecuali dialihkan.</p>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="card space-y-2 text-sm">
          <h3 class="font-bold text-green-900">Data bank sampah</h3>
          <p>{{ p.alamat }}</p><p class="text-gray-500">{{ p.jam }} · {{ p.kontak }}</p>
          <MapView :markers="[{ id: 1, nama: p.bank, latitude: p.latitude, longitude: p.longitude }]" height="170px" />
        </div>
        <div class="card space-y-2 text-sm">
          <h3 class="font-bold text-green-900">Penanggung jawab</h3>
          <p class="font-semibold">{{ p.pj }}</p><p>✉️ {{ p.email }}</p><p>📞 {{ p.hp }}</p>
          <p v-if="p.pemutus" class="text-xs text-gray-500">Diputuskan {{ p.pemutus }} · {{ p.diputuskan_pada }}</p>
          <p v-if="p.catatan_admin" class="rounded-lg bg-gray-50 p-2 text-gray-700"><b>Catatan untuk pemohon:</b> {{ p.catatan_admin }}</p>
          <Link v-if="p.supplier_id" :href="route('admin.supplier.show', p.supplier_id)" class="inline-block font-semibold text-green-700 hover:underline">Lihat akun Supplier →</Link>
        </div>
      </div>

      <div class="card">
        <h3 class="mb-3 font-bold text-green-900">Dokumen verifikasi</h3>
        <p v-if="!hak.dokumen" class="mb-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-800">Anda tidak memiliki hak akses membuka isi dokumen (pengajuan.dokumen).</p>
        <ul class="divide-y divide-green-50 text-sm">
          <li v-for="d in dokumen" :key="d.id" class="space-y-2 py-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="min-w-0"><p class="font-semibold">{{ d.jenis }}</p>
                <a v-if="hak.dokumen" :href="route('admin.pengajuan.dokumen', { pengajuan: p.id, dokumen: d.id })" target="_blank" class="text-green-700 hover:underline">{{ d.nama }}</a><span v-else class="text-gray-400">{{ d.nama }}</span>
                <span class="text-xs text-gray-400"> · {{ d.ukuran }} KB</span></div>
              <StatusBadge :status="d.status" />
            </div>
            <div v-if="hak.putuskan" class="flex flex-wrap items-center gap-2">
              <button class="rounded-lg bg-green-50 px-3 py-1 text-xs font-semibold text-green-800 hover:bg-green-100" @click="periksa(d, 'sesuai', '')">✓ Sesuai</button>
              <input :value="d.catatan" class="input !w-auto min-w-0 flex-1 !py-1 text-xs" placeholder="Catatan perbaikan…" @change="periksa(d, 'perlu_diperbaiki', $event.target.value)" />
              <button class="rounded-lg bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-100" @click="periksa(d, 'perlu_diperbaiki')">Perlu diperbaiki</button>
            </div>
            <p v-else-if="d.catatan" class="text-xs text-amber-700">Catatan: {{ d.catatan }}</p>
          </li>
        </ul>
      </div>

      <div class="card space-y-3">
        <h3 class="font-bold text-green-900">Catatan verifikasi internal</h3>
        <p class="text-xs text-gray-500">Hasil konfirmasi telepon atau kunjungan. Tidak ditampilkan kepada pemohon.</p>
        <textarea v-model="catatan.catatan_verifikasi" rows="3" class="input" :disabled="!hak.putuskan" />
        <button v-if="hak.putuskan" class="btn btn-outline" :disabled="catatan.processing" @click="catatan.post(route('admin.pengajuan.catatan', p.id), { preserveScroll: true })">Simpan catatan</button>
      </div>

      <div v-if="hak.mulai || hak.putuskan || hak.alihkan" class="card space-y-3">
        <h3 class="font-bold text-green-900">Keputusan</h3>
        <div class="flex flex-wrap gap-2">
          <button v-if="hak.mulai" class="btn btn-primary" @click="aksi('mulai')">▶ Mulai verifikasi</button>
          <template v-if="hak.putuskan">
            <button class="btn btn-primary" @click="modal = 'setujui'">✅ Setujui</button>
            <button class="btn btn-outline" @click="modal = 'revisi'">✏️ Minta revisi</button>
            <button class="btn btn-outline !border-red-200 !text-red-600 hover:!bg-red-50" @click="modal = 'tolak'">Tolak</button>
          </template>
          <button v-if="hak.alihkan" class="btn btn-outline" @click="alih = true">↪ Alihkan</button>
        </div>
      </div>

      <div class="card">
        <h3 class="mb-3 font-bold text-green-900">Riwayat status</h3>
        <ol class="space-y-2 border-l-2 border-green-200 pl-4 text-sm">
          <li v-for="(r, i) in riwayat" :key="i"><b>{{ r.label }}</b> <span class="text-xs text-gray-400">· {{ r.waktu }}<span v-if="r.oleh"> · {{ r.oleh }}</span></span><p v-if="r.catatan" class="text-gray-600">{{ r.catatan }}</p></li>
        </ol>
      </div>

      <ConfirmModal :show="!!modal" :title="judulModal[modal]" :confirm-text="modal === 'tolak' ? 'Tolak pengajuan' : modal === 'revisi' ? 'Kirim permintaan revisi' : 'Setujui dan buat akun'" :danger="modal === 'tolak'" :processing="keputusan.processing" :text="modal === 'setujui' ? 'Akun Supplier dan bank sampah dibuat. Tautan aktivasi dikirim ke email penanggung jawab; Supplier menetapkan kata sandinya sendiri.' : ''" @close="modal = null" @confirm="kirimKeputusan">
        <label class="mb-1 block text-sm font-medium">{{ modal === 'tolak' ? 'Alasan penolakan' : modal === 'revisi' ? 'Catatan revisi' : 'Catatan (opsional)' }}<span v-if="modal !== 'setujui'" class="text-red-500"> *</span></label>
        <textarea v-model="keputusan.catatan" rows="3" class="input" :placeholder="modal === 'revisi' ? 'Jelaskan bagian yang perlu diperbaiki' : ''" />
        <p v-if="keputusan.errors.catatan" class="mt-1 text-sm text-red-600">{{ keputusan.errors.catatan }}</p>
      </ConfirmModal>

      <ConfirmModal :show="alih" title="Alihkan pengajuan" confirm-text="Alihkan" @close="alih = false" @confirm="kirimAlih">
        <select v-model="target" class="input"><option value="" disabled>Pilih Admin tujuan</option><option v-for="a in adminTujuan" :key="a.id" :value="a.id">{{ a.nama }}</option></select>
        <p v-if="!adminTujuan.length" class="mt-2 text-xs text-gray-500">Tidak ada Admin lain yang berhak memutuskan pengajuan.</p>
      </ConfirmModal>
    </div>
  </AppLayout>
</template>
