<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { rp, kg, metodeLabel, penjemputanLabel } from '@/utils'

const props = defineProps({ setoran: Object })

const s = computed(() => props.setoran)
const kurir = computed(() => s.value.metode === 'dijemput_kurir')
const belumDiterima = computed(() => ['diajukan', 'disetujui', 'menunggu_penjemputan', 'dalam_penjemputan'].includes(s.value.status))
const bisaDitimbang = computed(() => s.value.status === 'diterima')

const aksi = (nama, data = {}) => router.post(route(`supplier.setoran.${nama}`, s.value.id), data, { preserveScroll: true })

// Tolak
const tolakModal = ref(false)
const tolak = useForm({ alasan_penolakan: '' })
const kirimTolak = () => tolak.post(route('supplier.setoran.tolak', s.value.id), { preserveScroll: true, onSuccess: () => (tolakModal.value = false) })

// Timbang: berat aktual per jenis; nilai dihitung dari harga yang berlaku saat penimbangan
const timbang = useForm({ berat: Object.fromEntries(props.setoran.items.map((i) => [i.id, ''])) })
const nilaiItem = (i) => Math.round((Number(timbang.berat[i.id]) || 0) * i.harga_sekarang * 100) / 100
const totalBerat = computed(() => s.value.items.reduce((a, i) => a + (Number(timbang.berat[i.id]) || 0), 0))
const totalNilai = computed(() => s.value.items.reduce((a, i) => a + nilaiItem(i), 0))
const konfirmTimbang = ref(false)
const kirimTimbang = () => timbang.post(route('supplier.setoran.timbang', s.value.id), { onFinish: () => (konfirmTimbang.value = false) })
</script>

