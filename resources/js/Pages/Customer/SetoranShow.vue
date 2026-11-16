<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import MapView from '@/Components/MapView.vue'
import { rp, kg, metodeLabel, penjemputanLabel } from '@/utils'

const props = defineProps({ setoran: Object })

// Garis waktu status (PRD bagian 4)
const alur = computed(() => {
  const kurir = props.setoran.metode === 'dijemput_kurir'
  return kurir
    ? [['diajukan', 'Diajukan'], ['menunggu_penjemputan', 'Menunggu penjemputan'], ['dalam_penjemputan', 'Dalam penjemputan'], ['diterima', 'Diterima'], ['selesai', 'Ditimbang & selesai']]
    : [['diajukan', 'Diajukan'], ['disetujui', 'Disetujui'], ['diterima', 'Diterima'], ['selesai', 'Ditimbang & selesai']]
})
const urutan = computed(() => alur.value.map((a) => a[0]))
const posisi = computed(() => urutan.value.indexOf(props.setoran.status === 'ditimbang' ? 'selesai' : props.setoran.status))
const berhenti = computed(() => ['dibatalkan', 'ditolak'].includes(props.setoran.status))
const ditunda = computed(() => props.setoran.status === 'ditunda')

const konfirmasi = ref(false)
const proses = ref(false)
const batal = () => router.post(route('customer.setoran.batal', props.setoran.id), {}, { onStart: () => (proses.value = true), onFinish: () => { proses.value = false; konfirmasi.value = false } })
</script>

<template>
  <AppLayout :title="`Setoran #${setoran.id}`">
    <div class="mx-auto max-w-3xl space-y-5">
      <Link :href="route('customer.riwayat')" class="text-sm font-semibold text-green-700 hover:underline">← Riwayat</Link>

      <div class="card flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-xs text-gray-400">{{ setoran.tanggal }}</p><p class="font-bold text-green-900">{{ setoran.bank.nama }}</p></div>
        <StatusBadge :status="setoran.status" />
      </div>

      <div v-if="berhenti" class="rounded-2xl p-4 text-sm" :class="setoran.status === 'ditolak' ? 'bg-red-50 text-red-800' : 'bg-gray-100 text-gray-700'">
        <b>{{ setoran.status === 'ditolak' ? 'Setoran ditolak bank sampah.' : 'Setoran dibatalkan.' }}</b>
        <span v-if="setoran.alasan_penolakan"> Alasan: {{ setoran.alasan_penolakan }}</span>
      </div>
      <div v-else-if="ditunda" class="rounded-2xl bg-yellow-50 p-4 text-sm text-yellow-900">
        <b>Setoran ditunda.</b> Bank sampah sedang ditangguhkan sementara. Setoran akan dilanjutkan bila bank sampah dipulihkan, atau Anda dapat membatalkannya. Setoran yang tertunda terlalu lama dibatalkan otomatis.
      </div>
      <ol v-else class="card space-y-3">
        <li v-for="(a, i) in alur" :key="a[0]" class="flex items-center gap-3">
          <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold" :class="i < posisi ? 'bg-green-700 text-white' : i === posisi ? 'bg-amber-400 text-green-950' : 'bg-green-100 text-green-600'">{{ i < posisi ? '✓' : i + 1 }}</span>
          <span class="text-sm" :class="i <= posisi ? 'font-semibold text-green-900' : 'text-gray-400'">{{ a[1] }}</span>
        </li>
      </ol>

      <div class="card space-y-3">
        <h3 class="font-bold text-green-900">Rincian</h3>
        <ul class="divide-y divide-green-50 text-sm">
          <li v-for="i in setoran.items" :key="i.id" class="flex items-center justify-between py-2">
            <span>{{ i.ikon }} {{ i.nama }} <span class="text-xs text-gray-400">{{ rp(i.harga) }}/kg</span></span>
            <span v-if="i.berat !== null" class="text-right"><b>{{ kg(i.berat) }}</b> · <span class="font-semibold text-green-700">{{ rp(i.nilai) }}</span></span>
            <span v-else class="text-xs text-gray-400">belum ditimbang</span>
          </li>
        </ul>
        <div v-if="setoran.nilai_final !== null" class="rounded-xl bg-green-50 p-4">
          <p class="text-sm text-gray-600">Berat aktual {{ kg(setoran.total_berat) }}</p>
          <p class="text-2xl font-extrabold text-green-800">{{ rp(setoran.nilai_final) }} <span class="text-sm font-medium text-gray-500">nilai final</span></p>
          <p class="text-xs text-gray-500">Sudah ditambahkan ke saldo Anda.</p>
        </div>
        <div v-else class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
          Estimasi {{ rp(setoran.estimasi) }}. Nilai final ditetapkan setelah penimbangan.
        </div>
      </div>

      <div class="card space-y-2 text-sm">
        <h3 class="font-bold text-green-900">Penyerahan</h3>
        <p><b>{{ metodeLabel[setoran.metode] }}</b></p>
        <template v-if="setoran.metode === 'dijemput_kurir'">
          <p>{{ setoran.alamat_jemput }}</p><p class="text-gray-500">Jadwal: {{ setoran.jadwal_jemput }}</p>
          <ul v-if="setoran.riwayat_penjemputan.length" class="mt-2 space-y-1 border-l-2 border-green-200 pl-3 text-xs text-gray-500">
            <li v-for="(r, i) in setoran.riwayat_penjemputan" :key="i">{{ penjemputanLabel[r.status] ?? r.status }} · {{ r.waktu }}</li>
          </ul>
        </template>
        <template v-else>
          <p>{{ setoran.bank.alamat }}</p><p class="text-gray-500">{{ setoran.bank.jam }} · {{ setoran.bank.kontak }}</p>
          <MapView :markers="[{ ...setoran.bank, id: 1 }]" height="180px" />
        </template>
      </div>

      <div class="card"><h3 class="mb-2 font-bold text-green-900">Foto sampah</h3><img :src="setoran.foto" alt="Foto sampah" class="max-h-72 rounded-xl object-cover" /></div>

      <button v-if="setoran.bisa_batal" class="btn btn-outline w-full !border-red-200 !text-red-600 hover:!bg-red-50" @click="konfirmasi = true">Batalkan setoran</button>
      <ConfirmModal :show="konfirmasi" title="Batalkan setoran?" text="Setoran tidak akan diproses lebih lanjut. Tindakan ini tidak dapat diulang." confirm-text="Ya, batalkan" danger :processing="proses" @close="konfirmasi = false" @confirm="batal" />
    </div>
  </AppLayout>
</template>
