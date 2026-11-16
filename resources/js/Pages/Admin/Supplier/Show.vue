<script setup>
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import MapView from '@/Components/MapView.vue'
import { rp, statusAkunLabel } from '@/utils'

const props = defineProps({ s: Object, bank: Object, pengajuanId: Number, dampak: Object, tujuan: Array, hak: Object })

const target = ref(null)
const form = useForm({ status: '', alasan: '', rencana: '' })
const ada = computed(() => props.dampak && (props.dampak.saldo_belum_dicairkan > 0 || props.dampak.setoran_belum_final > 0))
const statusSetoran = { diajukan: 'Diajukan', disetujui: 'Disetujui', menunggu_penjemputan: 'Menunggu penjemputan', dalam_penjemputan: 'Dalam penjemputan', diterima: 'Diterima (belum ditimbang)', ditimbang: 'Ditimbang', ditunda: 'Ditunda' }

// Tombol tujuan yang boleh: Nonaktif dengan dampak hanya untuk pemegang supplier.nonaktifkan
const bolehKe = (t) => (t === 'nonaktif' && ada.value ? props.hak.nonaktifkan : props.hak.status || (t === 'nonaktif' && props.hak.nonaktifkan))
const buka = (t) => { target.value = t; form.reset(); form.clearErrors(); form.status = t }
const kirim = () => {
  const rute = target.value === 'nonaktif' && props.hak.nonaktifkan && ada.value ? 'admin.supplier.nonaktifkan' : 'admin.supplier.status'
  form.post(route(rute, props.s.id), { preserveScroll: true, onSuccess: () => (target.value = null) })
}
const post = (nama) => router.post(route(`admin.supplier.${nama}`, props.s.id), {}, { preserveScroll: true })
const dampakTeks = { ditangguhkan: 'Setoran yang belum diterima menjadi Ditunda. Customer dapat membatalkan atau menunggu pemulihan.', dalam_penyelesaian: 'Setoran yang belum diterima dibatalkan otomatis. Supplier hanya dapat menimbang setoran yang sudah diterima, mencatat pencairan, dan mengunduh laporan selama masa penyelesaian.', nonaktif: 'Setoran yang belum diterima dibatalkan otomatis dan Supplier tidak dapat login. Saldo Customer tetap tercatat.', aktif: 'Akun aktif kembali. Setoran Ditunda dilanjutkan; setoran yang sudah dibatalkan tidak dipulihkan.' }
</script>