<template>
  <AppLayout :title="`Setoran #${s.id}`">
    <div class="mx-auto max-w-3xl space-y-5">
      <Link :href="route('supplier.setoran.index')" class="text-sm font-semibold text-green-700 hover:underline">← Semua setoran</Link>

      <div class="card flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs text-gray-400">{{ s.tanggal }}</p>
          <p class="font-bold text-green-900">{{ s.customer.nama }}</p>
          <a :href="`tel:${s.customer.hp}`" class="text-sm text-green-700 hover:underline">📞 {{ s.customer.hp }}</a>
        </div>
        <StatusBadge :status="s.status" />
      </div>

      <p v-if="Object.values($page.props.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ Object.values($page.props.errors)[0] }}</p>

      <div class="card grid gap-4 sm:grid-cols-2">
        <img :src="s.foto" alt="Foto sampah" class="max-h-64 w-full rounded-xl object-cover" />
        <div class="space-y-2 text-sm">
          <p><b>{{ metodeLabel[s.metode] }}</b></p>
          <template v-if="kurir"><p>{{ s.alamat_jemput }}</p><p class="text-gray-500">Jadwal: {{ s.jadwal_jemput }}</p></template>
          <p v-else class="text-gray-500">Customer mengantar sendiri ke bank sampah.</p>
          <p class="text-gray-500">Estimasi: {{ rp(s.estimasi) }}</p>
          <ul v-if="s.riwayat_penjemputan.length" class="space-y-1 border-l-2 border-green-200 pl-3 text-xs text-gray-500">
            <li v-for="(r, i) in s.riwayat_penjemputan" :key="i">{{ penjemputanLabel[r.status] ?? r.status }} · {{ r.waktu }}</li>
          </ul>
          <p v-if="s.alasan_penolakan" class="rounded-lg bg-red-50 p-2 text-red-700">Alasan penolakan: {{ s.alasan_penolakan }}</p>
        </div>
      </div>

      <!-- Tindakan -->
      <div v-if="belumDiterima" class="card space-y-3">
        <h3 class="font-bold text-green-900">Tindakan</h3>
        <div class="flex flex-wrap gap-2">
          <button v-if="s.status === 'diajukan'" class="btn btn-primary" @click="aksi('setujui')">✅ Setujui</button>
          <button v-if="s.status === 'menunggu_penjemputan'" class="btn btn-outline" @click="aksi('penjemputan', { status: 'dalam_penjemputan' })">🛵 Kurir berangkat</button>
          <button v-if="kurir && ['menunggu_penjemputan', 'dalam_penjemputan'].includes(s.status)" class="btn btn-primary" @click="aksi('penjemputan', { status: 'selesai' })">📦 Selesai dijemput (diterima)</button>
          <button v-else class="btn btn-primary" @click="aksi('terima')">📦 Tandai sampah diterima</button>
          <button class="btn btn-outline !border-red-200 !text-red-600 hover:!bg-red-50" @click="tolakModal = true">Tolak</button>
        </div>
      </div>

      <!-- Penimbangan -->
      <form v-if="bisaDitimbang" class="card space-y-4" @submit.prevent="konfirmTimbang = true">
        <div>
          <h3 class="font-bold text-green-900">Catat berat aktual</h3>
          <p class="text-xs text-gray-500">Nilai final dihitung dari berat aktual × harga yang berlaku saat ini, lalu masuk ke saldo Customer.</p>
        </div>
        <div v-for="i in s.items" :key="i.id" class="flex items-center gap-3 rounded-xl bg-green-50 p-3">
          <span class="text-2xl">{{ i.ikon }}</span>
          <div class="min-w-0 flex-1"><p class="font-semibold">{{ i.nama }}</p><p class="text-xs text-gray-500">{{ rp(i.harga_sekarang) }}/kg</p></div>
          <div class="w-28"><input v-model="timbang.berat[i.id]" type="number" step="0.01" min="0" inputmode="decimal" class="input text-right" placeholder="0,00" /></div>
          <span class="w-5 text-sm text-gray-500">kg</span>
          <span class="w-24 text-right text-sm font-bold text-green-800">{{ rp(nilaiItem(i)) }}</span>
        </div>
        <p v-if="timbang.errors.berat" class="text-sm text-red-600">{{ timbang.errors.berat }}</p>
        <div class="flex items-center justify-between rounded-xl bg-amber-50 p-4">
          <span class="text-sm text-amber-900">Total {{ kg(totalBerat) }}</span>
          <span class="text-xl font-extrabold text-green-800">{{ rp(totalNilai) }}</span>
        </div>
        <button class="btn btn-primary w-full" :disabled="totalBerat <= 0 || timbang.processing">Simpan & Tetapkan Nilai Final</button>
      </form>

      <!-- Hasil -->
      <div v-if="s.nilai_final !== null" class="card space-y-3">
        <h3 class="font-bold text-green-900">Hasil penimbangan</h3>
        <ul class="divide-y divide-green-50 text-sm">
          <li v-for="i in s.items" :key="i.id" class="flex justify-between py-2"><span>{{ i.ikon }} {{ i.nama }} · {{ kg(i.berat) }} × {{ rp(i.harga) }}</span><b class="text-green-700">{{ rp(i.nilai) }}</b></li>
        </ul>
        <p class="rounded-xl bg-green-50 p-4 font-bold text-green-800">Nilai final {{ rp(s.nilai_final) }} · {{ kg(s.total_berat) }}</p>
      </div>

      <ConfirmModal :show="konfirmTimbang" title="Tetapkan nilai final?" :text="`Berat ${kg(totalBerat)} senilai ${rp(totalNilai)} akan ditambahkan ke saldo Customer. Data penimbangan tidak dapat diubah setelah ini.`" confirm-text="Ya, simpan" :processing="timbang.processing" @close="konfirmTimbang = false" @confirm="kirimTimbang" />

      <ConfirmModal :show="tolakModal" title="Tolak setoran" confirm-text="Tolak setoran" danger :processing="tolak.processing" @close="tolakModal = false" @confirm="kirimTolak">
        <label class="mb-1 block text-sm font-medium">Alasan penolakan <span class="text-red-500">*</span></label>
        <textarea v-model="tolak.alasan_penolakan" rows="3" class="input" placeholder="Contoh: foto tidak sesuai jenis sampah" />
        <p v-if="tolak.errors.alasan_penolakan" class="mt-1 text-sm text-red-600">{{ tolak.errors.alasan_penolakan }}</p>
      </ConfirmModal>
    </div>
  </AppLayout>
</template>
