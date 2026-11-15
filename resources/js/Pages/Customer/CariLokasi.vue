<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MapView from '@/Components/MapView.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ banks: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const filter = ref(props.filters.filter ?? '')
const lat = ref(props.filters.lat)
const lng = ref(props.filters.lng)
const dipilih = ref(null)
const lokasiError = ref('')

const filterOpsi = [['', 'Semua'], ['terdekat', 'Terdekat'], ['organik', 'Organik'], ['anorganik', 'Anorganik']]

function cari() {
  router.get(route('customer.bank.index'), { q: q.value || undefined, filter: filter.value || undefined, lat: lat.value || undefined, lng: lng.value || undefined }, { preserveState: true, preserveScroll: true, replace: true })
}
function pilihFilter(f) {
  filter.value = f
  if (f === 'terdekat' && lat.value == null) return minta()
  cari()
}
function minta() {
  lokasiError.value = ''
  if (!navigator.geolocation) return (lokasiError.value = 'Peramban tidak mendukung lokasi.')
  navigator.geolocation.getCurrentPosition(
    (p) => { lat.value = p.coords.latitude.toFixed(6); lng.value = p.coords.longitude.toFixed(6); if (!filter.value) filter.value = 'terdekat'; cari() },
    () => { lokasiError.value = 'Izin lokasi ditolak. Jarak tidak dapat ditampilkan.'; if (filter.value === 'terdekat') filter.value = '' ; cari() },
    { timeout: 8000 },
  )
}
const koordinat = computed(() => (lat.value != null && lng.value != null ? { lat: lat.value, lng: lng.value } : {}))
const markers = computed(() => props.banks)
</script>

<template>
  <AppLayout title="Cari Bank Sampah">
    <div class="space-y-4">
      <form class="flex gap-2" @submit.prevent="cari">
        <input v-model="q" type="search" class="input" placeholder="Cari nama atau lokasi bank sampah…" />
        <button class="btn btn-primary">Cari</button>
      </form>

      <div class="flex flex-wrap items-center gap-2">
        <button v-for="f in filterOpsi" :key="f[0]" type="button" class="rounded-full px-4 py-1.5 text-sm font-medium transition"
          :class="filter === f[0] ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200 hover:bg-green-50'" @click="pilihFilter(f[0])">{{ f[1] }}</button>
        <button type="button" class="ml-auto text-sm font-semibold text-green-700 hover:underline" @click="minta">📡 {{ lat ? 'Perbarui lokasi saya' : 'Gunakan lokasi saya' }}</button>
      </div>
      <p v-if="lokasiError" class="text-sm text-amber-700">{{ lokasiError }}</p>

      <MapView :markers="markers" :selected-id="dipilih" height="260px" @pilih="(id) => (dipilih = id)" />

      <EmptyState v-if="!banks.length" icon="📍" title="Bank sampah tidak ditemukan" text="Coba kata kunci atau filter lain." />

      <ul class="grid gap-3 lg:grid-cols-2">
        <li v-for="b in banks" :key="b.id" class="card flex flex-col gap-3 transition" :class="dipilih === b.id ? '!ring-2 !ring-amber-400' : ''" @click="dipilih = b.id">
          <div class="flex gap-3">
            <img v-if="b.foto" :src="b.foto" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover" />
            <span v-else class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-green-100 text-2xl">🏢</span>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-2">
                <p class="font-bold text-green-900">{{ b.nama }}</p>
                <span v-if="b.jarak !== null" class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ b.jarak }} km</span>
              </div>
              <p class="text-sm text-gray-500">{{ b.alamat }}</p>
              <p class="text-xs text-gray-400">🕗 {{ b.jam }}</p>
            </div>
          </div>
          <p class="text-xs text-gray-500">Menerima: {{ b.jenis.join(', ') || '–' }}</p>
          <div class="flex gap-2">
            <Link :href="route('customer.bank.show', { bank: b.id, ...koordinat })" class="btn btn-outline flex-1">Lihat</Link>
            <Link :href="route('customer.setoran.create', { bank: b.id, ...koordinat })" class="btn btn-primary flex-1">Pilih Lokasi</Link>
          </div>
        </li>
      </ul>
    </div>
  </AppLayout>
</template>