<template>
  <AppLayout :title="bank?.nama ?? s.nama">
    <div class="mx-auto max-w-4xl space-y-5">
      <Link :href="route('admin.supplier.index')" class="text-sm font-semibold text-green-700 hover:underline">← Daftar Supplier</Link>
      <p v-if="Object.values($page.props.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ Object.values($page.props.errors)[0] }}</p>

      <div class="card flex flex-wrap items-start justify-between gap-3">
        <div><h2 class="text-xl font-bold text-green-900">{{ bank?.nama }}</h2><p class="text-sm text-gray-500">{{ s.nama }} · {{ s.email }} · {{ s.hp }}</p><p class="text-xs text-gray-400">Terdaftar {{ s.terdaftar }} · login terakhir {{ s.terakhir_login ?? 'belum pernah' }}</p></div>
        <div class="flex flex-col items-end gap-1"><StatusBadge :status="s.status" /><StatusBadge v-if="bank" :status="bank.tayang" /></div>
      </div>

      <div v-if="s.alasan && s.status !== 'aktif'" class="rounded-xl bg-yellow-50 p-3 text-sm text-yellow-900"><b>Alasan status terakhir:</b> {{ s.alasan }}<span v-if="s.batas_penyelesaian"> · masa penyelesaian hingga {{ s.batas_penyelesaian }}</span></div>

      <div class="grid gap-4 md:grid-cols-2">
        <div v-if="bank" class="card space-y-2 text-sm"><h3 class="font-bold text-green-900">Bank sampah</h3><p>{{ bank.alamat }}</p><p class="text-gray-500">{{ bank.jam }} · {{ bank.kontak }}</p><MapView :markers="[bank]" height="150px" /></div>
        <div v-if="dampak" class="card space-y-2 text-sm">
          <h3 class="font-bold text-green-900">Ringkasan dampak</h3>
          <p class="rounded-xl bg-amber-50 p-3"><span class="text-gray-600">Saldo belum dicairkan</span><br><b class="text-xl text-green-800">{{ rp(dampak.saldo_belum_dicairkan) }}</b><span v-if="dampak.customer_bersaldo" class="text-xs text-gray-500"> · {{ dampak.customer_bersaldo }} Customer</span></p>
          <p class="font-semibold">Setoran belum final: {{ dampak.setoran_belum_final }}</p>
          <ul class="text-gray-600"><li v-for="(n, st) in dampak.setoran_per_status" :key="st">{{ statusSetoran[st] ?? st }}: {{ n }}</li></ul>
          <Link v-if="hak.saldo" :href="route('admin.saldo.index')" class="inline-block font-semibold text-green-700 hover:underline">Daftar saldo belum dicairkan →</Link>
        </div>
      </div>

      <div class="card space-y-3">
        <h3 class="font-bold text-green-900">Kelola akun</h3>
        <div class="flex flex-wrap gap-2">
          <template v-for="t in tujuan" :key="t"><button v-if="bolehKe(t)" class="btn" :class="t === 'aktif' ? 'btn-primary' : t === 'nonaktif' ? 'btn-danger' : 'btn-outline'" @click="buka(t)">{{ t === 'aktif' ? 'Aktifkan kembali' : statusAkunLabel[t] }}</button></template>
          <button v-if="hak.akses && s.status === 'menunggu_aktivasi'" class="btn btn-outline" @click="post('aktivasi-ulang')">✉️ Kirim ulang tautan aktivasi</button>
          <button v-if="hak.akses && s.status !== 'menunggu_aktivasi'" class="btn btn-outline" @click="post('reset-password')">🔑 Kirim tautan reset kata sandi</button>
          <Link v-if="pengajuanId && hak.pengajuan" :href="route('admin.pengajuan.show', pengajuanId)" class="btn btn-outline">📝 Lihat pengajuan</Link>
        </div>
        <p v-if="!tujuan.length" class="text-sm text-gray-400">Tidak ada perubahan status yang tersedia untuk status ini.</p>
        <p v-if="ada && !hak.nonaktifkan" class="text-xs text-gray-500">Menonaktifkan saat masih ada saldo atau setoran belum final hanya dapat dilakukan Admin dengan hak akses supplier.nonaktifkan.</p>
        <p class="text-xs text-gray-500">Admin tidak dapat melihat atau menetapkan kata sandi; pemulihan dilakukan lewat tautan sekali pakai.</p>
      </div>

      <ConfirmModal :show="!!target" :title="`Ubah status menjadi ${target ? (target === 'aktif' ? 'Aktif' : statusAkunLabel[target]) : ''}`" confirm-text="Ya, ubah status" :danger="target === 'nonaktif'" :processing="form.processing" @close="target = null" @confirm="kirim">
        <div class="space-y-3 text-sm">
          <p class="rounded-xl bg-amber-50 p-3 text-amber-900">{{ dampakTeks[target] }}</p>
          <p v-if="dampak && target !== 'aktif'" class="text-gray-600">Dampak saat ini: <b>{{ dampak.setoran_belum_final }}</b> setoran belum final dan saldo <b>{{ rp(dampak.saldo_belum_dicairkan) }}</b> pada {{ dampak.customer_bersaldo }} Customer. Customer terdampak diberi tahu lewat aplikasi dan email.</p>
          <div><label class="mb-1 block font-medium">Alasan <span class="text-red-500">*</span></label><textarea v-model="form.alasan" rows="2" class="input" /><p v-if="form.errors.alasan" class="mt-1 text-red-600">{{ form.errors.alasan }}</p></div>
          <div v-if="target === 'nonaktif' && ada"><label class="mb-1 block font-medium">Rencana penyelesaian <span class="text-red-500">*</span></label><textarea v-model="form.rencana" rows="2" class="input" placeholder="Bagaimana saldo dan setoran yang tersisa akan diselesaikan" /><p v-if="form.errors.rencana" class="mt-1 text-red-600">{{ form.errors.rencana }}</p></div>
          <p v-if="form.errors.status" class="text-red-600">{{ form.errors.status }}</p>
        </div>
      </ConfirmModal>
    </div>
  </AppLayout>
</template>
