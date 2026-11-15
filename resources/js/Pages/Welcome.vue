<script setup>
import { Head, Link } from '@inertiajs/vue3'
defineProps({ canLogin: Boolean, canRegister: Boolean })

const langkah = [
  ['📍', 'Pilih bank sampah', 'Cari bank sampah terdekat dan lihat harga per kilogram.'],
  ['📷', 'Setor & foto', 'Pilih jenis sampah, unggah foto, lalu pilih dijemput atau antar sendiri.'],
  ['⚖️', 'Ditimbang petugas', 'Nilai final dihitung dari berat aktual, bukan perkiraan.'],
  ['💰', 'Saldo bertambah', 'Pantau saldo dan riwayat, lengkap dengan notifikasi.'],
]
</script>

<template>
  <Head title="Selamat Datang" />
  <div class="min-h-screen bg-gradient-to-b from-green-100 to-green-50">
    <div class="mx-auto flex max-w-5xl flex-col items-center px-6 py-16 text-center">
      <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-green-700 text-3xl text-white">♻</span>
      <h1 class="mt-5 text-4xl font-extrabold text-green-900 sm:text-5xl">SIRKULA</h1>
      <p class="mt-3 max-w-xl text-gray-600">Sistem Informasi Bank Sampah Sirkular. Ubah sampah jadi tabungan, lalu olah jadi produk bernilai.</p>
      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-primary px-6 py-3">Buka Beranda</Link>
        <template v-else>
          <Link v-if="canLogin" :href="route('login')" class="btn btn-primary px-6 py-3">Masuk</Link>
          <Link v-if="canRegister" :href="route('register')" class="btn btn-outline px-6 py-3">Daftar Customer</Link>
        </template>
      </div>

      <div class="mt-14 grid w-full gap-4 text-left sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="(l, i) in langkah" :key="i" class="card">
          <span class="text-3xl">{{ l[0] }}</span>
          <p class="mt-2 font-bold text-green-900">{{ i + 1 }}. {{ l[1] }}</p>
          <p class="mt-1 text-sm text-gray-500">{{ l[2] }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
