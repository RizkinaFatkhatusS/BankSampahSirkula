<script setup>
import { onMounted, ref, watch } from 'vue'
import QRCode from 'qrcode'

const props = defineProps({ teks: String, ukuran: { type: Number, default: 200 } })
const canvas = ref(null)
const gambar = () => canvas.value && QRCode.toCanvas(canvas.value, props.teks, { width: props.ukuran, margin: 1 })
onMounted(gambar)
watch(() => props.teks, gambar)
</script>
<template><canvas ref="canvas" class="rounded-lg ring-1 ring-green-200" /></template>
