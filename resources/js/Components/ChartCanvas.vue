<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const props = defineProps({
  type: { type: String, default: 'bar' }, // bar | line | doughnut
  labels: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] },
  height: { type: String, default: '240px' },
})
const canvas = ref(null)
let chart
const warna = ['#15803d', '#f59e0b', '#0284c7', '#e11d48', '#7c3aed', '#0d9488']

function buat() {
  chart?.destroy()
  const dong = props.type === 'doughnut'
  chart = new Chart(canvas.value, {
    type: props.type,
    data: {
      labels: props.labels,
      datasets: props.datasets.map((d, i) => ({
        borderRadius: 6,
        backgroundColor: dong ? warna : warna[i % warna.length],
        borderColor: dong ? '#fff' : warna[i % warna.length],
        ...d,
      })),
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: dong || props.datasets.length > 1, position: 'bottom' } },
      scales: dong ? {} : { y: { beginAtZero: true, grid: { color: '#ecfdf5' } }, x: { grid: { display: false } } },
    },
  })
}
onMounted(buat)
onBeforeUnmount(() => chart?.destroy())
watch(() => [props.labels, props.datasets], buat, { deep: true })
</script>
<template>
  <div :style="{ height }"><canvas ref="canvas" /></div>
</template>
