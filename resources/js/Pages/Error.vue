<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({ status: Number })
const info = computed(() => ({
  403: ['🔒', 'Akses ditolak', 'Anda tidak memiliki akses ke halaman ini.'],
  404: ['🔎', 'Halaman tidak ditemukan', 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.'],
  500: ['🛠️', 'Terjadi kesalahan', 'Ada masalah di server. Silakan coba lagi sebentar lagi.'],
  503: ['🛠️', 'Sedang pemeliharaan', 'Silakan kembali beberapa saat lagi.'],
}[props.status] ?? ['⚠️', 'Terjadi kesalahan', 'Silakan coba lagi.']))
</script>

<template>
  <Head :title="info[1]" />
  <div class="flex min-h-screen flex-col items-center justify-center bg-green-50 px-6 text-center">
    <span class="text-6xl">{{ info[0] }}</span>
    <p class="mt-3 text-sm font-semibold text-gray-400">{{ status }}</p>
    <h1 class="text-2xl font-bold text-green-900">{{ info[1] }}</h1>
    <p class="mt-1 max-w-sm text-gray-500">{{ info[2] }}</p>
    <Link :href="route('dashboard')" class="btn btn-primary mt-6">Kembali ke beranda</Link>
  </div>
</template>
