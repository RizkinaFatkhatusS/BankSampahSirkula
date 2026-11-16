<script setup>
// Memilih titik lokasi dengan mengetuk peta (Leaflet + OpenStreetMap), atau memakai lokasi perangkat.
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'

const props = defineProps({ lat: [Number, String], lng: [Number, String], height: { type: String, default: '240px' } })
const emit = defineEmits(['update'])
const el = ref(null)
const pesan = ref('')
let map, marker

const ikon = L.divIcon({ className: '', html: '<div style="width:30px;height:30px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:#15803d;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>', iconSize: [30, 30], iconAnchor: [15, 30] })

function taruh(lat, lng, geser = true) {
  if (!map) return
  if (!marker) marker = L.marker([lat, lng], { icon: ikon, draggable: true }).addTo(map).on('dragend', (e) => { const p = e.target.getLatLng(); emit('update', { lat: +p.lat.toFixed(6), lng: +p.lng.toFixed(6) }) })
  else marker.setLatLng([lat, lng])
  if (geser) map.setView([lat, lng], Math.max(map.getZoom(), 15))
}
function lokasiSaya() {
  pesan.value = ''
  if (!navigator.geolocation) return (pesan.value = 'Peramban tidak mendukung lokasi.')
  navigator.geolocation.getCurrentPosition((p) => { const lat = +p.coords.latitude.toFixed(6), lng = +p.coords.longitude.toFixed(6); taruh(lat, lng); emit('update', { lat, lng }) }, () => (pesan.value = 'Izin lokasi ditolak. Ketuk peta untuk memilih titik.'), { timeout: 8000 })
}
onMounted(() => {
  const ada = props.lat !== '' && props.lat != null && props.lng !== '' && props.lng != null
  map = L.map(el.value, { scrollWheelZoom: false }).setView(ada ? [+props.lat, +props.lng] : [-6.3745, 106.8227], ada ? 15 : 12)
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map)
  if (ada) taruh(+props.lat, +props.lng, false)
  map.on('click', (e) => { const lat = +e.latlng.lat.toFixed(6), lng = +e.latlng.lng.toFixed(6); taruh(lat, lng, false); emit('update', { lat, lng }) })
  setTimeout(() => map && map.invalidateSize(), 200)
})
onBeforeUnmount(() => map && map.remove())
watch(() => [props.lat, props.lng], ([a, b]) => { if (a !== '' && a != null && b !== '' && b != null) taruh(+a, +b, false) })
</script>
<template>
  <div class="space-y-2">
    <div ref="el" class="z-0 w-full overflow-hidden rounded-xl ring-1 ring-green-200" :style="{ height }" />
    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
      <button type="button" class="font-semibold text-green-700 hover:underline" @click="lokasiSaya">📡 Gunakan lokasi saya</button>
      <span v-if="lat !== '' && lat != null">Titik: {{ lat }}, {{ lng }}</span>
      <span v-else>Ketuk peta untuk menentukan titik lokasi.</span>
    </div>
    <p v-if="pesan" class="text-xs text-amber-700">{{ pesan }}</p>
  </div>
</template>
