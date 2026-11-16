<script setup>
// Peta Leaflet + OpenStreetMap (tanpa kunci API). Marker memakai divIcon agar tidak perlu aset gambar bawaan Leaflet.
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'

const props = defineProps({
  // [{ id, nama, latitude, longitude, warna?: 'hijau'|'biru' }]
  markers: { type: Array, default: () => [] },
  selectedId: { type: [Number, String], default: null },
  height: { type: String, default: '260px' },
})
const emit = defineEmits(['pilih'])
const el = ref(null)
const keyword = ref('')
const hasilPencarian = ref([])
const loadingSearch = ref(false)
const errorSearch = ref('')
let map, layer

const ikon = (warna, aktif) =>
  L.divIcon({
    className: '',
    html: `<div style="width:${aktif ? 34 : 28}px;height:${aktif ? 34 : 28}px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:${warna === 'biru' ? '#0284c7' : aktif ? '#f59e0b' : '#15803d'};border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>`,
    iconSize: [34, 34],
    iconAnchor: [17, 34],
  })

function gambar() {
  if (!map) return
  layer.clearLayers()
  const pts = []
  props.markers.forEach((m) => {
    const p = [Number(m.latitude), Number(m.longitude)]
    pts.push(p)
    L.marker(p, { icon: ikon(m.warna, m.id === props.selectedId) })
      .addTo(layer)
      .bindTooltip(m.nama)
      .on('click', () => emit('pilih', m.id))
  })
  if (pts.length === 1) map.setView(pts[0], 15)
  else if (pts.length > 1) map.fitBounds(pts, { padding: [30, 30], maxZoom: 15 })
}

async function cariLokasi() {
  if (!keyword.value.trim()) return

  loadingSearch.value = true
  errorSearch.value = ''
  hasilPencarian.value = []

  try {
    const params = new URLSearchParams({
      q: keyword.value,
      format: 'jsonv2',
      limit: '5',
      countrycodes: 'id',
      'accept-language': 'id'
    })

    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?${params}`
    )

    if (!response.ok) throw new Error('Gagal mencari lokasi.')

    const data = await response.json()

    if (!data.length) {
      errorSearch.value = 'Lokasi tidak ditemukan.'
      return
    }

    hasilPencarian.value = data
  } catch (error) {
    errorSearch.value = 'Terjadi kesalahan saat mencari lokasi.'
  } finally {
    loadingSearch.value = false
  }
}

function pilihHasil(item) {
  const lat = Number(item.lat)
  const lng = Number(item.lon)

  map.setView([lat, lng], 16)

  keyword.value = item.display_name
  hasilPencarian.value = []
}

onMounted(() => {
  map = L.map(el.value, { scrollWheelZoom: false }).setView([-6.3745, 106.8227], 13)
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map)
  layer = L.layerGroup().addTo(map)
  gambar()
  setTimeout(() => map && map.invalidateSize(), 200)
})
onBeforeUnmount(() => map && map.remove())
watch(() => [props.markers, props.selectedId], gambar, { deep: true })
</script>

<template>
  <div class="space-y-2">

    <!-- Search Location -->
    <form
      class="flex gap-2"
      @submit.prevent="cariLokasi"
    >
      <input
        v-model="keyword"
        type="text"
        placeholder="Cari lokasi..."
        class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none"
      />

      <button
        type="submit"
        :disabled="loadingSearch"
        class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 disabled:opacity-50"
      >
        {{ loadingSearch ? 'Mencari...' : 'Cari' }}
      </button>
    </form>

    <!-- Hasil pencarian -->
    <div
      v-if="hasilPencarian.length"
      class="max-h-40 overflow-y-auto rounded-lg border border-gray-200 bg-white"
    >
      <button
        v-for="item in hasilPencarian"
        :key="item.place_id"
        type="button"
        @click="pilihHasil(item)"
        class="block w-full border-b border-gray-100 p-3 text-left text-xs hover:bg-green-50"
      >
        {{ item.display_name }}
      </button>
    </div>

    <p v-if="errorSearch" class="text-xs text-amber-700">
      {{ errorSearch }}
    </p>

    <!-- Peta -->
    <div
      ref="el"
      class="z-0 w-full overflow-hidden rounded-xl ring-1 ring-green-200"
      :style="{ height }"
    />
  </div>
</template>
<!-- <template>
  <div ref="el" class="z-0 w-full overflow-hidden rounded-xl ring-1 ring-green-200" :style="{ height }" />
</template> -->
