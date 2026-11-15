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
  <div ref="el" class="z-0 w-full overflow-hidden rounded-xl ring-1 ring-green-200" :style="{ height }" />
</template>
